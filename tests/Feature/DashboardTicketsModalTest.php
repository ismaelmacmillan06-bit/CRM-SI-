<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTicketsModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_tickets_in_dashboard_modal_with_go_links(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin']));

        $consultantUser = User::factory()->create(['name' => 'Consultor Uno']);
        $consultant = Consultant::create(['user_id' => $consultantUser->id]);

        $schoolA = School::create(['name' => 'Colegio Alfa', 'status' => 'activo', 'consultant_id' => $consultant->id]);
        $schoolB = School::create(['name' => 'Colegio Beta', 'status' => 'activo', 'consultant_id' => $consultant->id]);

        $ticketA = Ticket::create([
            'school_id'     => $schoolA->id,
            'consultant_id' => $consultant->id,
            'title'         => 'Error acceso MEE',
            'description'   => 'No carga la plataforma',
            'status'        => 'open',
            'priority'      => 'high',
        ]);
        $ticketB = Ticket::create([
            'school_id'     => $schoolB->id,
            'consultant_id' => $consultant->id,
            'title'         => 'Cambio de contraseña',
            'description'   => 'Alumno nuevo',
            'status'        => 'closed',
            'priority'      => 'low',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('modal-tickets', false);
        $response->assertSee('Error acceso MEE');
        $response->assertSee('Cambio de contraseña');
        $response->assertSee(route('tickets.show', $ticketA->id), false);
        $response->assertSee(route('tickets.show', $ticketB->id), false);
    }

    public function test_consultor_digital_only_sees_tickets_from_assigned_schools(): void
    {
        $role = Role::firstOrCreate(['name' => 'consultor_digital']);

        $user1 = User::factory()->create(['name' => 'Consultor Asignado']);
        $user1->assignRole($role);
        $consultant1 = Consultant::create(['user_id' => $user1->id]);

        $user2 = User::factory()->create(['name' => 'Otro Consultor']);
        $user2->assignRole($role);
        $consultant2 = Consultant::create(['user_id' => $user2->id]);

        $schoolPropia = School::create(['name' => 'Colegio Propio', 'status' => 'activo', 'consultant_id' => $consultant1->id]);
        $schoolAjena  = School::create(['name' => 'Colegio Ajeno', 'status' => 'activo', 'consultant_id' => $consultant2->id]);

        SchoolConsultant::create([
            'school_id'     => $schoolPropia->id,
            'consultant_id' => $consultant1->id,
            'role'          => 'digital',
        ]);
        SchoolConsultant::create([
            'school_id'     => $schoolAjena->id,
            'consultant_id' => $consultant2->id,
            'role'          => 'digital',
        ]);

        $ticketPropio = Ticket::create([
            'school_id'     => $schoolPropia->id,
            'consultant_id' => $consultant1->id,
            'title'         => 'Ticket de mi colegio',
            'description'   => 'Detalle propio',
            'status'        => 'open',
            'priority'      => 'medium',
        ]);
        $ticketAjeno = Ticket::create([
            'school_id'     => $schoolAjena->id,
            'consultant_id' => $consultant2->id,
            'title'         => 'Ticket de otro colegio',
            'description'   => 'Detalle ajeno',
            'status'        => 'open',
            'priority'      => 'medium',
        ]);

        $response = $this->actingAs($user1)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Ticket de mi colegio');
        $response->assertSee(route('tickets.show', $ticketPropio->id), false);
        $response->assertDontSee('Ticket de otro colegio');
        $response->assertDontSee(route('tickets.show', $ticketAjeno->id), false);
    }

    public function test_ticket_show_page_and_reports_work(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin']));
        $consultant = Consultant::create(['user_id' => $admin->id]);

        $school = School::create([
            'name'          => 'Colegio Reporte',
            'nexus_id'      => 'NX-9021',
            'status'        => 'activo',
            'consultant_id' => $consultant->id,
        ]);

        $ticket = Ticket::create([
            'school_id'     => $school->id,
            'consultant_id' => $consultant->id,
            'title'         => 'Soporte proyector',
            'description'   => 'No enciende en salón 3B',
            'medium'        => 'salesforce',
            'productos'     => ['MEE', 'Equipamiento'],
            'status'        => 'open',
            'priority'      => 'high',
        ]);

        // Index muestra botón Ver y Generar Reporte
        $indexRes = $this->actingAs($admin)->get(route('schools.tickets.index', $school));
        $indexRes->assertOk();
        $indexRes->assertSee(route('tickets.show', $ticket), false);
        $indexRes->assertSee('Generar Reporte');

        // Vista de solo lectura (show)
        $showRes = $this->actingAs($admin)->get(route('tickets.show', $ticket));
        $showRes->assertOk();
        $showRes->assertSee('Soporte proyector');
        $showRes->assertSee('No enciende en salón 3B');
        $showRes->assertSee('NX-9021');

        // Reporte PDF visual con gráficos SVG y logo
        $pdfRes = $this->actingAs($admin)->get(route('schools.tickets.report.pdf', $school));
        $pdfRes->assertOk();
        $pdfRes->assertSee('Colegio Reporte');
        $pdfRes->assertSee('NX-9021');
        $pdfRes->assertSee('Soporte proyector');
        $pdfRes->assertSee('logo-si-pdf.png', false);
        $pdfRes->assertSee('<svg', false);

        // Reporte Excel con gráficos y logo
        $excelRes = $this->actingAs($admin)->get(route('schools.tickets.report.excel', $school));
        $excelRes->assertOk();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelRes->getFile()->getPathname());
        $this->assertSame(['Resumen y Gráficos', 'Detalle de Tickets'], $spreadsheet->getSheetNames());
        $resumenSheet = $spreadsheet->getSheetByName('Resumen y Gráficos');
        $detalleSheet = $spreadsheet->getSheetByName('Detalle de Tickets');
        $this->assertStringContainsString('NX-9021', (string) $resumenSheet->getCell('A2')->getValue());
        $this->assertCount(1, $resumenSheet->getDrawingCollection());
        $this->assertCount(1, $detalleSheet->getDrawingCollection());
        $this->assertSame('Soporte proyector', $detalleSheet->getCell('B4')->getValue());

        // Filtro por periodo de fechas (excluye ticket antiguo)
        $oldTicket = Ticket::create([
            'school_id'     => $school->id,
            'consultant_id' => $consultant->id,
            'title'         => 'Ticket del mes pasado',
            'description'   => 'Fuera de rango',
            'medium'        => 'whatsapp',
            'productos'     => ['MEE'],
            'status'        => 'closed',
            'priority'      => 'low',
        ]);
        $oldTicket->created_at = now()->subDays(40);
        $oldTicket->save();

        $hoy = now()->format('Y-m-d');
        $pdfRango = $this->actingAs($admin)->get(route('schools.tickets.report.pdf', [
            'school' => $school,
            'desde'  => $hoy,
            'hasta'  => $hoy,
        ]));
        $pdfRango->assertOk();
        $pdfRango->assertSee('Soporte proyector');
        $pdfRango->assertDontSee('Ticket del mes pasado');
    }
}
