<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolServiceType;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SesionesTest extends TestCase
{
    use RefreshDatabase;

    private function setupSchool(bool $globalEduca = false): array
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole($role);
        $consultant = Consultant::create(['user_id' => $user->id]);
        $school = School::create([
            'name' => 'Colegio de prueba',
            'status' => 'activo',
            'consultant_id' => $consultant->id,
        ]);
        if ($globalEduca) {
            $school->services()->attach(SchoolServiceType::where('name', 'Global Educa')->value('id'));
        }
        $this->actingAs($user);

        return [$user, $consultant, $school];
    }

    public function test_formato_entrega_button_and_modal_visibility_based_on_global_educa(): void
    {
        // Caso 1: Colegio sin Global Educa
        [, , $schoolWithout] = $this->setupSchool(false);
        $responseWithout = $this->get(route('schools.show', $schoolWithout));
        $responseWithout->assertOk();
        $responseWithout->assertDontSee('🌐 Formato Global Educa');
        $responseWithout->assertDontSee('id="formato-entrega-dialog"', false);
        $responseWithout->assertDontSee('data-formato-entrega', false);
        $responseWithout->assertDontSee(route('schools.formato-entrega.script', $schoolWithout));

        // Caso 2: Colegio con Global Educa
        [, , $schoolWith] = $this->setupSchool(true);
        $responseWith = $this->get(route('schools.show', $schoolWith));
        $responseWith->assertOk();
        $responseWith->assertSee('🌐 Formato Global Educa');
        $responseWith->assertSee('id="formato-entrega-dialog"', false);
        $responseWith->assertSee('data-formato-entrega', false);
        $responseWith->assertSee(route('schools.formato-entrega.script', $schoolWith));
    }

    public function test_sesiones_button_in_school_show(): void
    {
        [, , $school] = $this->setupSchool(false);
        $response = $this->get(route('schools.show', $school));
        $response->assertOk();
        $response->assertSee('📅 Sesiones');
        $response->assertSee(route('schools.visits.index', $school));
    }

    public function test_formato_entrega_endpoints_are_forbidden_without_global_educa(): void
    {
        [, , $school] = $this->setupSchool(false);

        $this->assertFalse($school->tieneGlobalEduca());
        $this->get(route('schools.formato-entrega', $school))->assertForbidden();
        $this->get(route('schools.formato-entrega.accesos', $school))->assertForbidden();
        $this->get(route('schools.formato-entrega.script', $school))->assertForbidden();
    }

    public function test_formato_entrega_endpoints_are_accessible_with_global_educa(): void
    {
        [, , $school] = $this->setupSchool(true);

        $this->assertTrue($school->tieneGlobalEduca());
        $this->get(route('schools.formato-entrega.script', $school))->assertOk();
    }

    public function test_session_requires_productos_and_valid_modalidad_tipo_prioridad(): void
    {
        [, $consultant, $school] = $this->setupSchool();

        // Sin productos ni modalidad válida
        $this->post(route('schools.visits.store', $school), [
            'consultant_id' => $consultant->id,
            'scheduled_date' => '2026-11-01',
            'status' => 'pendiente',
            'modalidad' => 'otra',
            'tipo' => 'invalido',
            'prioridad' => 'invalida',
        ])->assertSessionHasErrors(['modalidad', 'tipo', 'prioridad', 'productos']);

        // Con productos vacíos
        $this->post(route('schools.visits.store', $school), [
            'consultant_id' => $consultant->id,
            'scheduled_date' => '2026-11-01',
            'status' => 'pendiente',
            'modalidad' => 'presencial',
            'tipo' => 'programada',
            'prioridad' => 'media',
            'productos' => [],
        ])->assertSessionHasErrors(['productos']);

        // Con un producto fuera del catálogo permitido
        $this->post(route('schools.visits.store', $school), [
            'consultant_id' => $consultant->id,
            'scheduled_date' => '2026-11-01',
            'status' => 'pendiente',
            'modalidad' => 'presencial',
            'tipo' => 'programada',
            'prioridad' => 'media',
            'productos' => ['ProductoInexistente'],
        ])->assertSessionHasErrors(['productos.0']);
    }

    public function test_programmed_session_requires_scheduled_date(): void
    {
        [, $consultant, $school] = $this->setupSchool();

        $this->post(route('schools.visits.store', $school), [
            'consultant_id' => $consultant->id,
            'scheduled_date' => null,
            'status' => 'pendiente',
            'modalidad' => 'presencial',
            'tipo' => 'programada',
            'prioridad' => 'media',
            'productos' => ['MEE'],
        ])->assertSessionHasErrors(['scheduled_date']);
    }

    public function test_emergency_session_requires_visit_date_and_forces_muy_urgente(): void
    {
        [, $consultant, $school] = $this->setupSchool();

        // Emergencia sin visit_date falla
        $this->post(route('schools.visits.store', $school), [
            'consultant_id' => $consultant->id,
            'status' => 'terminada',
            'modalidad' => 'presencial',
            'tipo' => 'emergencia',
            'prioridad' => 'baja',
            'productos' => ['MEE'],
        ])->assertSessionHasErrors(['visit_date']);

        // Emergencia con visit_date fuerza prioridad muy_urgente y anula scheduled_date
        $this->post(route('schools.visits.store', $school), [
            'consultant_id' => $consultant->id,
            'scheduled_date' => '2026-11-01',
            'visit_date' => '2026-10-27',
            'status' => 'terminada',
            'modalidad' => 'presencial',
            'tipo' => 'emergencia',
            'prioridad' => 'baja',
            'productos' => ['Equipamiento'],
        ])->assertSessionHasNoErrors();

        $visit = Visit::firstOrFail();
        $this->assertSame('muy_urgente', $visit->prioridad);
        $this->assertNull($visit->scheduled_date);
    }

    public function test_session_lifecycle_crud_and_activity_logs(): void
    {
        [, $consultant, $school] = $this->setupSchool();

        $payload = [
            'consultant_id' => $consultant->id,
            'scheduled_date' => '2026-11-01',
            'status' => 'pendiente',
            'modalidad' => 'virtual',
            'tipo' => 'programada',
            'prioridad' => 'alta',
            'productos' => ['MEE', 'Lego'],
            'motivo' => 'Capacitación docente inicial',
        ];

        // Crear
        $this->post(route('schools.visits.store', $school), $payload)
            ->assertRedirect(route('schools.visits.index', $school))
            ->assertSessionHas('success', 'Sesión registrada correctamente.');

        $visit = Visit::firstOrFail();
        $this->assertSame(['MEE', 'Lego'], $visit->productos);
        $this->assertSame('virtual', $visit->modalidad);
        $this->assertSame('programada', $visit->tipo);
        $this->assertSame('alta', $visit->prioridad);

        $logRegistro = ActivityLog::where('school_id', $school->id)->latest()->first();
        $this->assertNotNull($logRegistro);
        $this->assertStringContainsString('Sesión programada registrada', $logRegistro->descripcion);

        // Vistas index, create, edit, show
        $this->get(route('schools.visits.index', $school))
            ->assertOk()
            ->assertSee('Sesiones — Colegio de prueba')
            ->assertSee('Lego')
            ->assertSee('Alta');

        $this->get(route('schools.visits.create', $school))->assertOk();
        $this->get(route('visits.edit', $visit))
            ->assertOk()
            ->assertSee('Editar sesión')
            ->assertSee('Capacitación docente inicial');

        // Actualizar
        $payload['status'] = 'terminada';
        $payload['visit_date'] = '2026-11-01';
        $payload['productos'] = ['Global Educa'];
        $this->put(route('visits.update', $visit), $payload)
            ->assertRedirect(route('schools.visits.index', $school))
            ->assertSessionHas('success', 'Sesión actualizada correctamente.');

        $visit->refresh();
        $this->assertSame('terminada', $visit->status);
        $this->assertSame(['Global Educa'], $visit->productos);

        $logCambio = ActivityLog::where('school_id', $school->id)->latest()->first();
        $this->assertStringContainsString('Sesión cambió de Pendiente → Terminada', $logCambio->descripcion);

        // Eliminar
        $this->delete(route('visits.destroy', $visit))
            ->assertRedirect(route('schools.visits.index', $school))
            ->assertSessionHas('success', 'Sesión eliminada correctamente.');

        $this->assertNull(Visit::find($visit->id));
    }

    public function test_dashboard_renders_sesiones_stat_card_and_modal(): void
    {
        [, $consultant, $school] = $this->setupSchool();

        Visit::create([
            'school_id' => $school->id,
            'consultant_id' => $consultant->id,
            'scheduled_date' => '2026-11-05',
            'status' => 'pendiente',
            'modalidad' => 'presencial',
            'tipo' => 'programada',
            'prioridad' => 'media',
            'productos' => ['MEE'],
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Sesiones');
        $response->assertSee('id="modal-visitas"', false);
        $response->assertSee('filtro-producto-dash', false);
        $response->assertSee('Colegio de prueba');
    }

    public function test_reporte_master_exports_sesiones_sheet_with_new_columns(): void
    {
        [, $consultant, $school] = $this->setupSchool();

        Visit::create([
            'school_id' => $school->id,
            'consultant_id' => $consultant->id,
            'scheduled_date' => '2026-11-05',
            'visit_date' => '2026-11-05',
            'status' => 'terminada',
            'modalidad' => 'virtual',
            'tipo' => 'programada',
            'prioridad' => 'alta',
            'productos' => ['MEE', 'Lego'],
            'notes' => 'Nota de prueba',
            'summary' => 'Solución de prueba',
        ]);

        $response = $this->get(route('schools.reporte-master', $school));
        $response->assertOk();
        $tempPath = tempnam(sys_get_temp_dir(), 'rep_master_');
        file_put_contents($tempPath, $response->streamedContent());

        try {
            $spreadsheet = IOFactory::load($tempPath);
            $sheetNames = $spreadsheet->getSheetNames();
            $this->assertContains('Sesiones', $sheetNames);

            $sesionesSheet = $spreadsheet->getSheetByName('Sesiones');
            $this->assertNotNull($sesionesSheet);

            $expectedHeaders = [
                'Fecha Programada', 'Fecha Real', 'Status', 'Consultor',
                'Modalidad', 'Tipo', 'Prioridad', 'Producto(s)',
                'Notas', 'Resumen', 'Próxima Sesión', 'Evidencia'
            ];

            $actualHeaders = [];
            foreach (range('A', 'L') as $col) {
                $actualHeaders[] = $sesionesSheet->getCell("{$col}4")->getValue();
            }
            $this->assertSame($expectedHeaders, $actualHeaders);

            // Verificar contenido de fila 5
            $this->assertSame('💻 Virtual', $sesionesSheet->getCell('E5')->getValue());
            $this->assertSame('📅 Programada', $sesionesSheet->getCell('F5')->getValue());
            $this->assertSame('Alta', $sesionesSheet->getCell('G5')->getValue());
            $this->assertSame('MEE, Lego', $sesionesSheet->getCell('H5')->getValue());
        } finally {
            @unlink($tempPath);
        }
    }
}
