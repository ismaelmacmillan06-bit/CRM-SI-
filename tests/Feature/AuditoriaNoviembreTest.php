<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SeguimientoRecurso;
use App\Models\SeguimientoRecursoAudiencia;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditoriaNoviembreTest extends TestCase
{
    use RefreshDatabase;

    private const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));

        return $user;
    }

    private function ticket(School $school, string $titulo): Ticket
    {
        $consultant = Consultant::create(['user_id' => User::factory()->create()->id]);

        return Ticket::create([
            'school_id' => $school->id, 'consultant_id' => $consultant->id, 'title' => $titulo,
            'description' => '=SUMA(A1:A3)', 'priority' => 'medium', 'status' => 'open',
            'medium' => 'whatsapp', 'productos' => ['Meta'],
        ]);
    }

    public function test_academic_and_coordinator_roles_download_dashboard_reports_as_real_excel(): void
    {
        School::create(['name' => 'Colegio Uno', 'status' => 'activo']);

        $rutas = [
            'consultor_eca' => ['reportes.general', 'reportes.zonas', 'dashboard.acciones-arranque.excel'],
            'consultor_elt' => ['reportes.general', 'reportes.zonas', 'dashboard.acciones-arranque.excel'],
            'coordinador'   => ['reportes.general', 'reportes.zonas', 'dashboard.acciones-arranque.excel'],
        ];

        foreach ($rutas as $rol => $nombres) {
            $this->actingAs($this->usuario($rol));
            foreach ($nombres as $ruta) {
                $res = $this->get(route($ruta));
                $res->assertOk();
                $this->assertStringContainsString('spreadsheetml', $res->headers->get('Content-Type'), "$rol → $ruta");
            }
        }
    }

    public function test_text_starting_with_equals_does_not_break_excel_reports(): void
    {
        $school = School::create(['name' => 'Colegio Fórmulas', 'status' => 'activo']);
        $this->ticket($school, '=Revisar acceso MEE (urgente)');

        $res = $this->actingAs($this->usuario('admin'))->get(route('schools.tickets.report.excel', $school));
        $res->assertOk();

        $libro = IOFactory::load($res->baseResponse->getFile()->getPathname());
        $detalle = $libro->getSheetByName('Detalle de Tickets');
        $this->assertSame('=Revisar acceso MEE (urgente)', $detalle->getCell('B4')->getValue());
        $this->assertSame('s', $detalle->getCell('B4')->getDataType());
        $this->assertSame('=SUMA(A1:A3)', $detalle->getCell('C4')->getValue());
    }

    public function test_digital_consultant_cannot_open_tickets_of_other_schools(): void
    {
        $user = $this->usuario('consultor_digital');
        $consultant = Consultant::create(['user_id' => $user->id]);
        $propio = School::create(['name' => 'Propio', 'status' => 'activo']);
        $ajeno = School::create(['name' => 'Ajeno', 'status' => 'activo']);
        SchoolConsultant::create(['school_id' => $propio->id, 'consultant_id' => $consultant->id, 'role' => 'digital']);

        $this->actingAs($user);
        $this->get(route('tickets.show', $this->ticket($propio, 'Mío')))->assertOk();
        $this->get(route('tickets.show', $this->ticket($ajeno, 'Ajeno')))->assertForbidden();
    }

    public function test_txt_resource_preview_is_served_as_plain_text(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('seguimiento-recursos/meta/manuales/x.txt', '<script>alert(1)</script>');
        $admin = $this->usuario('admin');
        $recurso = SeguimientoRecurso::create([
            'plataforma' => 'meta', 'categoria' => 'manuales', 'titulo' => 'Notas', 'archivo' => 'seguimiento-recursos/meta/manuales/x.txt',
            'archivo_nombre' => 'x.txt', 'extension' => 'txt', 'mime_type' => 'text/html', 'tamano' => 30, 'user_id' => $admin->id,
        ]);
        SeguimientoRecursoAudiencia::create(['seguimiento_recurso_id' => $recurso->id, 'rol' => 'todos']);

        $res = $this->actingAs($this->usuario('consultor_eca'))->get(route('seguimiento.recursos.preview', $recurso));
        $res->assertOk();
        $this->assertStringStartsWith('text/plain', $res->headers->get('Content-Type'));
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
    }
}
