<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoMeta;
use App\Models\Student;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Responsable de Seguimiento (Equipo SI): el consultor digital marcado como responsable de una
 * plataforma trabaja en todos los colegios que la llevan, menos borrar. Meta lo consultan todos.
 */
class ResponsableSeguimientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'consultor_digital', 'consultor_eca'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }
    }

    private function digital(array $responsable = []): array
    {
        $user = User::factory()->create();
        $user->assignRole('consultor_digital');
        $consultor = Consultant::create(['user_id' => $user->id, 'responsable_seguimiento' => $responsable ?: null]);
        $propio = $this->colegio('Colegio propio', ['meta']);
        SchoolConsultant::create(['school_id' => $propio->id, 'consultant_id' => $consultor->id, 'role' => 'digital']);

        return [$user, $consultor, $propio];
    }

    private function colegio(string $nombre, array $plataformas): School
    {
        $school = School::create(['name' => $nombre, 'status' => 'activo']);
        foreach ($plataformas as $p) {
            $school->services()->attach(SchoolServiceType::where('plataforma', $p)->value('id'));
        }

        return $school;
    }

    private function datosMeta(): array
    {
        return ['administrador' => 'Ana', 'usuario' => 'u@x.test', 'contrasena' => 'p', 'capacitacion' => 'Zoom',
            'fecha_capacitacion' => today()->addDays(5)->format('Y-m-d'), 'hora_capacitacion' => '10:30',
            'fecha_aplicacion' => today()->addDays(60)->format('Y-m-d'), 'notas' => null];
    }

    public function test_any_digital_sees_all_meta_schools_but_only_touches_his_own(): void
    {
        [$user, , $propio] = $this->digital();
        $ajeno = $this->colegio('Colegio ajeno', ['meta', 'global-educa']);

        $this->actingAs($user)->get(route('seguimiento.show', 'meta'))
            ->assertOk()->assertSee('Colegio ajeno')->assertSee('Mis colegios');
        $this->get(route('seguimiento.colegio', ['meta', $ajeno]))->assertOk()->assertDontSee('Guardar cambios');
        $this->post(route('seguimiento.guardar', ['meta', $ajeno]), $this->datosMeta())->assertForbidden();
        $this->assertSame(0, SeguimientoMeta::where('school_id', $ajeno->id)->count());

        // Fuera de Meta nada cambia: ni la ficha del colegio ni otras plataformas
        $this->get(route('schools.show', $ajeno))->assertForbidden();
        $this->get(route('seguimiento.colegio', ['global-educa', $ajeno]))->assertForbidden();
        $this->get(route('seguimiento.show', 'global-educa'))->assertDontSee('Colegio ajeno');
        $this->get(route('schools.index'))->assertDontSee('Colegio ajeno');
    }

    public function test_meta_responsible_works_in_every_meta_school_except_deleting(): void
    {
        [$user, $consultor] = $this->digital(['meta']);
        $ajeno = $this->colegio('Colegio ajeno', ['meta', 'global-educa']);
        $sinMeta = $this->colegio('Colegio sin Meta', ['global-educa']);
        $alumno = Student::create(['school_id' => $ajeno->id, 'name' => 'Ana', 'last_name' => 'Ruiz', 'mee_username' => 'ana1', 'mee_password' => 'x', 'level' => 'Primaria', 'grade' => '1°A']);

        $this->actingAs($user);
        $this->get(route('schools.index'))->assertSee('Colegio ajeno')->assertDontSee('Colegio sin Meta');
        foreach ([
            route('schools.show', $ajeno), route('schools.students.index', $ajeno), route('schools.teachers.index', $ajeno),
            route('schools.tickets.create', $ajeno), route('schools.visits.create', $ajeno),
            route('students.edit', $alumno), route('schools.processes.index', $ajeno),
        ] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('schools.reporte-master', $ajeno))->assertSuccessful();
        $this->post(route('schools.students.export-by-class', $ajeno), ['clases' => [base64_encode(json_encode(['Primaria', '1°A']))]])->assertOk();

        // Registra tickets y captura Meta
        $this->post(route('schools.tickets.store', $ajeno), [
            'title' => 'Ticket responsable', 'description' => 'x', 'priority' => 'low', 'consultant_id' => $consultor->id,
            'medium' => 'whatsapp', 'productos' => ['Meta'],
        ])->assertRedirect();
        $this->assertTrue(Ticket::where('school_id', $ajeno->id)->where('title', 'Ticket responsable')->exists());
        $this->get(route('seguimiento.colegio', ['meta', $ajeno]))->assertSee('Guardar cambios');
        $this->post(route('seguimiento.guardar', ['meta', $ajeno]), $this->datosMeta())->assertRedirect();
        $this->assertSame(1, SeguimientoMeta::where('school_id', $ajeno->id)->count());

        // No borra ni edita la ficha general; botones ocultos
        $this->get(route('schools.students.index', $ajeno))->assertDontSee('form-eliminar-alumno-' . $alumno->id);
        $this->delete(route('students.destroy', $alumno))->assertForbidden();
        $this->delete(route('schools.students.destroy-all', $ajeno))->assertForbidden();
        $this->delete(route('schools.teachers.destroy-all', $ajeno))->assertForbidden();
        $this->assertModelExists($alumno);
        $this->get(route('schools.edit', $ajeno))->assertForbidden();

        // Solo es responsable de Meta: Global Educa de ese colegio y colegios sin Meta siguen cerrados
        $this->post(route('seguimiento.guardar', ['global-educa', $ajeno]), ['acciones' => []])->assertForbidden();
        $this->get(route('schools.show', $sinMeta))->assertForbidden();
    }

    public function test_only_admin_assigns_responsibilities(): void
    {
        [$user, $consultor] = $this->digital();

        // El propio consultor no puede dárselo aunque mande el campo
        $this->actingAs($user)->put(route('consultants.update', $consultor), [
            'name' => 'Yo', 'responsable_seguimiento_enviado' => 1, 'responsable_seguimiento' => ['meta'],
        ])->assertRedirect();
        $this->assertNull($consultor->fresh()->responsable_seguimiento);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('consultants.edit', $consultor))->assertOk()->assertSee('Responsable de Seguimiento');
        $this->put(route('consultants.update', $consultor), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'consultor_digital',
            'responsable_seguimiento_enviado' => 1, 'responsable_seguimiento' => ['meta', 'inventada'],
        ])->assertRedirect();
        $this->assertSame(['meta'], $consultor->fresh()->responsable_seguimiento);
        $this->get(route('consultants.index'))->assertSee('Resp. Meta');

        // Desmarcar todo la quita; y no aplica a otros roles
        $this->put(route('consultants.update', $consultor), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'consultor_eca', 'responsable_seguimiento_enviado' => 1,
            'responsable_seguimiento' => ['meta'],
        ])->assertRedirect();
        $this->assertNull($consultor->fresh()->responsable_seguimiento);
    }
}
