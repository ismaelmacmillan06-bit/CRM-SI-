<?php

namespace Tests\Feature;

use App\Http\Controllers\SeguimientoController;
use App\Models\School;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoRecurso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rol consul_ged (Consultor Global Educa): consulta casi todo y tiene libertad
 * total solo en Seguimiento → Global Educa.
 */
class ConsulGedRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'consultor_digital', 'consultor_eca', 'consultor_elt', 'representante_ventas', 'coordinador', 'consul_ged'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }
    }

    private function ged(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'consul_ged']));

        return $user;
    }

    private function colegioCon(string ...$plataformas): School
    {
        $school = School::create(['name' => 'Colegio GED', 'status' => 'activo']);
        foreach ($plataformas as $p) {
            $school->services()->attach(SchoolServiceType::where('plataforma', $p)->value('id'));
        }

        return $school;
    }

    public function test_sees_allowed_sections_and_not_the_rest(): void
    {
        $school = $this->colegioCon('global-educa');
        $this->actingAs($this->ged());

        foreach ([
            route('dashboard'), route('avance-colegios.index'), route('reporte-semanal.index'),
            route('schools.index'), route('bundles.index'), route('herramientas.index'),
            route('produccion.index'), route('seguimiento-sic.index'), route('notes.index'),
            route('seguimiento.show', 'global-educa'), route('seguimiento.show', 'meta'),
            route('seguimiento.show', 'frances'), route('seguimiento.show', 'red-roo-reads'),
            route('seguimiento.show', 'equipamiento'),
            route('schools.students.index', $school), route('schools.tickets.index', $school),
        ] as $url) {
            $this->get($url)->assertSuccessful();
        }
        // Formato de entrega: llega al generador (sin alumnos responde 422, no redirige ni 403)
        $formato = $this->get(route('schools.formato-entrega', ['school' => $school, 'tipo' => 'alumnos']));
        $this->assertNotContains($formato->status(), [302, 403]);

        $this->get(route('schools.show', $school))->assertOk()
            ->assertSee('REPORT MASTER')
            ->assertDontSee(route('schools.edit', $school));

        foreach ([
            route('consultants.index'), route('alumnos-docentes.index'), route('ssa.index'),
            route('tareas.index'), route('configuracion.servicios.index'), route('schools.create'),
            route('schools.edit', $school), route('bitacora.index'),
        ] as $url) {
            $this->get($url)->assertRedirect();
        }

        $menu = $this->get(route('dashboard'))->getContent();
        $this->assertStringNotContainsString(route('configuracion.servicios.index'), $menu);
        $this->assertStringNotContainsString(route('consultants.index'), $menu);
        $this->assertStringContainsString(route('herramientas.index'), $menu);
        $this->assertStringContainsString(route('seguimiento-sic.index'), $menu);
    }

    public function test_captures_global_educa_but_only_views_other_platforms(): void
    {
        $school = $this->colegioCon('global-educa', 'frances');
        $this->actingAs($this->ged());

        $this->get(route('seguimiento.colegio', ['global-educa', $school]))->assertOk()
            ->assertSee('Guardar cambios')->assertDontSee('Personalizar acciones');
        $this->get(route('seguimiento.colegio', ['frances', $school]))->assertOk()
            ->assertDontSee('Guardar cambios');

        $ge = collect(SeguimientoController::PLATAFORMAS['global-educa']['acciones'])->map(fn () => ['status' => 'done', 'notes' => null])->all();
        $this->post(route('seguimiento.guardar', ['global-educa', $school]), ['acciones' => $ge])->assertRedirect();
        $this->assertDatabaseHas('seguimiento_acciones', ['school_id' => $school->id, 'plataforma' => 'global-educa', 'status' => 'done']);

        $fr = collect(SeguimientoController::PLATAFORMAS['frances']['acciones'])->map(fn () => ['status' => 'done', 'notes' => null])->all();
        $this->post(route('seguimiento.guardar', ['frances', $school]), ['acciones' => $fr])
            ->assertSessionHas('error_acceso');
        $this->assertDatabaseMissing('seguimiento_acciones', ['school_id' => $school->id, 'plataforma' => 'frances']);

        // El catálogo de acciones sigue siendo solo del admin
        $this->post(route('seguimiento.acciones.store', 'global-educa'), ['etiqueta' => 'Nueva'])->assertForbidden();
    }

    public function test_manages_global_educa_files_only(): void
    {
        Storage::fake('local');
        $this->actingAs($this->ged());
        $datos = fn () => [
            'titulo' => 'Manual GE', 'categoria' => 'manuales', 'audiencia' => ['consultor_digital'],
            'archivo' => UploadedFile::fake()->create('manual.pdf', 10, 'application/pdf'),
        ];

        $this->post(route('seguimiento.recursos.store', 'global-educa'), $datos())->assertRedirect();
        $recurso = SeguimientoRecurso::where('plataforma', 'global-educa')->firstOrFail();

        // Aunque no esté en la audiencia, lo ve y lo puede borrar porque gestiona Global Educa
        $this->get(route('seguimiento.show', 'global-educa'))->assertSee('Manual GE');
        $this->delete(route('seguimiento.recursos.destroy', $recurso))->assertRedirect();
        $this->assertModelMissing($recurso);

        $this->post(route('seguimiento.recursos.store', 'frances'), $datos())->assertSessionHas('error_acceso');
        $this->assertSame(0, SeguimientoRecurso::where('plataforma', 'frances')->count());
    }

    public function test_cannot_modify_schools_bundles_tools_or_weekly_report(): void
    {
        $school = $this->colegioCon('global-educa');
        $this->actingAs($this->ged());

        $this->delete(route('schools.destroy', $school))->assertSessionHas('error_acceso');
        $this->assertModelExists($school);
        $this->post(route('reporte-semanal.store'), ['payload' => '[]', 'week' => today()->toDateString()])
            ->assertSessionHas('error_acceso');
        $this->post(route('bundles.store'), ['serie' => 'X'])->assertSessionHas('error_acceso');
        $this->post(route('herramientas.archivos.store'), [])->assertSessionHas('error_acceso');
        $this->delete(route('produccion.destroy'))->assertSessionHas('error_acceso');

        $this->get(route('reporte-semanal.index'))->assertOk()->assertDontSee('Guardar reporte');
        $bundle = \App\Models\Bundle::create(['serie' => 'S1', 'name' => 'Libro', 'grade' => '1', 'level' => 'Primaria', 'role' => 'student', 'type' => 'ELT']);
        $this->get(route('bundles.index'))->assertOk()->assertDontSee('form-eliminar-bundle-' . $bundle->id);
    }
}
