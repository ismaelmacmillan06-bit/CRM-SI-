<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoAccion;
use App\Models\SeguimientoMeta;
use App\Models\Ticket;
use App\Models\Visit;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            ->assertSee('Contacto Responsable')
            ->assertSee('Celeste May')
            ->assertSee('celeste.may.contractor@macmillaneducation.com')
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
            ->assertSessionHasErrors('fecha_aplicacion');

        $this->assertSame(0, SeguimientoMeta::count());

        // Al volver, la página Gestionar muestra el error y conserva lo que se había escrito
        $this->from(route('seguimiento.colegio', ['meta', $school]))
            ->followingRedirects()
            ->post(route('seguimiento.guardar', ['meta', $school]), $this->datos(['fecha_aplicacion' => 'no-es-fecha']))
            ->assertOk()
            ->assertSee('Gestionar Meta')
            ->assertSee('value="admin.meta@colegio.com"', false);
    }

    public function test_invalid_time_is_rejected(): void
    {
        $school = $this->colegio('Colegio Meta');

        $this->actingAs($this->usuario('admin'))
            ->post(route('seguimiento.guardar', ['meta', $school]), $this->datos(['hora_capacitacion' => '25:99']))
            ->assertSessionHasErrors('hora_capacitacion');
    }

    public function test_manage_page_saves_data_and_startup_actions_together(): void
    {
        $school = $this->colegio('Colegio arranque', true, 'activo', 'NX-9');
        $admin = $this->usuario('admin');
        $this->actingAs($admin);

        $this->get(route('seguimiento.show', 'meta'))
            ->assertSee(route('seguimiento.colegio', ['meta', $school]), false)
            ->assertSee('Gestionar')
            ->assertSee('0/5')
            ->assertSee('→ Compartir información a Edgar');

        $this->get(route('seguimiento.colegio', ['meta', $school]))
            ->assertOk()
            ->assertSee('Gestionar Meta — Colegio arranque')
            ->assertSee('Acciones de arranque')
            ->assertSee('Carga de alumnos en META')
            ->assertSee(route('schools.tickets.index', $school), false);

        $this->post(route('seguimiento.guardar', ['meta', $school]), $this->datos([
            'acciones' => [
                'compartir-edgar' => ['status' => 'done', 'notes' => 'Enviado por correo'],
                'capacitacion'    => ['status' => 'in_progress', 'notes' => null],
                'carga-alumnos'   => ['status' => 'pending', 'notes' => null],
            ],
        ]))->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Laura Pérez', SeguimientoMeta::first()->administrador);
        $this->assertSame(2, SeguimientoAccion::where('school_id', $school->id)->where('plataforma', 'meta')->count());
        $hecha = SeguimientoAccion::where('accion', 'compartir-edgar')->first();
        $this->assertSame('done', $hecha->status);
        $this->assertSame($admin->id, $hecha->completed_by);

        $this->get(route('seguimiento.show', 'meta'))->assertSee('1/5')->assertSee('→ Capacitación');
        // El contador de la página Gestionar también avanza
        $this->get(route('seguimiento.colegio', ['meta', $school]))->assertSee('1/5 completadas');

        // Completar todo marca el arranque como completo
        $todas = collect(\App\Http\Controllers\SeguimientoController::PLATAFORMAS['meta']['acciones'])
            ->map(fn () => ['status' => 'done', 'notes' => null])->all();
        $this->post(route('seguimiento.guardar', ['meta', $school]), $this->datos(['acciones' => $todas]));
        $this->get(route('seguimiento.show', 'meta'))->assertSee('5/5')->assertSee('✅ Completo');
    }

    public function test_invalid_action_status_is_rejected(): void
    {
        $school = $this->colegio('Colegio Meta');

        $this->actingAs($this->usuario('admin'))
            ->post(route('seguimiento.guardar', ['meta', $school]), $this->datos([
                'acciones' => ['capacitacion' => ['status' => 'inventado']],
            ]))
            ->assertSessionHasErrors('acciones.capacitacion.status');

        $this->assertSame(0, SeguimientoMeta::count());
        $this->assertSame(0, SeguimientoAccion::count());
    }

    private function actividad(School $school, array $productos, string $ticketStatus = 'open', string $sesionStatus = 'pendiente'): void
    {
        $consultor = Consultant::firstOrCreate(['user_id' => User::factory()->create()->id]);
        Ticket::create(['school_id' => $school->id, 'consultant_id' => $consultor->id, 'title' => 'Ticket ' . implode('+', $productos), 'description' => 'x',
            'status' => $ticketStatus, 'priority' => 'medium', 'medium' => 'whatsapp', 'productos' => $productos]);
        Visit::create(['school_id' => $school->id, 'consultant_id' => $consultor->id, 'status' => $sesionStatus, 'scheduled_date' => today(),
            'modalidad' => 'virtual', 'tipo' => 'programada', 'prioridad' => 'media', 'productos' => $productos]);
    }

    public function test_cards_count_only_tickets_and_sessions_with_meta_product(): void
    {
        $school = $this->colegio('Colegio con actividad');
        $sinMeta = $this->colegio('Colegio sin Meta', false);
        $this->actividad($school, ['Meta'], 'closed', 'terminada');
        $this->actividad($school, ['MEE', 'Meta']);
        $this->actividad($school, ['MEE']);              // otro producto: no cuenta
        $this->actividad($sinMeta, ['Meta']);            // colegio fuera de la agenda: no cuenta

        $this->actingAs($this->usuario('admin'))
            ->get(route('seguimiento.show', 'meta'))
            ->assertOk()
            ->assertSee('Tickets Meta')
            ->assertSee('1 abiertos · 1 cerrados')
            ->assertSee('Sesiones Meta')
            ->assertSee('1 realizadas · 1 pendientes')
            ->assertSee(route('seguimiento.excel', 'meta'), false);
    }

    public function test_excel_download_contains_schools_actions_tickets_and_sessions(): void
    {
        $school = $this->colegio('Colegio Excel', true, 'activo', 'NX-77');
        $this->actividad($school, ['Meta'], 'closed', 'terminada');
        $this->actividad($school, ['MEE']);
        $this->actingAs($this->usuario('admin'));
        $this->post(route('seguimiento.guardar', ['meta', $school]), $this->datos([
            'acciones' => ['compartir-edgar' => ['status' => 'done', 'notes' => null]],
        ]));

        $res = $this->get(route('seguimiento.excel', 'meta'))->assertOk();
        $this->assertStringContainsString('Seguimiento_Meta_', $res->headers->get('content-disposition'));

        $libro = IOFactory::load($res->getFile()->getPathname());
        $this->assertSame(['Resumen', 'Colegios', 'Acciones de arranque', 'Tickets', 'Sesiones'], $libro->getSheetNames());

        $colegios = $libro->getSheetByName('Colegios');
        $this->assertSame('NX-77', $colegios->getCell('A5')->getValue());
        $this->assertSame('Colegio Excel', $colegios->getCell('B5')->getValue());
        $this->assertSame('1/5 (20%)', $colegios->getCell('F5')->getValue());
        $this->assertSame('Laura Pérez', $colegios->getCell('H5')->getValue());

        $this->assertSame('Completado', $libro->getSheetByName('Acciones de arranque')->getCell('C5')->getValue());
        $this->assertSame('Pendiente', $libro->getSheetByName('Acciones de arranque')->getCell('D5')->getValue());

        $tickets = $libro->getSheetByName('Tickets');
        $this->assertSame('Ticket Meta', $tickets->getCell('C5')->getValue());
        $this->assertNull($tickets->getCell('C6')->getValue()); // el de MEE no sale
        $this->assertSame('Terminada', $libro->getSheetByName('Sesiones')->getCell('E5')->getValue());
    }

    public function test_excel_respects_digital_consultant_scope(): void
    {
        $user = $this->usuario('consultor_digital');
        $consultant = Consultant::create(['user_id' => $user->id]);
        $propio = $this->colegio('Colegio propio');
        $this->colegio('Colegio ajeno');
        SchoolConsultant::create(['school_id' => $propio->id, 'consultant_id' => $consultant->id, 'role' => 'digital']);

        $res = $this->actingAs($user)->get(route('seguimiento.excel', 'meta'))->assertOk();
        $hoja = IOFactory::load($res->getFile()->getPathname())->getSheetByName('Colegios');
        // Meta lo consultan todos los digitales: sus colegios primero y luego los demás
        $this->assertSame('Colegio propio', $hoja->getCell('B5')->getValue());
        $this->assertSame('Colegio ajeno', $hoja->getCell('B6')->getValue());

        $this->actingAs($this->usuario('admin'))->get('/seguimiento/inexistente/excel')->assertNotFound();
    }

    public function test_school_without_meta_cannot_be_edited(): void
    {
        $school = $this->colegio('Sin Meta', false);

        $this->actingAs($this->usuario('admin'))
            ->post(route('seguimiento.guardar', ['meta', $school]), $this->datos())
            ->assertNotFound();
        $this->get(route('seguimiento.colegio', ['meta', $school]))->assertNotFound();
    }

    public function test_digital_consultant_only_edits_assigned_schools(): void
    {
        $user = $this->usuario('consultor_digital');
        $consultant = Consultant::create(['user_id' => $user->id]);
        $propio = $this->colegio('Colegio propio');
        $ajeno = $this->colegio('Colegio ajeno');
        SchoolConsultant::create(['school_id' => $propio->id, 'consultant_id' => $consultant->id, 'role' => 'digital']);

        // Ve todos los colegios Meta, pero en los ajenos solo consulta
        $this->actingAs($user)->get(route('seguimiento.show', 'meta'))
            ->assertSee('Colegio propio')
            ->assertSee('Colegio ajeno')
            ->assertSee('Mis colegios');

        $this->get(route('seguimiento.colegio', ['meta', $propio]))->assertOk()->assertSee('Guardar');
        $this->get(route('seguimiento.colegio', ['meta', $ajeno]))->assertOk()->assertDontSee(route('schools.tickets.index', $ajeno));
        $this->post(route('seguimiento.guardar', ['meta', $propio]), $this->datos())->assertRedirect();
        $this->post(route('seguimiento.guardar', ['meta', $ajeno]), $this->datos())->assertForbidden();
        $this->assertSame([$propio->id], SeguimientoMeta::pluck('school_id')->all());
    }

    public function test_read_only_roles_can_view_and_download_but_not_save(): void
    {
        $school = $this->colegio('Colegio consulta');

        foreach (['consultor_eca', 'consultor_elt', 'coordinador', 'representante_ventas'] as $rol) {
            $this->actingAs($this->usuario($rol));
            $this->get(route('seguimiento.show', 'meta'))
                ->assertOk()
                ->assertSee('Colegio consulta')
                ->assertSee('Ver →')
                ->assertDontSee('Gestionar →');
            $this->get(route('seguimiento.colegio', ['meta', $school]))
                ->assertOk()
                ->assertSee('Consultar Meta')
                ->assertSee('<fieldset disabled', false)
                ->assertDontSee('Guardar cambios');
            $this->get(route('seguimiento.excel', 'meta'))->assertOk();
            $this->post(route('seguimiento.guardar', ['meta', $school]), $this->datos())->assertRedirect();
        }

        $this->assertSame(0, SeguimientoMeta::count());
    }
}
