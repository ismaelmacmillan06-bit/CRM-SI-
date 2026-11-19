<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\Level;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoRecurso;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguimientoEquipamientoRedRooReadsTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));

        return $user;
    }

    private function colegioConPlataformas(string $nombre, array $plataformas, string $status = 'activo'): School
    {
        $school = School::create(['name' => $nombre, 'status' => $status, 'nexus_id' => 'SR-' . random_int(100, 999)]);
        $serviceIds = SchoolServiceType::whereIn('plataforma', $plataformas)->pluck('id')->all();
        if ($serviceIds !== []) {
            $school->services()->attach($serviceIds);
        }

        return $school;
    }

    private function crearActividad(School $school, array $productos, string $ticketStatus = 'open', string $sesionStatus = 'pendiente', string $label = ''): void
    {
        $consultor = Consultant::firstOrCreate(['user_id' => User::factory()->create()->id]);
        $tag = $label ?: implode('+', $productos);

        Ticket::create([
            'school_id'     => $school->id,
            'consultant_id' => $consultor->id,
            'title'         => 'Ticket ' . $tag,
            'description'   => 'Desc ' . $tag,
            'status'        => $ticketStatus,
            'priority'      => 'medium',
            'medium'        => 'whatsapp',
            'productos'     => $productos,
        ]);

        Visit::create([
            'school_id'      => $school->id,
            'consultant_id'  => $consultor->id,
            'status'         => $sesionStatus,
            'scheduled_date' => today(),
            'modalidad'      => 'virtual',
            'tipo'           => 'programada',
            'prioridad'      => 'media',
            'motivo'         => 'Sesión ' . $tag,
            'productos'      => $productos,
        ]);
    }

    public function test_schools_appear_only_in_the_panels_for_their_assigned_services(): void
    {
        $soloEquip = $this->colegioConPlataformas('Colegio Solo Equipamiento', ['equipamiento']);
        $soloRedRoo = $this->colegioConPlataformas('Colegio Solo Red Roo', ['red-roo-reads']);
        $cincoProductos = $this->colegioConPlataformas('Colegio Cinco Servicios', ['meta', 'global-educa', 'frances', 'red-roo-reads', 'equipamiento']);
        $inactivo = $this->colegioConPlataformas('Colegio Inactivo EQ', ['equipamiento', 'red-roo-reads'], 'inactivo');

        $this->actingAs($this->usuario('admin'));

        $this->get(route('seguimiento.show', 'equipamiento'))
            ->assertOk()
            ->assertSee('Colegio Solo Equipamiento')
            ->assertSee('Colegio Cinco Servicios')
            ->assertDontSee('Colegio Solo Red Roo')
            ->assertDontSee('Colegio Inactivo EQ');

        $this->get(route('seguimiento.show', 'red-roo-reads'))
            ->assertOk()
            ->assertSee('Colegio Solo Red Roo')
            ->assertSee('Colegio Cinco Servicios')
            ->assertDontSee('Colegio Solo Equipamiento')
            ->assertDontSee('Colegio Inactivo EQ');

        foreach (['meta', 'global-educa', 'frances', 'red-roo-reads', 'equipamiento'] as $plat) {
            $this->get(route('seguimiento.show', $plat))
                ->assertOk()
                ->assertSee('Colegio Cinco Servicios');
        }

        // Consultor digital solo ve sus colegios asignados
        $digitalUser = $this->usuario('consultor_digital');
        $digitalConsultant = Consultant::create(['user_id' => $digitalUser->id]);
        SchoolConsultant::create(['school_id' => $soloEquip->id, 'consultant_id' => $digitalConsultant->id, 'role' => 'digital']);

        $this->actingAs($digitalUser)
            ->get(route('seguimiento.show', 'equipamiento'))
            ->assertOk()
            ->assertSee('Colegio Solo Equipamiento')
            ->assertDontSee('Colegio Cinco Servicios');

        // Sin formato de entrega ni acciones individuales activas todavía
        $this->actingAs($this->usuario('admin'));
        foreach (['equipamiento' => $soloEquip, 'red-roo-reads' => $soloRedRoo] as $slug => $school) {
            $this->get(route('seguimiento.show', $slug))
                ->assertOk()
                ->assertDontSee('Formato entrega')
                ->assertDontSee('data-formato-entrega', false)
                ->assertDontSee('Implementación completa');
            $this->get(route('seguimiento.colegio', [$slug, $school]))->assertNotFound();
        }
    }

    public function test_tickets_sessions_and_excel_work_for_equipamiento_and_red_roo_reads(): void
    {
        $eqSchool = $this->colegioConPlataformas('Colegio EQ Activo', ['equipamiento']);
        $eqSchool->levels()->attach(Level::firstOrCreate(['name' => 'Primaria'])->id);
        $rrrSchool = $this->colegioConPlataformas('Colegio RRR Activo', ['red-roo-reads']);
        $otroSchool = $this->colegioConPlataformas('Colegio Sin EQ', ['global-educa']);

        $this->crearActividad($eqSchool, ['Equipamiento'], 'closed', 'terminada', 'SoloEQ');
        $this->crearActividad($eqSchool, ['MEE', 'Equipamiento'], 'open', 'pendiente', 'MixtoEQ');
        $this->crearActividad($eqSchool, ['Meta'], 'open', 'pendiente', 'OtroProdEQ');
        $this->crearActividad($otroSchool, ['Equipamiento'], 'open', 'pendiente', 'AjenoEQ');

        $this->crearActividad($rrrSchool, ['Red Roo Reads'], 'closed', 'terminada', 'SoloRRR');
        $this->crearActividad($rrrSchool, ['Global Educa', 'Red Roo Reads'], 'open', 'pendiente', 'MixtoRRR');
        $this->crearActividad($otroSchool, ['Red Roo Reads'], 'open', 'pendiente', 'AjenoRRR');

        $this->actingAs($this->usuario('admin'));

        $this->get(route('seguimiento.show', 'equipamiento'))
            ->assertOk()
            ->assertSee('Tickets Equipamiento')
            ->assertSee('1 abiertos · 1 cerrados')
            ->assertSee('Sesiones Equipamiento')
            ->assertSee('1 realizadas · 1 pendientes')
            ->assertSee('Ticket SoloEQ')
            ->assertSee('Ticket MixtoEQ')
            ->assertSee('Sesión SoloEQ')
            ->assertSee('Sesión MixtoEQ')
            ->assertDontSee('Ticket OtroProdEQ')
            ->assertDontSee('Ticket AjenoEQ');

        $this->get(route('seguimiento.show', 'red-roo-reads'))
            ->assertOk()
            ->assertSee('Tickets Red Roo Reads')
            ->assertSee('1 abiertos · 1 cerrados')
            ->assertSee('Sesiones Red Roo Reads')
            ->assertSee('1 realizadas · 1 pendientes')
            ->assertSee('Ticket SoloRRR')
            ->assertSee('Ticket MixtoRRR')
            ->assertSee('Sesión SoloRRR')
            ->assertSee('Sesión MixtoRRR')
            ->assertDontSee('Ticket AjenoRRR');

        foreach (['equipamiento' => ['Ticket SoloEQ', 'Ticket MixtoEQ'], 'red-roo-reads' => ['Ticket SoloRRR', 'Ticket MixtoRRR']] as $slug => $titulosEsperados) {
            $excel = $this->get(route('seguimiento.excel', $slug))->assertOk();
            $libro = IOFactory::load($excel->getFile()->getPathname());
            $this->assertSame(['Resumen', 'Colegios', 'Tickets', 'Sesiones'], $libro->getSheetNames());

            $ticketsSheet = $libro->getSheetByName('Tickets');
            $this->assertEqualsCanonicalizing(
                $titulosEsperados,
                [$ticketsSheet->getCell('C5')->getValue(), $ticketsSheet->getCell('C6')->getValue()]
            );
            $this->assertNull($ticketsSheet->getCell('C7')->getValue());
        }
    }

    public function test_formatos_and_manuales_tabs_work_for_frances_red_roo_reads_and_equipamiento(): void
    {
        Storage::fake('local');

        $admin = $this->usuario('admin');
        $eca = $this->usuario('consultor_eca');
        $elt = $this->usuario('consultor_elt');

        foreach (['frances', 'red-roo-reads', 'equipamiento'] as $slug) {
            $this->actingAs($admin)
                ->get(route('seguimiento.show', $slug))
                ->assertOk()
                ->assertSee('data-seg-vista="tabla"', false)
                ->assertSee('data-seg-vista="tickets"', false)
                ->assertSee('data-seg-vista="sesiones"', false)
                ->assertSee('data-seg-vista="formatos"', false)
                ->assertSee('data-seg-vista="manuales"', false);

            $this->actingAs($admin)->post(route('seguimiento.recursos.store', $slug), [
                'titulo'    => "Formato {$slug}",
                'categoria' => 'formatos',
                'audiencia' => ['consultor_eca'],
                'archivo'   => UploadedFile::fake()->create("formato_{$slug}.pdf", 50, 'application/pdf'),
            ])->assertRedirect(route('seguimiento.show', $slug));

            $this->actingAs($admin)->post(route('seguimiento.recursos.store', $slug), [
                'titulo'    => "Manual {$slug}",
                'categoria' => 'manuales',
                'audiencia' => ['todos'],
                'archivo'   => UploadedFile::fake()->create("manual_{$slug}.pdf", 60, 'application/pdf'),
            ])->assertRedirect(route('seguimiento.show', $slug));

            $this->actingAs($eca)
                ->get(route('seguimiento.show', $slug))
                ->assertOk()
                ->assertSee("Formato {$slug}")
                ->assertSee("Manual {$slug}");

            $this->actingAs($elt)
                ->get(route('seguimiento.show', $slug))
                ->assertOk()
                ->assertDontSee("Formato {$slug}")
                ->assertSee("Manual {$slug}");
        }

        $this->assertSame(6, SeguimientoRecurso::count());

        // Roles de solo lectura no pueden subir recursos ni editar, pero sí consultar y descargar Excel
        $this->actingAs($this->usuario('consultor_digital'))
            ->post(route('seguimiento.recursos.store', 'equipamiento'), [
                'titulo'    => 'No permitido',
                'categoria' => 'formatos',
                'audiencia' => ['todos'],
                'archivo'   => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])->assertForbidden();

        foreach (['consultor_eca', 'consultor_elt', 'coordinador', 'representante_ventas'] as $rol) {
            $user = $this->usuario($rol);
            $this->actingAs($user)->get(route('seguimiento.show', 'equipamiento'))->assertOk();
            $this->actingAs($user)->get(route('seguimiento.excel', 'equipamiento'))->assertOk();
            $this->actingAs($user)->get(route('seguimiento.show', 'red-roo-reads'))->assertOk();
            $this->actingAs($user)->get(route('seguimiento.excel', 'red-roo-reads'))->assertOk();
        }
    }
}
