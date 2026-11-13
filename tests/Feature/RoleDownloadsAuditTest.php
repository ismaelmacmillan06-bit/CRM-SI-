<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolServiceType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleDownloadsAuditTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin']));
        $consultant = Consultant::create(['user_id' => $admin->id]);

        $this->school = School::create([
            'name'          => 'Colegio Auditoria',
            'nexus_id'      => 'NX-555',
            'status'        => 'activo',
            'consultant_id' => $consultant->id,
        ]);

        $this->school->students()->create([
            'name'         => 'Lucía',
            'last_name'    => 'Méndez',
            'mee_username' => 'lucia@audit.com',
            'mee_password' => 'pass1',
            'level'        => 'Primaria',
            'grade'        => 'azul',
        ]);

        $this->ticket = Ticket::create([
            'school_id'     => $this->school->id,
            'consultant_id' => $consultant->id,
            'title'         => 'Ticket Auditoría',
            'description'   => 'Detalle auditoría',
            'medium'        => 'whatsapp',
            'productos'     => ['MEE'],
            'status'        => 'open',
            'priority'      => 'medium',
        ]);

        $ge = SchoolServiceType::firstOrCreate(
            ['plataforma' => 'global-educa'],
            ['name' => 'Global Educa', 'icon' => '🌐', 'color' => '#2563eb']
        );
        $meta = SchoolServiceType::firstOrCreate(
            ['plataforma' => 'meta'],
            ['name' => 'Meta', 'icon' => '🎯', 'color' => '#dc2626']
        );
        $this->school->services()->attach([$ge->id, $meta->id]);
    }

    public function test_all_read_only_roles_can_access_new_downloads_and_views(): void
    {
        $roles = ['coordinador', 'consultor_eca', 'consultor_elt', 'representante_ventas'];

        foreach ($roles as $roleName) {
            $user = User::factory()->create();
            $user->assignRole(Role::firstOrCreate(['name' => $roleName]));
            $this->actingAs($user);

            // 1. Ver detalle de ticket
            $this->get(route('tickets.show', $this->ticket))
                ->assertOk()
                ->assertSee('Ticket Auditoría');

            // 2. Reporte PDF de tickets
            $this->get(route('schools.tickets.report.pdf', $this->school))
                ->assertOk()
                ->assertSee('Colegio Auditoria');

            // 3. Reporte Excel de tickets
            $this->get(route('schools.tickets.report.excel', $this->school))
                ->assertOk();

            // 4. Descarga de alumnos por clase (POST)
            $this->post(route('schools.students.export-by-class', $this->school), [
                'clases' => [base64_encode(json_encode(['Primaria', 'azul']))],
                'modo'   => 'hojas',
            ])->assertOk();

            // 5. Seguimiento Global Educa incluye botón Formato entrega
            $this->get(route('seguimiento.show', 'global-educa'))
                ->assertOk()
                ->assertSee('Formato entrega');

            // 6. Seguimiento Meta incluye enlace a alumnos del colegio
            $this->get(route('seguimiento.show', 'meta'))
                ->assertOk()
                ->assertSee(route('schools.students.index', $this->school), false);
        }
    }
}
