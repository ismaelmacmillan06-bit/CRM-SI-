<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Consultant;
use App\Models\Level;
use App\Models\Process;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolLevel;
use App\Models\SchoolLevelProcess;
use App\Models\Student;
use App\Models\TareaSI;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pendientes de auditoría cerrados el 2026-11-26: rutas shallow por colegio,
 * Equipo SI solo admin, notificaciones de tareas académicas y XSS en Bundles.
 */
class PendientesAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));

        return $user;
    }

    private function asignar(User $user, School $school, string $area): Consultant
    {
        $consultant = Consultant::firstOrCreate(['user_id' => $user->id]);
        SchoolConsultant::create(['school_id' => $school->id, 'consultant_id' => $consultant->id, 'role' => $area]);

        return $consultant;
    }

    public function test_digital_cannot_touch_students_teachers_or_visits_of_other_schools(): void
    {
        $digital = $this->usuario('consultor_digital');
        $propio = School::create(['name' => 'Propio', 'status' => 'activo']);
        $ajeno = School::create(['name' => 'Ajeno', 'status' => 'activo']);
        $consultant = $this->asignar($digital, $propio, 'digital');

        $registros = function (School $school) use ($consultant) {
            return [
                'students' => Student::create(['school_id' => $school->id, 'name' => 'Ana', 'last_name' => 'Ruiz', 'mee_username' => uniqid('a'), 'mee_password' => 'x', 'level' => 'Primaria', 'grade' => '1°A']),
                'teachers' => Teacher::create(['school_id' => $school->id, 'name' => 'Luis', 'last_name' => 'Paz', 'email' => uniqid() . '@x.test']),
                'visits'   => Visit::create(['school_id' => $school->id, 'consultant_id' => $consultant->id, 'status' => 'pendiente', 'scheduled_date' => today(), 'modalidad' => 'virtual', 'tipo' => 'programada', 'prioridad' => 'media']),
            ];
        };
        $ajenos = $registros($ajeno);
        $propios = $registros($propio);

        $this->actingAs($digital);
        foreach ($ajenos as $recurso => $modelo) {
            $this->get("/{$recurso}/{$modelo->id}/edit")->assertForbidden();
            $this->put("/{$recurso}/{$modelo->id}", [])->assertForbidden();
            $this->delete("/{$recurso}/{$modelo->id}")->assertForbidden();
            $this->assertModelExists($modelo);
        }
        foreach ($propios as $recurso => $modelo) {
            $this->get("/{$recurso}/{$modelo->id}/edit")->assertOk();
        }
    }

    public function test_school_process_must_belong_to_the_school_in_the_url(): void
    {
        $digital = $this->usuario('consultor_digital');
        $propio = School::create(['name' => 'Propio', 'status' => 'activo']);
        $ajeno = School::create(['name' => 'Ajeno', 'status' => 'activo']);
        $this->asignar($digital, $propio, 'digital');

        $level = Level::first() ?? Level::create(['name' => 'Primaria']);
        $process = Process::first() ?? Process::create(['name' => 'Alta', 'slug' => 'alta', 'order' => 1]);
        $slpAjeno = SchoolLevelProcess::create([
            'school_level_id' => SchoolLevel::create(['school_id' => $ajeno->id, 'level_id' => $level->id])->id,
            'process_id' => $process->id,
            'status' => 'pending',
        ]);

        $this->actingAs($digital)
            ->post(route('schools.processes.update', [$propio, $slpAjeno]), ['status' => 'done'])
            ->assertNotFound();
        $this->assertSame('pending', $slpAjeno->fresh()->status);
    }

    public function test_only_admin_manages_team_members_and_roles(): void
    {
        $digital = $this->usuario('consultor_digital');
        $propio = Consultant::create(['user_id' => $digital->id]);
        $otro = Consultant::create(['user_id' => $this->usuario('consultor_eca')->id]);

        $this->actingAs($digital);
        $this->get(route('consultants.create'))->assertForbidden();
        $this->post(route('consultants.store'), [
            'name' => 'Intruso', 'email' => 'intruso@x.test', 'password' => 'secreto123',
            'password_confirmation' => 'secreto123', 'role' => 'admin',
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'intruso@x.test']);

        $this->get(route('consultants.edit', $otro))->assertForbidden();
        $this->put(route('consultants.update', $otro), ['name' => 'Cambiado'])->assertForbidden();
        $this->delete(route('consultants.destroy', $otro))->assertForbidden();
        $this->assertModelExists($otro);

        // Su propia ficha sí, pero el rol no cambia aunque lo mande
        $this->get(route('consultants.edit', $propio))->assertOk();
        $this->put(route('consultants.update', $propio), ['name' => 'Mi nombre', 'role' => 'admin'])->assertRedirect();
        $this->assertSame('Mi nombre', $digital->fresh()->name);
        $this->assertFalse($digital->fresh()->hasRole('admin'));

        $this->get(route('consultants.index'))->assertOk()->assertDontSee('+ Nuevo Miembro');

        $admin = $this->usuario('admin');
        $this->actingAs($admin)->delete(route('consultants.destroy', $otro))->assertRedirect();
        $this->assertModelMissing($otro);
    }

    public function test_academic_task_notifies_only_eca_with_schools_in_the_task(): void
    {
        $admin = $this->usuario('admin');
        $ecaConColegio = $this->usuario('consultor_eca');
        $ecaSinColegio = $this->usuario('consultor_eca');
        $elt = $this->usuario('consultor_elt');
        $coord = $this->usuario('coordinador');
        $school = School::create(['name' => 'Con ECA', 'status' => 'activo']);
        $this->asignar($ecaConColegio, $school, 'eca');
        Consultant::create(['user_id' => $ecaSinColegio->id]);
        $this->asignar($elt, $school, 'elt');

        $this->actingAs($admin)->post(route('tareas.store'), [
            'titulo' => 'Revisar planeaciones', 'descripcion' => 'x', 'audiencia' => 'eca',
        ])->assertRedirect();

        $notificados = UserNotification::pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($ecaConColegio->id, $notificados);
        $this->assertContains($coord->id, $notificados);
        $this->assertNotContains($ecaSinColegio->id, $notificados);
        $this->assertNotContains($elt->id, $notificados);
        $this->assertNotContains($admin->id, $notificados); // quien publica no se notifica
        $this->assertSame(1, TareaSI::count());
    }

    public function test_bundle_edit_button_escapes_text(): void
    {
        Bundle::create(['serie' => 'S1', 'name' => "Libro'); alert(1);//", 'grade' => "1'", 'level' => 'Primaria', 'role' => 'student', 'type' => 'ELT']);

        $html = $this->actingAs($this->usuario('admin'))->get(route('bundles.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString("Libro'); alert(1);//", $html);
        $this->assertStringContainsString('abrirEditar(', $html);
    }
}
