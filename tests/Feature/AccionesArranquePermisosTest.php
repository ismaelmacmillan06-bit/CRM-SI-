<?php

namespace Tests\Feature;

use App\Http\Controllers\SeguimientoController;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regla: personalizar el catálogo de acciones de arranque es solo del admin;
 * capturar avances en sus colegios lo siguen haciendo los consultores digitales.
 */
class AccionesArranquePermisosTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));

        return $user;
    }

    private function colegioDeDigital(User $user): School
    {
        $consultant = Consultant::create(['user_id' => $user->id]);
        $school = School::create(['name' => 'Colegio Digital', 'status' => 'activo']);
        $school->services()->attach(SchoolServiceType::where('plataforma', 'global-educa')->value('id'));
        SchoolConsultant::create(['school_id' => $school->id, 'consultant_id' => $consultant->id, 'role' => 'digital']);

        return $school;
    }

    public function test_digital_captures_seguimiento_but_cannot_customize_catalog(): void
    {
        $user = $this->usuario('consultor_digital');
        $school = $this->colegioDeDigital($user);

        $this->actingAs($user)
            ->get(route('seguimiento.colegio', ['global-educa', $school]))
            ->assertOk()
            ->assertSee('Guardar cambios')
            ->assertDontSee('Personalizar acciones');

        $acciones = collect(SeguimientoController::PLATAFORMAS['global-educa']['acciones'])
            ->map(fn () => ['status' => 'done', 'notes' => null])
            ->all();
        $this->post(route('seguimiento.guardar', ['global-educa', $school]), ['acciones' => $acciones])
            ->assertRedirect();
        $this->assertDatabaseHas('seguimiento_acciones', ['school_id' => $school->id, 'status' => 'done']);

        $this->post(route('seguimiento.acciones.store', 'global-educa'), ['etiqueta' => 'Nueva'])
            ->assertForbidden();
        $this->post(route('processes.catalog.store'), ['name' => 'Nueva'])
            ->assertForbidden();
    }

    public function test_digital_sees_school_processes_editable_without_catalog_button(): void
    {
        $user = $this->usuario('consultor_digital');
        $school = $this->colegioDeDigital($user);

        $this->actingAs($user)
            ->get(route('schools.processes.index', $school))
            ->assertOk()
            ->assertDontSee('Modo consulta')
            ->assertDontSee('Personalizar acciones de arranque');
    }

    public function test_admin_can_customize_catalog(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->postJson(route('seguimiento.acciones.store', 'global-educa'), ['etiqueta' => 'Acción nueva'])
            ->assertSuccessful();
        $this->assertDatabaseHas('seguimiento_definicion_acciones', ['plataforma' => 'global-educa', 'etiqueta' => 'Acción nueva']);
    }
}
