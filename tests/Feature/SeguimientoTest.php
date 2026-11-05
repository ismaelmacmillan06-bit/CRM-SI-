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

    public function test_other_roles_cannot_enter(): void
    {
        $school = $this->colegio('Colegio');

        foreach (['consultor_eca', 'consultor_elt', 'coordinador', 'representante_ventas'] as $rol) {
            $this->actingAs($this->usuario($rol));
            $this->get(route('seguimiento.show', 'global-educa'))->assertRedirect();
            $this->post(route('seguimiento.guardar', ['global-educa', $school]), ['acciones' => $this->todasCompletas()])
                ->assertRedirect();
        }

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
