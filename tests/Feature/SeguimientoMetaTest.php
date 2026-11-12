<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoMeta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguimientoMetaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));

        return $user;
    }

    private function colegio(string $nombre, bool $meta = true, string $status = 'activo', ?string $nexus = null): School
    {
        $school = School::create(['name' => $nombre, 'status' => $status, 'nexus_id' => $nexus]);
        if ($meta) {
            $school->services()->attach(SchoolServiceType::where('plataforma', 'meta')->value('id'));
        }

        return $school;
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'administrador'      => 'Laura Pérez',
            'usuario'            => 'admin.meta@colegio.com',
            'contrasena'         => 'Meta.2026',
            'capacitacion'       => 'Capacitación docentes',
            'fecha_capacitacion' => today()->addDays(5)->format('Y-m-d'),
            'hora_capacitacion'  => '10:30',
            'fecha_aplicacion'   => today()->addDays(60)->format('Y-m-d'),
            'notas'              => 'Confirmar sala de cómputo',
        ], $extra);
    }

    public function test_agenda_lists_only_active_schools_with_meta(): void
    {
        $this->colegio('Con Meta', true, 'activo', 'NX-123');
        $this->colegio('Sin Meta', false);
        $this->colegio('Inactivo con Meta', true, 'inactivo');

        $this->actingAs($this->usuario('admin'))
            ->get(route('seguimiento.show', 'meta'))
            ->assertOk()
            ->assertSee('Agenda de exámenes Meta')
            ->assertSee('Con Meta')
            ->assertSee('NX-123')
            ->assertDontSee('Sin Meta')
            ->assertDontSee('Inactivo con Meta');
    }

    public function test_saving_fills_the_row_and_logs_activity(): void
    {
        $school = $this->colegio('Colegio Meta');
        $admin = $this->usuario('admin');

        $this->actingAs($admin)
            ->post(route('seguimiento.guardar', ['meta', $school]), $this->datos())
            ->assertRedirect()
            ->assertSessionHas('success');

        $meta = SeguimientoMeta::where('school_id', $school->id)->first();
        $this->assertSame('Laura Pérez', $meta->administrador);
        $this->assertSame('Meta.2026', $meta->contrasena);
        $this->assertSame($admin->id, $meta->updated_by);
        $this->assertTrue(ActivityLog::where('school_id', $school->id)->where('descripcion', 'like', 'Meta:%')->exists());

        $this->get(route('seguimiento.show', 'meta'))
            ->assertSee('Laura Pérez')
            ->assertSee('Capacitación docentes')
            ->assertSee('En 5 días')
            ->assertSee('10:30 h')
            ->assertSee('"hora":"10:30"', false)
            ->assertSee('"tipo":"apl"', false);
        $this->assertSame('10:30', SeguimientoMeta::first()->horaCorta());

        // Volver a guardar lo mismo no duplica el renglón ni registra otra actividad
        $this->post(route('seguimiento.guardar', ['meta', $school]), $this->datos());
        $this->assertSame(1, SeguimientoMeta::count());
        $this->assertSame(1, ActivityLog::where('descripcion', 'like', 'Meta:%')->count());
    }

    public function test_invalid_date_is_rejected(): void
    {
        $school = $this->colegio('Colegio Meta');

        $this->actingAs($this->usuario('admin'))
            ->post(route('seguimiento.guardar', ['meta', $school]), $this->datos(['fecha_aplicacion' => 'no-es-fecha']))
            ->assertSessionHasErrors('fecha_aplicacion', null, 'meta_' . $school->id);

        $this->assertSame(0, SeguimientoMeta::count());

        // Al volver, la página muestra el error y reabre el modal con lo que se había escrito
        $this->from(route('seguimiento.show', 'meta'))
            ->followingRedirects()
            ->post(route('seguimiento.guardar', ['meta', $school]), $this->datos(['fecha_aplicacion' => 'no-es-fecha']))
            ->assertOk()
            ->assertSee('mt-err"', false)
            ->assertSee('data-colegio="' . $school->id . '"', false)
            ->assertSee('"usuario":"admin.meta@colegio.com"', false);
    }

    public function test_invalid_time_is_rejected(): void
    {
        $school = $this->colegio('Colegio Meta');

        $this->actingAs($this->usuario('admin'))
            ->post(route('seguimiento.guardar', ['meta', $school]), $this->datos(['hora_capacitacion' => '25:99']))
            ->assertSessionHasErrors('hora_capacitacion', null, 'meta_' . $school->id);
    }

    public function test_school_without_meta_cannot_be_edited(): void
    {
        $school = $this->colegio('Sin Meta', false);

        $this->actingAs($this->usuario('admin'))
            ->post(route('seguimiento.guardar', ['meta', $school]), $this->datos())
            ->assertNotFound();
    }

    public function test_digital_consultant_only_edits_assigned_schools(): void
    {
        $user = $this->usuario('consultor_digital');
        $consultant = Consultant::create(['user_id' => $user->id]);
        $propio = $this->colegio('Colegio propio');
        $ajeno = $this->colegio('Colegio ajeno');
        SchoolConsultant::create(['school_id' => $propio->id, 'consultant_id' => $consultant->id, 'role' => 'digital']);

        $this->actingAs($user)->get(route('seguimiento.show', 'meta'))
            ->assertSee('Colegio propio')
            ->assertDontSee('Colegio ajeno');

        $this->post(route('seguimiento.guardar', ['meta', $propio]), $this->datos())->assertRedirect();
        $this->post(route('seguimiento.guardar', ['meta', $ajeno]), $this->datos())->assertForbidden();
        $this->assertSame([$propio->id], SeguimientoMeta::pluck('school_id')->all());
    }

    public function test_other_roles_cannot_enter(): void
    {
        $school = $this->colegio('Colegio');

        foreach (['consultor_eca', 'consultor_elt', 'coordinador', 'representante_ventas'] as $rol) {
            $this->actingAs($this->usuario($rol));
            $this->get(route('seguimiento.show', 'meta'))->assertRedirect();
            $this->post(route('seguimiento.guardar', ['meta', $school]), $this->datos())->assertRedirect();
        }

        $this->assertSame(0, SeguimientoMeta::count());
    }
}
