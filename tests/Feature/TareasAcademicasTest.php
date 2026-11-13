<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\TareaColegio;
use App\Models\TareaSI;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TareasAcademicasTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));

        return $user;
    }

    private function asignar(User $user, School $school, string $rol): void
    {
        $consultant = Consultant::firstOrCreate(['user_id' => $user->id]);
        SchoolConsultant::create(['school_id' => $school->id, 'consultant_id' => $consultant->id, 'role' => $rol]);
    }

    public function test_coordinator_publishes_task_only_for_schools_with_eca_consultant(): void
    {
        $eca = $this->usuario('consultor_eca');
        $conEca = School::create(['name' => 'Colegio Con ECA', 'status' => 'activo']);
        $inactivo = School::create(['name' => 'Colegio Inactivo', 'status' => 'inactivo']);
        School::create(['name' => 'Colegio Sin ECA', 'status' => 'activo']);
        $this->asignar($eca, $conEca, 'eca');
        $this->asignar($eca, $inactivo, 'eca');

        $coordinador = $this->usuario('coordinador');
        $this->actingAs($coordinador)
            ->get(route('tareas.index'))
            ->assertOk()
            ->assertSee('+ Tarea SI')
            ->assertSee(route('tareas.index'), false);

        $this->post(route('tareas.store'), ['titulo' => 'Revisar planeaciones', 'audiencia' => 'eca'])
            ->assertRedirect();

        $tarea = TareaSI::firstOrFail();
        $this->assertSame('eca', $tarea->audiencia);
        $this->assertSame([$conEca->id], TareaColegio::where('tarea_si_id', $tarea->id)->pluck('school_id')->all());
    }

    public function test_coordinator_cannot_publish_digital_tasks_or_touch_them(): void
    {
        $coordinador = $this->usuario('coordinador');
        $this->actingAs($coordinador)
            ->post(route('tareas.store'), ['titulo' => 'Para digitales', 'audiencia' => 'digital'])
            ->assertSessionHasErrors('audiencia');

        $digital = TareaSI::create(['titulo' => 'Tarea digital', 'audiencia' => 'digital', 'created_by' => $coordinador->id]);
        $this->put(route('tareas.update', $digital), ['titulo' => 'Cambio'])->assertForbidden();
        $this->delete(route('tareas.destroy', $digital))->assertForbidden();
        $this->assertDatabaseHas('tareas_si', ['id' => $digital->id, 'titulo' => 'Tarea digital']);
    }

    public function test_coordinator_can_edit_and_delete_academic_tasks(): void
    {
        $coordinador = $this->usuario('coordinador');
        $tarea = TareaSI::create(['titulo' => 'Original', 'audiencia' => 'academico', 'created_by' => $coordinador->id]);

        $this->actingAs($coordinador)->put(route('tareas.update', $tarea), ['titulo' => 'Editada'])->assertRedirect();
        $this->assertSame('Editada', $tarea->fresh()->titulo);

        $this->delete(route('tareas.destroy', $tarea))->assertRedirect();
        $this->assertDatabaseMissing('tareas_si', ['id' => $tarea->id]);
    }

    public function test_eca_consultant_sees_own_task_and_marks_only_own_schools(): void
    {
        $eca = $this->usuario('consultor_eca');
        $otroEca = $this->usuario('consultor_eca');
        $propio = School::create(['name' => 'Colegio Propio', 'status' => 'activo']);
        $ajeno = School::create(['name' => 'Colegio Ajeno', 'status' => 'activo']);
        $this->asignar($eca, $propio, 'eca');
        $this->asignar($otroEca, $ajeno, 'eca');

        $coordinador = $this->usuario('coordinador');
        $this->actingAs($coordinador)->post(route('tareas.store'), ['titulo' => 'Tarea ECA', 'audiencia' => 'academico']);
        $tarea = TareaSI::firstOrFail();
        TareaSI::create(['titulo' => 'Tarea solo digital', 'audiencia' => 'digital', 'created_by' => $coordinador->id]);

        $this->actingAs($eca)
            ->get(route('tareas.index', ['tarea_id' => $tarea->id]))
            ->assertOk()
            ->assertSee('Tarea ECA')
            ->assertDontSee('Tarea solo digital')
            ->assertSee('Colegio Propio')
            ->assertDontSee('Colegio Ajeno')
            ->assertDontSee('+ Tarea SI');

        $this->patchJson(route('tareas.update-status', [$tarea, $propio]), ['status' => 'realizada'])->assertOk();
        $this->patchJson(route('tareas.update-status', [$tarea, $ajeno]), ['status' => 'realizada'])->assertForbidden();

        $this->assertSame('realizada', TareaColegio::where('tarea_si_id', $tarea->id)->where('school_id', $propio->id)->value('status'));
        $this->assertSame('pendiente', TareaColegio::where('tarea_si_id', $tarea->id)->where('school_id', $ajeno->id)->value('status'));

        // ECA no puede publicar ni borrar
        $this->post(route('tareas.store'), ['titulo' => 'X', 'audiencia' => 'eca'])->assertRedirect();
        $this->delete(route('tareas.destroy', $tarea))->assertRedirect();
        $this->assertSame(2, TareaSI::count());
    }

    public function test_elt_does_not_see_eca_only_tasks(): void
    {
        $coordinador = $this->usuario('coordinador');
        TareaSI::create(['titulo' => 'Solo para ECA', 'audiencia' => 'eca', 'created_by' => $coordinador->id]);
        TareaSI::create(['titulo' => 'Para ELT', 'audiencia' => 'elt', 'created_by' => $coordinador->id]);

        $this->actingAs($this->usuario('consultor_elt'))
            ->get(route('tareas.index'))
            ->assertOk()
            ->assertSee('Para ELT')
            ->assertDontSee('Solo para ECA');
    }

    public function test_digital_consultant_cannot_mark_academic_tasks(): void
    {
        $digital = $this->usuario('consultor_digital');
        $school = School::create(['name' => 'Colegio', 'status' => 'activo']);
        $this->asignar($digital, $school, 'digital');
        $tarea = TareaSI::create(['titulo' => 'Académica', 'audiencia' => 'eca', 'created_by' => $digital->id]);
        TareaColegio::create(['tarea_si_id' => $tarea->id, 'school_id' => $school->id, 'status' => 'pendiente']);

        $this->actingAs($digital)
            ->patchJson(route('tareas.update-status', [$tarea, $school]), ['status' => 'realizada'])
            ->assertForbidden();
    }

    public function test_admin_still_publishes_digital_tasks_for_all_schools(): void
    {
        School::create(['name' => 'A', 'status' => 'activo']);
        School::create(['name' => 'B', 'status' => 'activo']);

        $this->actingAs($this->usuario('admin'))
            ->post(route('tareas.store'), ['titulo' => 'Bienvenida', 'audiencia' => 'digital'])
            ->assertRedirect();

        $this->assertSame(2, TareaColegio::count());
        $this->assertSame('digital', TareaSI::firstOrFail()->audiencia);
    }
}
