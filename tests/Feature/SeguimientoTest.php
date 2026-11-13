<?php

namespace Tests\Feature;

use App\Http\Controllers\SeguimientoController;
use App\Models\ActivityLog;
use App\Models\Consultant;
use App\Models\Level;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoAccion;
use App\Models\Ticket;
use App\Models\Visit;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguimientoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));

        return $user;
    }

    private function colegio(string $nombre, bool $globalEduca = true, string $status = 'activo'): School
    {
        $school = School::create(['name' => $nombre, 'status' => $status]);
        if ($globalEduca) {
            $school->services()->attach(SchoolServiceType::where('plataforma', 'global-educa')->value('id'));
        }

        return $school;
    }

    private function todasCompletas(): array
    {
        return collect(SeguimientoController::PLATAFORMAS['global-educa']['acciones'])
            ->map(fn () => ['status' => 'done', 'notes' => null])
            ->all();
    }

    public function test_migration_links_services_and_creates_meta_and_frances(): void
    {
        $this->assertSame('global-educa', SchoolServiceType::where('name', 'Global Educa')->value('plataforma'));
        $this->assertSame('red-roo-reads', SchoolServiceType::where('name', 'Red Roo Reads')->value('plataforma'));
        $this->assertSame('meta', SchoolServiceType::where('name', 'Meta')->value('plataforma'));
        $this->assertSame('frances', SchoolServiceType::where('name', 'Francés')->value('plataforma'));
    }

    public function test_equipamiento_appears_in_menu_as_under_construction(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->get(route('seguimiento.show', 'equipamiento'))
            ->assertOk()
            ->assertSee('Sección en construcción')
            ->assertSee(route('seguimiento.show', 'equipamiento'), false);
    }

    public function test_dashboard_lists_only_active_schools_with_the_service(): void
    {
        $this->colegio('Con servicio');
        $this->colegio('Sin servicio', false);
        $this->colegio('Inactivo con servicio', true, 'inactivo');

        $this->actingAs($this->usuario('admin'))
            ->get(route('seguimiento.show', 'global-educa'))
            ->assertOk()
            ->assertSee('Con servicio')
            ->assertDontSee('Sin servicio')
            ->assertDontSee('Inactivo con servicio')
            ->assertSee('Kickoff (contacto con el colegio)');
    }

    public function test_dashboard_and_detail_link_to_school_tickets_and_sessions(): void
    {
        $school = $this->colegio('Con accesos directos');
        $this->actingAs($this->usuario('admin'));

        foreach ([route('seguimiento.show', 'global-educa'), route('seguimiento.colegio', ['global-educa', $school])] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee(route('schools.tickets.index', $school), false)
                ->assertSee(route('schools.visits.index', $school), false);
        }
    }

    private function actividad(School $school, array $productos, string $ticketStatus = 'open', string $sesionStatus = 'pendiente'): void
    {
        $consultor = Consultant::firstOrCreate(['user_id' => User::factory()->create()->id]);
        Ticket::create(['school_id' => $school->id, 'consultant_id' => $consultor->id, 'title' => 'Ticket ' . implode('+', $productos),
            'description' => 'x', 'status' => $ticketStatus, 'priority' => 'medium', 'medium' => 'whatsapp', 'productos' => $productos]);
        Visit::create(['school_id' => $school->id, 'consultant_id' => $consultor->id, 'status' => $sesionStatus, 'scheduled_date' => today(),
            'modalidad' => 'virtual', 'tipo' => 'programada', 'prioridad' => 'media', 'productos' => $productos]);
    }

    public function test_cards_count_only_tickets_and_sessions_with_global_educa_product(): void
    {
        $school = $this->colegio('Colegio GE');
        $sinServicio = $this->colegio('Colegio sin GE', false);
        $this->actividad($school, ['Global Educa'], 'closed', 'terminada');
        $this->actividad($school, ['MEE', 'Global Educa']);
        $this->actividad($school, ['Meta']);              // otro producto: no cuenta
        $this->actividad($sinServicio, ['Global Educa']); // colegio fuera de la lista: no cuenta

        $this->actingAs($this->usuario('admin'))
            ->get(route('seguimiento.show', 'global-educa'))
            ->assertOk()
            ->assertSee('Tickets Global Educa')
            ->assertSee('1 abiertos · 1 cerrados')
            ->assertSee('Sesiones Global Educa')
            ->assertSee('1 realizadas · 1 pendientes')
            ->assertSee(route('seguimiento.excel', 'global-educa'), false);
    }

    public function test_excel_download_for_global_educa(): void
    {
        $school = School::create(['name' => 'Colegio Excel GE', 'status' => 'activo', 'nexus_id' => 'GE-1']);
        $school->services()->attach(SchoolServiceType::where('plataforma', 'global-educa')->value('id'));
        $school->levels()->attach(Level::create(['name' => 'Primaria X'])->id);
        $this->actividad($school, ['Global Educa'], 'closed', 'terminada');
        $this->actividad($school, ['Meta']);
        $this->actingAs($this->usuario('admin'));
        $this->post(route('seguimiento.guardar', ['global-educa', $school]), [
            'acciones' => ['kickoff' => ['status' => 'done', 'notes' => null], 'pruebas' => ['status' => 'in_progress', 'notes' => null]],
        ]);

        $res = $this->get(route('seguimiento.excel', 'global-educa'))->assertOk();
        $this->assertStringContainsString('Seguimiento_Global_Educa_', $res->headers->get('content-disposition'));

        $libro = IOFactory::load($res->getFile()->getPathname());
        $this->assertSame(['Resumen', 'Colegios', 'Acciones de arranque', 'Tickets', 'Sesiones'], $libro->getSheetNames());
        $this->assertSame('Implementación completa (7/7)', $libro->getSheetByName('Resumen')->getCell('A6')->getValue());
        $this->assertSame('Tickets Global Educa', $libro->getSheetByName('Resumen')->getCell('A7')->getValue());

        $colegios = $libro->getSheetByName('Colegios');
        $this->assertSame('GE-1', $colegios->getCell('A5')->getValue());
        $this->assertSame('Primaria X', $colegios->getCell('E5')->getValue());
        $this->assertSame('1/7 (14%)', $colegios->getCell('F5')->getValue());
        $this->assertNull($colegios->getCell('H4')->getValue()); // sin columnas de Meta

        $acciones = $libro->getSheetByName('Acciones de arranque');
        $this->assertSame('Kickoff (contacto con el colegio)', $acciones->getCell('C4')->getValue());
        $this->assertSame('Completado', $acciones->getCell('C5')->getValue());
        $this->assertSame('En proceso', $acciones->getCell('F5')->getValue());

        $this->assertSame('Ticket Global Educa', $libro->getSheetByName('Tickets')->getCell('C5')->getValue());
        $this->assertNull($libro->getSheetByName('Tickets')->getCell('C6')->getValue());
        $this->assertSame('Terminada', $libro->getSheetByName('Sesiones')->getCell('E5')->getValue());
    }

    public function test_school_levels_are_shown_in_dashboard_and_detail(): void
    {
        $school = $this->colegio('Con niveles');
        $school->levels()->attach(Level::create(['name' => 'Nivel de prueba'])->id);

        $this->actingAs($this->usuario('admin'));
        $this->get(route('seguimiento.show', 'global-educa'))->assertSee('Nivel de prueba');
        $this->get(route('seguimiento.colegio', ['global-educa', $school]))->assertSee('Nivel de prueba');
    }

    public function test_renaming_the_service_does_not_break_the_relation(): void
    {
        $school = $this->colegio('Colegio renombrado');
        SchoolServiceType::where('plataforma', 'global-educa')->update(['name' => 'GE Plataforma']);

        $this->actingAs($this->usuario('admin'))
            ->get(route('seguimiento.show', 'global-educa'))
            ->assertSee('Colegio renombrado');

        $this->assertTrue($school->fresh()->tieneGlobalEduca());
    }

    public function test_saving_actions_counts_as_complete_implementation(): void
    {
        $admin = $this->usuario('admin');
        $school = $this->colegio('Completo');

        $this->actingAs($admin)
            ->post(route('seguimiento.guardar', ['global-educa', $school]), ['acciones' => $this->todasCompletas()])
            ->assertRedirect();

        $this->assertSame(7, SeguimientoAccion::where('school_id', $school->id)->where('status', 'done')->count());
        $this->assertSame($admin->id, SeguimientoAccion::where('school_id', $school->id)->value('completed_by'));
        $this->assertSame(7, ActivityLog::where('tipo', 'seguimiento')->count());

        $this->get(route('seguimiento.show', 'global-educa'))->assertSee('7/7')->assertSee('100%');
        $this->get(route('seguimiento.colegio', ['global-educa', $school]))->assertSee('7/7 completadas');
    }

    public function test_reverting_an_action_clears_who_completed_it(): void
    {
        $school = $this->colegio('Revertir');
        $this->actingAs($this->usuario('admin'));

        $this->post(route('seguimiento.guardar', ['global-educa', $school]), ['acciones' => $this->todasCompletas()]);
        $this->post(route('seguimiento.guardar', ['global-educa', $school]), [
            'acciones' => ['kickoff' => ['status' => 'in_progress', 'notes' => 'Se reabrió']],
        ]);

        $accion = SeguimientoAccion::where('school_id', $school->id)->where('accion', 'kickoff')->first();
        $this->assertSame('in_progress', $accion->status);
        $this->assertNull($accion->completed_at);
        $this->assertNull($accion->completed_by);
        $this->assertSame('Se reabrió', $accion->notes);
    }

    public function test_school_without_the_service_is_not_found(): void
    {
        $school = $this->colegio('Ajeno', false);

        $this->actingAs($this->usuario('admin'))
            ->get(route('seguimiento.colegio', ['global-educa', $school]))
            ->assertNotFound();
    }

    public function test_digital_consultant_only_manages_assigned_schools(): void
    {
        $user = $this->usuario('consultor_digital');
        $consultant = Consultant::create(['user_id' => $user->id]);
        $propio = $this->colegio('Colegio propio');
        $ajeno = $this->colegio('Colegio ajeno');
        SchoolConsultant::create(['school_id' => $propio->id, 'consultant_id' => $consultant->id, 'role' => 'digital']);

        $this->actingAs($user)->get(route('seguimiento.show', 'global-educa'))
            ->assertSee('Colegio propio')
            ->assertDontSee('Colegio ajeno');

        $this->get(route('seguimiento.colegio', ['global-educa', $propio]))->assertOk();
        $this->get(route('seguimiento.colegio', ['global-educa', $ajeno]))->assertForbidden();
        $this->post(route('seguimiento.guardar', ['global-educa', $ajeno]), ['acciones' => $this->todasCompletas()])
            ->assertForbidden();
    }

    public function test_read_only_roles_can_view_but_not_save(): void
    {
        $school = $this->colegio('Colegio consulta');

        foreach (['consultor_eca', 'consultor_elt', 'coordinador', 'representante_ventas'] as $rol) {
            $this->actingAs($this->usuario($rol));
            $this->get(route('seguimiento.show', 'global-educa'))
                ->assertOk()
                ->assertSee('Colegio consulta')
                ->assertSee('Ver →')
                ->assertDontSee('Gestionar →');
            $this->get(route('seguimiento.colegio', ['global-educa', $school]))
                ->assertOk()
                ->assertSee('Modo consulta')
                ->assertSee('<fieldset disabled', false)
                ->assertDontSee('Guardar cambios');
            $this->get(route('seguimiento.show', 'frances'))->assertOk();
            $this->post(route('seguimiento.guardar', ['global-educa', $school]), ['acciones' => $this->todasCompletas()])
                ->assertRedirect();
        }

        $this->assertSame(0, SeguimientoAccion::count());
    }

    public function test_read_only_roles_see_seguimiento_in_the_menu(): void
    {
        $this->actingAs($this->usuario('consultor_eca'))
            ->get(route('seguimiento.show', 'meta'))
            ->assertSee(route('seguimiento.show', 'global-educa'), false);
    }

    public function test_save_is_forbidden_in_controller_for_read_only_roles(): void
    {
        $school = $this->colegio('Colegio');
        $this->actingAs($this->usuario('consultor_eca'));

        // Aunque el middleware se saltara, el controlador también lo impide
        $this->withoutMiddleware(\App\Http\Middleware\VerificarAccesoRol::class)
            ->post(route('seguimiento.guardar', ['global-educa', $school]), ['acciones' => $this->todasCompletas()])
            ->assertForbidden();
        $this->assertSame(0, SeguimientoAccion::count());
    }

    public function test_platform_can_only_be_linked_to_one_service(): void
    {
        $lego = SchoolServiceType::where('name', 'Lego')->first();

        $this->actingAs($this->usuario('admin'))
            ->from(route('configuracion.servicios.index'))
            ->patch(route('configuracion.servicios.update', $lego), ['name' => 'Lego', 'plataforma' => 'global-educa'])
            ->assertSessionHasErrors(['plataforma'], null, 'editar_' . $lego->id);

        $this->assertNull($lego->fresh()->plataforma);
    }
}
