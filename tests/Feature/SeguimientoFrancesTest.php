<?php

namespace Tests\Feature;

use App\Http\Controllers\FormatoEntregaController;
use App\Http\Controllers\SeguimientoController;
use App\Models\ActivityLog;
use App\Models\Consultant;
use App\Models\Level;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoAccion;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguimientoFrancesTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));

        return $user;
    }

    private function colegioFrances(string $nombre, bool $conFrances = true, bool $conGlobalEduca = false, string $status = 'activo', array $niveles = []): School
    {
        $school = School::create(['name' => $nombre, 'status' => $status, 'nexus_id' => 'FR-' . random_int(100, 999)]);
        if ($conFrances) {
            $school->services()->attach(SchoolServiceType::where('plataforma', 'frances')->value('id'));
        }
        if ($conGlobalEduca) {
            $school->services()->attach(SchoolServiceType::where('plataforma', 'global-educa')->value('id'));
        }
        if ($niveles !== []) {
            $levelIds = collect($niveles)->map(fn (string $nombreNivel) => Level::firstOrCreate(['name' => $nombreNivel])->id)->all();
            $school->levels()->attach($levelIds);
        }

        return $school;
    }

    private function todasCompletasFrances(): array
    {
        return collect(SeguimientoController::PLATAFORMAS['frances']['acciones'])
            ->map(fn () => ['status' => 'done', 'notes' => null])
            ->all();
    }

    private function crearActividad(School $school, array $productos, string $ticketStatus = 'open', string $sesionStatus = 'pendiente', string $titulo = ''): array
    {
        $consultor = Consultant::firstOrCreate(['user_id' => User::factory()->create()->id]);
        $label = $titulo ?: implode('+', $productos);

        $ticket = Ticket::create([
            'school_id'     => $school->id,
            'consultant_id' => $consultor->id,
            'title'         => 'Ticket ' . $label,
            'description'   => 'Detalle ' . $label,
            'status'        => $ticketStatus,
            'priority'      => 'medium',
            'medium'        => 'whatsapp',
            'productos'     => $productos,
        ]);

        $visit = Visit::create([
            'school_id'      => $school->id,
            'consultant_id'  => $consultor->id,
            'status'         => $sesionStatus,
            'scheduled_date' => today(),
            'modalidad'      => 'virtual',
            'tipo'           => 'programada',
            'prioridad'      => 'media',
            'motivo'         => 'Sesión ' . $label,
            'productos'      => $productos,
        ]);

        return [$ticket, $visit];
    }

    public function test_frances_dashboard_lists_only_active_schools_with_frances_service(): void
    {
        $school = $this->colegioFrances('Colegio Francés Activo', true, false, 'activo', ['Preescolar', 'Primaria']);
        Level::firstOrCreate(['name' => 'Secundaria']);
        $this->colegioFrances('Colegio Solo GE', false, true);
        $this->colegioFrances('Colegio Francés Inactivo', true, false, 'inactivo');

        $this->actingAs($this->usuario('admin'));

        $this->get(route('seguimiento.show', 'frances'))
            ->assertOk()
            ->assertSee('Colegio Francés Activo')
            ->assertDontSee('Colegio Solo GE')
            ->assertDontSee('Colegio Francés Inactivo')
            ->assertSee('Preescolar: Alta de docentes en Espace Virtuel')
            ->assertSee('Preescolar 0/7')
            ->assertSee('Primaria 0/7')
            ->assertDontSee('Secundaria');

        $detail = $this->get(route('seguimiento.colegio', ['frances', $school]))->assertOk();
        $detail->assertSee('id="panel-nivel-' . $school->levels->firstWhere('name', 'Preescolar')->id . '"', false)
            ->assertSee('id="panel-nivel-' . $school->levels->firstWhere('name', 'Primaria')->id . '"', false)
            ->assertSee('Expandir todos')
            ->assertSee('Contraer todos')
            ->assertDontSee('Secundaria');
        foreach (SeguimientoController::PLATAFORMAS['frances']['acciones'] as $etiqueta) {
            $detail->assertSee($etiqueta);
        }

        $sinNiveles = $this->colegioFrances('Colegio FR Sin Niveles');
        $this->get(route('seguimiento.colegio', ['frances', $sinNiveles]))
            ->assertOk()
            ->assertSee('Este colegio no tiene niveles registrados.');
    }

    public function test_frances_actions_can_be_saved_reverted_and_respect_roles(): void
    {
        $admin = $this->usuario('admin');
        $school = $this->colegioFrances('Colegio Acciones FR', true, false, 'activo', ['Preescolar', 'Primaria']);
        $preescolar = $school->levels->firstWhere('name', 'Preescolar');
        $primaria = $school->levels->firstWhere('name', 'Primaria');
        $ajeno = Level::firstOrCreate(['name' => 'Secundaria']);

        // Guardar solo Preescolar + intento con nivel ajeno al colegio (se ignora)
        $this->actingAs($admin)
            ->post(route('seguimiento.guardar', ['frances', $school]), [
                'acciones' => [
                    $preescolar->id => $this->todasCompletasFrances(),
                    $ajeno->id      => $this->todasCompletasFrances(),
                ],
            ])
            ->assertRedirect();

        $this->assertSame(7, SeguimientoAccion::where('school_id', $school->id)->where('plataforma', 'frances')->where('level_id', $preescolar->id)->where('status', 'done')->count());
        $this->assertSame(0, SeguimientoAccion::where('school_id', $school->id)->where('plataforma', 'frances')->where('level_id', $ajeno->id)->count());
        $this->assertSame(7, ActivityLog::where('tipo', 'seguimiento')->count());
        $this->assertStringContainsString('Francés (Preescolar):', ActivityLog::where('tipo', 'seguimiento')->value('descripcion'));

        $this->get(route('seguimiento.show', 'frances'))
            ->assertSee('7/14')
            ->assertSee('50%')
            ->assertSee('Preescolar 7/7')
            ->assertSee('Primaria 0/7')
            ->assertSee('Primaria: Alta de docentes en Espace Virtuel');
        $this->get(route('seguimiento.colegio', ['frances', $school]))
            ->assertSee('7/14 completadas (50%)');

        // Completar Primaria también => 14/14 (100%)
        $this->post(route('seguimiento.guardar', ['frances', $school]), [
            'acciones' => [
                $primaria->id => $this->todasCompletasFrances(),
            ],
        ])->assertRedirect();

        $this->assertSame(14, SeguimientoAccion::where('school_id', $school->id)->where('plataforma', 'frances')->where('status', 'done')->count());
        $this->get(route('seguimiento.show', 'frances'))
            ->assertSee('14/14')
            ->assertSee('100%')
            ->assertSee('Implementación completa');
        $this->get(route('seguimiento.colegio', ['frances', $school]))
            ->assertSee('14/14 completadas (100%)');

        // Revertir una acción en Preescolar no altera Primaria
        $this->post(route('seguimiento.guardar', ['frances', $school]), [
            'acciones' => [
                $preescolar->id => ['alta-docentes' => ['status' => 'in_progress', 'notes' => 'Faltan 2 docentes']],
            ],
        ]);
        $accionPre = SeguimientoAccion::where('school_id', $school->id)->where('plataforma', 'frances')->where('level_id', $preescolar->id)->where('accion', 'alta-docentes')->firstOrFail();
        $accionPri = SeguimientoAccion::where('school_id', $school->id)->where('plataforma', 'frances')->where('level_id', $primaria->id)->where('accion', 'alta-docentes')->firstOrFail();
        $this->assertSame('in_progress', $accionPre->status);
        $this->assertNull($accionPre->completed_at);
        $this->assertNull($accionPre->completed_by);
        $this->assertSame('Faltan 2 docentes', $accionPre->notes);
        $this->assertSame('done', $accionPri->status);

        // Consultor digital solo gestiona colegios asignados
        $digitalUser = $this->usuario('consultor_digital');
        $digitalConsultant = Consultant::create(['user_id' => $digitalUser->id]);
        $propio = $this->colegioFrances('Colegio FR Propio', true, false, 'activo', ['Primaria']);
        SchoolConsultant::create(['school_id' => $propio->id, 'consultant_id' => $digitalConsultant->id, 'role' => 'digital']);

        $this->actingAs($digitalUser)->get(route('seguimiento.show', 'frances'))
            ->assertOk()
            ->assertSee('Colegio FR Propio')
            ->assertDontSee('Colegio Acciones FR');
        $this->get(route('seguimiento.colegio', ['frances', $propio]))->assertOk();
        $this->get(route('seguimiento.colegio', ['frances', $school]))->assertForbidden();

        // Roles de solo lectura ven los paneles desplegables en modo consulta y pueden descargar Formato de Entrega y Excel
        Student::create([
            'school_id'    => $school->id,
            'name'         => 'Camille',
            'last_name'    => 'Laurent',
            'mee_username' => 'camille@example.test',
            'mee_password' => 'secret1',
            'level'        => 'Primaria',
            'grade'        => '1°A',
        ]);

        foreach (['consultor_eca', 'consultor_elt', 'coordinador', 'representante_ventas'] as $rol) {
            $this->actingAs($this->usuario($rol));
            $this->get(route('seguimiento.show', 'frances'))
                ->assertOk()
                ->assertSee('Colegio Acciones FR')
                ->assertSee('Ver →')
                ->assertSee('Formato entrega')
                ->assertDontSee('Gestionar →');
            $this->get(route('seguimiento.colegio', ['frances', $school]))
                ->assertOk()
                ->assertSee('Modo consulta')
                ->assertSee('seg-nivel-panel')
                ->assertSee('<fieldset disabled', false)
                ->assertDontSee('Guardar cambios');
            $this->get(route('seguimiento.excel', 'frances'))->assertOk();
            $this->get(route('schools.formato-entrega', ['school' => $school, 'plataforma' => 'frances']))->assertOk();
            $this->get(route('schools.formato-entrega.accesos', ['school' => $school, 'plataforma' => 'frances']))->assertOk();
        }
    }

    public function test_frances_is_valid_product_in_tickets_and_visits_single_and_combined(): void
    {
        $admin = $this->usuario('admin');
        $consultant = Consultant::create(['user_id' => $admin->id]);
        $school = $this->colegioFrances('Colegio Productos FR');

        $this->actingAs($admin);

        $this->get(route('schools.tickets.create', $school))->assertOk()->assertSee('value="Francés"', false);
        $this->get(route('schools.visits.create', $school))->assertOk()->assertSee('value="Francés"', false);

        $this->post(route('schools.tickets.store', $school), [
            'title'         => 'Alta de docente',
            'description'   => 'Cuenta Espace Virtuel',
            'priority'      => 'medium',
            'consultant_id' => $consultant->id,
            'medium'        => 'whatsapp',
            'productos'     => ['MEE', 'Francés'],
        ])->assertRedirect(route('schools.tickets.index', $school));

        $ticket = Ticket::firstOrFail();
        $this->assertSame(['MEE', 'Francés'], $ticket->productos);
        $this->get(route('schools.tickets.index', $school))->assertOk()->assertSee('🗼 Francés');

        $this->put(route('tickets.update', $ticket), [
            'title'         => 'Alta de docente',
            'description'   => 'Solo Francés',
            'priority'      => 'high',
            'status'        => 'closed',
            'medium'        => 'salesforce',
            'productos'     => ['Francés'],
        ])->assertRedirect();
        $this->assertSame(['Francés'], $ticket->fresh()->productos);

        $this->post(route('schools.visits.store', $school), [
            'consultant_id'  => $consultant->id,
            'scheduled_date' => '2026-12-01',
            'status'         => 'pendiente',
            'modalidad'      => 'virtual',
            'tipo'           => 'programada',
            'prioridad'      => 'media',
            'productos'      => ['Global Educa', 'Francés'],
        ])->assertRedirect(route('schools.visits.index', $school));

        $visit = Visit::firstOrFail();
        $this->assertSame(['Global Educa', 'Francés'], $visit->productos);
        $this->get(route('schools.visits.index', $school))->assertOk()->assertSee('🗼 Francés');
    }

    public function test_frances_dashboard_and_excel_include_tickets_and_sessions_with_frances_even_combined(): void
    {
        $school = $this->colegioFrances('Colegio Seguimiento FR', true, false, 'activo', ['Preescolar', 'Primaria']);
        $preescolar = $school->levels->firstWhere('name', 'Preescolar');
        $sinFrances = $this->colegioFrances('Colegio Sin FR', false, true);

        $this->crearActividad($school, ['Francés'], 'closed', 'terminada', 'SoloFR');
        $this->crearActividad($school, ['MEE', 'Francés'], 'open', 'pendiente', 'MixtoFR');
        $this->crearActividad($school, ['Global Educa'], 'open', 'pendiente', 'SoloGE');
        $this->crearActividad($sinFrances, ['Francés'], 'open', 'pendiente', 'ColegioAjeno');

        $this->actingAs($this->usuario('admin'));
        $this->post(route('seguimiento.guardar', ['frances', $school]), [
            'acciones' => [
                $preescolar->id => ['alta-docentes' => ['status' => 'done', 'notes' => null]],
            ],
        ]);

        $res = $this->get(route('seguimiento.show', 'frances'))
            ->assertOk()
            ->assertSee('Tickets Francés')
            ->assertSee('1 abiertos · 1 cerrados')
            ->assertSee('Sesiones Francés')
            ->assertSee('1 realizadas · 1 pendientes')
            ->assertSee('Ticket SoloFR')
            ->assertSee('Ticket MixtoFR')
            ->assertSee('Sesión SoloFR')
            ->assertSee('Sesión MixtoFR')
            ->assertDontSee('Ticket SoloGE')
            ->assertDontSee('Ticket ColegioAjeno')
            ->assertDontSee('Sesión SoloGE')
            ->assertDontSee('Sesión ColegioAjeno')
            ->assertSee('logo-espace-virtuel.png')
            ->assertSee('Formato entrega');
        $res->assertSee(route('seguimiento.excel', 'frances'), false);

        $excelRes = $this->get(route('seguimiento.excel', 'frances'))->assertOk();
        $libro = IOFactory::load($excelRes->getFile()->getPathname());
        $this->assertSame(['Resumen', 'Colegios', 'Acciones de arranque', 'Tickets', 'Sesiones'], $libro->getSheetNames());

        $resumenSheet = $libro->getSheetByName('Resumen');
        $this->assertSame('Niveles completados', $resumenSheet->getCell('B14')->getValue());
        $this->assertSame('1 / 2', $resumenSheet->getCell('B15')->getValue());

        $accionesSheet = $libro->getSheetByName('Acciones de arranque');
        $this->assertSame('Nivel', $accionesSheet->getCell('C4')->getValue());
        $this->assertSame('Preescolar', $accionesSheet->getCell('C5')->getValue());
        $this->assertSame('Completado', $accionesSheet->getCell('D5')->getValue());
        $this->assertSame('1/7', $accionesSheet->getCell('K5')->getValue());
        $this->assertSame('Primaria', $accionesSheet->getCell('C6')->getValue());
        $this->assertSame('Pendiente', $accionesSheet->getCell('D6')->getValue());
        $this->assertSame('0/7', $accionesSheet->getCell('K6')->getValue());

        $ticketsSheet = $libro->getSheetByName('Tickets');
        $ticketsTitulos = [$ticketsSheet->getCell('C5')->getValue(), $ticketsSheet->getCell('C6')->getValue()];
        $this->assertEqualsCanonicalizing(['Ticket SoloFR', 'Ticket MixtoFR'], $ticketsTitulos);
        $this->assertNull($ticketsSheet->getCell('C7')->getValue());

        $sesionesSheet = $libro->getSheetByName('Sesiones');
        $sesionesMotivos = [$sesionesSheet->getCell('K5')->getValue(), $sesionesSheet->getCell('K6')->getValue()];
        $this->assertEqualsCanonicalizing(['Sesión SoloFR', 'Sesión MixtoFR'], $sesionesMotivos);
        $this->assertNull($sesionesSheet->getCell('K7')->getValue());
    }

    public function test_formato_entrega_frances_excel_omits_gender_and_enforces_platform_access(): void
    {
        $admin = $this->usuario('admin');
        $soloGe = $this->colegioFrances('Colegio Solo Global', false, true);
        $soloFr = $this->colegioFrances('Colegio Espace Virtuel', true, false);

        $this->actingAs($admin);

        // Aislamiento entre plataformas
        $this->get(route('schools.formato-entrega', ['school' => $soloGe, 'plataforma' => 'frances']))->assertForbidden();
        $this->get(route('schools.formato-entrega.accesos', ['school' => $soloGe, 'plataforma' => 'frances']))->assertForbidden();
        $this->get(route('schools.formato-entrega.script', ['school' => $soloGe, 'plataforma' => 'frances']))->assertForbidden();

        $this->get(route('schools.formato-entrega', $soloFr))->assertForbidden();
        $this->get(route('schools.formato-entrega.accesos', $soloFr))->assertForbidden();
        $this->get(route('schools.formato-entrega.script', $soloFr))->assertForbidden();

        Student::create([
            'school_id'    => $soloFr->id,
            'name'         => 'Alex',
            'last_name'    => 'De la Cruz Perez',
            'mee_username' => 'alex@espace.test',
            'mee_password' => '00123',
            'level'        => 'Primaria',
            'grade'        => '1°A',
        ]);
        Student::create([
            'school_id'    => $soloFr->id,
            'name'         => 'Claire',
            'last_name'    => 'Moreau',
            'mee_username' => '',
            'mee_password' => 'clave2',
            'level'        => 'Primaria',
            'grade'        => '1°A',
        ]);

        Teacher::create([
            'school_id'    => $soloFr->id,
            'name'         => 'Alex',
            'last_name'    => 'Dupont',
            'email'        => 'alex.prof@espace.test',
            'mee_password' => 'prof123',
            'grade'        => '1°A',
            'subject'      => 'ELT',
        ]);

        $formatController = app(FormatoEntregaController::class);
        $summaryFr = $formatController->summaryForSchool($soloFr, 'frances');
        // En Francés no se revisa género ("Alex" completo cuenta como 0 por revisar; solo falta usuario en Claire = 1)
        $this->assertSame(1, $summaryFr['studentReviewCount']);
        $this->assertSame(0, $summaryFr['teacherReviewCount']);
        $this->assertSame(2, $summaryFr['studentCount']);
        $this->assertSame(1, $summaryFr['teacherCount']);

        // Excel de alumnos sin columna de género
        $studentRes = $this->get(route('schools.formato-entrega', ['school' => $soloFr, 'plataforma' => 'frances', 'tipo' => 'alumnos']))->assertOk();
        $studentPath = $studentRes->getFile()->getPathname();
        try {
            $this->assertStringContainsString(
                'Cuentas de Alumno Espace Virtuel_Colegio Espace Virtuel_',
                urldecode($studentRes->headers->get('Content-Disposition'))
            );
            $wb = IOFactory::load($studentPath);
            $this->assertSame(['Todos los alumnos', 'Primaria 1°A'], $wb->getSheetNames());
            $sheet = $wb->getSheet(0);
            $this->assertSame(
                ['Nombre', 'Apellido paterno', 'Apellido materno', 'Usuario', 'Contraseña', 'Nivel', 'Grado', 'Grupo'],
                array_map(fn ($col) => $sheet->getCell($col . '6')->getValue(), range('A', 'H'))
            );
            $this->assertNull($sheet->getCell('I6')->getValue());
            $this->assertNull($sheet->getCell('I7')->getValue());
            $this->assertSame('00123', $sheet->getCell('E7')->getValue());
            $this->assertCount(1, $sheet->getDrawingCollection());
        } finally {
            @unlink($studentPath);
        }

        // Excel de docentes sin columna de género
        $teacherRes = $this->get(route('schools.formato-entrega', ['school' => $soloFr, 'plataforma' => 'frances', 'tipo' => 'docentes']))->assertOk();
        $teacherPath = $teacherRes->getFile()->getPathname();
        try {
            $this->assertStringContainsString(
                'Cuentas de Docentes Espace Virtuel_Colegio Espace Virtuel_',
                urldecode($teacherRes->headers->get('Content-Disposition'))
            );
            $wb = IOFactory::load($teacherPath);
            $this->assertSame(['Todos los docentes', 'Docentes - 1°A'], $wb->getSheetNames());
            $sheet = $wb->getSheet(0);
            $this->assertSame(
                ['Nombre', 'Apellido paterno', 'Apellido materno', 'Usuario', 'Contraseña', 'Grado', 'Grupo'],
                array_map(fn ($col) => $sheet->getCell($col . '6')->getValue(), range('A', 'G'))
            );
            $this->assertNull($sheet->getCell('H6')->getValue());
            $this->assertNull($sheet->getCell('H7')->getValue());
            $this->assertSame('alex.prof@espace.test', $sheet->getCell('D7')->getValue());
            $this->assertCount(1, $sheet->getDrawingCollection());
        } finally {
            @unlink($teacherPath);
        }
    }

    public function test_formato_entrega_frances_accesos_returns_espace_virtuel_link_and_logo_exists(): void
    {
        $this->assertFileExists(public_path('images/logo-espace-virtuel.png'));
        $this->assertFileExists(public_path('images/logo-formato-entrega.png'));

        $admin = $this->usuario('admin');
        $school = $this->colegioFrances('Colegio Tarjetas EV');

        Student::create([
            'school_id'    => $school->id,
            'name'         => 'Marie',
            'last_name'    => 'Curie',
            'mee_username' => 'marie@ev.test',
            'mee_password' => 'passEV1',
            'level'        => 'Secundaria',
            'grade'        => '2°B',
        ]);
        Teacher::create([
            'school_id'    => $school->id,
            'name'         => 'Pierre',
            'last_name'    => 'Martin',
            'email'        => 'pierre@ev.test',
            'mee_password' => 'passDoc1',
            'grade'        => '2°B',
            'subject'      => 'ELT',
        ]);

        $this->actingAs($admin);

        $this->getJson(route('schools.formato-entrega.accesos', ['school' => $school, 'plataforma' => 'frances', 'tipo' => 'alumnos']))
            ->assertOk()
            ->assertJson([
                'school'   => 'Colegio Tarjetas EV',
                'link'     => 'https://espacevirtuel.com/macmillaneducationmexico',
                'brand'    => 'frances',
                'omitidos' => 0,
                'groups'   => [
                    [
                        'clase'   => 'Secundaria 2°B',
                        'alumnos' => [
                            ['name' => 'Marie Curie', 'username' => 'marie@ev.test', 'password' => 'passEV1'],
                        ],
                    ],
                ],
            ]);

        $this->getJson(route('schools.formato-entrega.accesos', ['school' => $school, 'plataforma' => 'frances', 'tipo' => 'docentes']))
            ->assertOk()
            ->assertJson([
                'school'   => 'Colegio Tarjetas EV',
                'link'     => 'https://espacevirtuel.com/macmillaneducationmexico',
                'brand'    => 'frances',
                'groups'   => [
                    [
                        'clase'   => 'Docentes - 2°B',
                        'alumnos' => [
                            ['name' => 'Pierre Martin', 'username' => 'pierre@ev.test', 'password' => 'passDoc1'],
                        ],
                    ],
                ],
            ]);
    }

    public function test_school_show_displays_formato_entrega_buttons_by_platform(): void
    {
        $this->actingAs($this->usuario('admin'));

        $soloFr = $this->colegioFrances('Colegio Ficha Solo FR', true, false);
        $this->get(route('schools.show', $soloFr))
            ->assertOk()
            ->assertSee('🗼 Formato Espace Virtuel')
            ->assertDontSee('🌐 Formato Global Educa')
            ->assertSee('logo-espace-virtuel.png')
            ->assertSee('plataforma=frances');

        $ambos = $this->colegioFrances('Colegio Ficha Ambos', true, true);
        $this->get(route('schools.show', $ambos))
            ->assertOk()
            ->assertSee('🌐 Formato Global Educa')
            ->assertSee('🗼 Formato Espace Virtuel')
            ->assertSee('logo-global-educa.png')
            ->assertSee('logo-espace-virtuel.png');
    }

    public function test_frances_levels_subset_can_be_configured_from_seguimiento(): void
    {
        $admin = $this->usuario('admin');
        $school = $this->colegioFrances(
            'Colegio Cuatro Niveles',
            true,
            false,
            'activo',
            ['Preescolar', 'Primaria', 'Secundaria', 'Preparatoria']
        );
        $preescolar = $school->levels->firstWhere('name', 'Preescolar');
        $primaria = $school->levels->firstWhere('name', 'Primaria');
        $secundaria = $school->levels->firstWhere('name', 'Secundaria');
        $preparatoria = $school->levels->firstWhere('name', 'Preparatoria');
        $nivelAjeno = Level::firstOrCreate(['name' => 'Universidad']);

        $this->actingAs($admin);

        // Elegir solo Preescolar y Secundaria (incluyendo un nivel que no tiene el colegio para confirmar que se ignora)
        $this->post(route('seguimiento.niveles', ['frances', $school]), [
            'niveles' => [$preescolar->id, $secundaria->id, $nivelAjeno->id],
        ])->assertRedirect();

        $this->get(route('seguimiento.show', 'frances'))
            ->assertOk()
            ->assertSee('0/14')
            ->assertSee('Preescolar 0/7')
            ->assertSee('Secundaria 0/7')
            ->assertDontSee('Primaria 0/7')
            ->assertDontSee('Preparatoria 0/7');

        $detail = $this->get(route('seguimiento.colegio', ['frances', $school]))->assertOk();
        $detail->assertSee('id="panel-nivel-' . $preescolar->id . '"', false)
            ->assertSee('id="panel-nivel-' . $secundaria->id . '"', false)
            ->assertDontSee('id="panel-nivel-' . $primaria->id . '"', false)
            ->assertDontSee('id="panel-nivel-' . $preparatoria->id . '"', false)
            ->assertDontSee('id="panel-nivel-' . $nivelAjeno->id . '"', false);

        // El selector de niveles ofrece los 4 niveles del colegio
        $detail->assertSee('value="' . $primaria->id . '"', false)
            ->assertSee('value="' . $preparatoria->id . '"', false);

        // Completar los 2 niveles activos => 14/14 (100%) e Implementación completa
        $this->post(route('seguimiento.guardar', ['frances', $school]), [
            'acciones' => [
                $preescolar->id => $this->todasCompletasFrances(),
                $secundaria->id => $this->todasCompletasFrances(),
                $primaria->id   => $this->todasCompletasFrances(), // nivel no activo en Francés: se ignora
            ],
        ])->assertRedirect();

        $this->assertSame(0, SeguimientoAccion::where('school_id', $school->id)->where('plataforma', 'frances')->where('level_id', $primaria->id)->count());
        $this->get(route('seguimiento.show', 'frances'))
            ->assertSee('14/14')
            ->assertSee('100%')
            ->assertSee('Implementación completa');

        // Excel exporta solo las 2 filas de los niveles activos
        $excelRes = $this->get(route('seguimiento.excel', 'frances'))->assertOk();
        $libro = IOFactory::load($excelRes->getFile()->getPathname());
        $this->assertSame('2 / 2', $libro->getSheetByName('Resumen')->getCell('B15')->getValue());
        $accionesSheet = $libro->getSheetByName('Acciones de arranque');
        $this->assertSame('Preescolar', $accionesSheet->getCell('C5')->getValue());
        $this->assertSame('Secundaria', $accionesSheet->getCell('C6')->getValue());
        $this->assertNull($accionesSheet->getCell('C7')->getValue());

        // Desmarcar Preescolar y luego volverlo a marcar conserva sus 7 acciones previas
        $this->post(route('seguimiento.niveles', ['frances', $school]), [
            'niveles' => [$secundaria->id],
        ])->assertRedirect();
        $this->get(route('seguimiento.show', 'frances'))
            ->assertSee('7/7')
            ->assertDontSee('Preescolar 7/7');

        $this->post(route('seguimiento.niveles', ['frances', $school]), [
            'niveles' => [$preescolar->id, $secundaria->id],
        ])->assertRedirect();
        $this->get(route('seguimiento.show', 'frances'))
            ->assertSee('14/14')
            ->assertSee('Preescolar 7/7');

        // Roles de solo lectura y consultor_digital ajeno no pueden cambiar niveles
        $this->actingAs($this->usuario('consultor_eca'))
            ->post(route('seguimiento.niveles', ['frances', $school]), ['niveles' => [$primaria->id]])
            ->assertRedirect();
        $this->assertEqualsCanonicalizing(
            [$preescolar->id, $secundaria->id],
            $school->fresh()->nivelesDePlataforma('frances')->pluck('id')->all()
        );

        $digitalUser = $this->usuario('consultor_digital');
        Consultant::create(['user_id' => $digitalUser->id]);
        $this->actingAs($digitalUser)
            ->post(route('seguimiento.niveles', ['frances', $school]), ['niveles' => [$primaria->id]])
            ->assertForbidden();
    }

    public function test_frances_levels_subset_can_be_configured_when_editing_school(): void
    {
        foreach (['consultor_digital', 'consultor_eca', 'consultor_elt', 'representante_ventas'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }

        $admin = $this->usuario('admin');
        $school = $this->colegioFrances(
            'Colegio Editar Niveles FR',
            true,
            false,
            'activo',
            ['Preescolar', 'Primaria', 'Secundaria']
        );
        $preescolar = $school->levels->firstWhere('name', 'Preescolar');
        $primaria = $school->levels->firstWhere('name', 'Primaria');
        $secundaria = $school->levels->firstWhere('name', 'Secundaria');
        $francesServiceId = SchoolServiceType::where('plataforma', 'frances')->value('id');

        $this->actingAs($admin);

        // El formulario de edición muestra el selector de niveles para Francés
        $this->get(route('schools.edit', $school))
            ->assertOk()
            ->assertSee('Niveles que llevan Francés')
            ->assertSee('name="frances_niveles[]"', false);

        // Editar colegio seleccionando solo Preescolar y Primaria para Francés
        $this->put(route('schools.update', $school), [
            'name'                    => $school->name,
            'status'                  => 'activo',
            'levels'                  => [$preescolar->id, $primaria->id, $secundaria->id],
            'services'                => [$francesServiceId],
            'frances_niveles_enviado' => '1',
            'frances_niveles'         => [$preescolar->id, $primaria->id],
        ])->assertRedirect(route('schools.index'));

        $this->get(route('schools.show', $school))
            ->assertOk()
            ->assertSee('Preescolar, Primaria');

        $this->get(route('seguimiento.colegio', ['frances', $school]))
            ->assertOk()
            ->assertSee('id="panel-nivel-' . $preescolar->id . '"', false)
            ->assertSee('id="panel-nivel-' . $primaria->id . '"', false)
            ->assertDontSee('id="panel-nivel-' . $secundaria->id . '"', false);

        // Quitar Primaria de los niveles del colegio también la retira de Francés
        $this->put(route('schools.update', $school), [
            'name'                    => $school->name,
            'status'                  => 'activo',
            'levels'                  => [$preescolar->id, $secundaria->id],
            'services'                => [$francesServiceId],
            'frances_niveles_enviado' => '1',
            'frances_niveles'         => [$preescolar->id, $primaria->id],
        ])->assertRedirect(route('schools.index'));

        $this->assertSame([$preescolar->id], $school->fresh()->nivelesDePlataforma('frances')->pluck('id')->all());

        // Dejar frances_niveles vacío muestra 0 paneles y el aviso sin error
        $this->put(route('schools.update', $school), [
            'name'                    => $school->name,
            'status'                  => 'activo',
            'levels'                  => [$preescolar->id, $secundaria->id],
            'services'                => [$francesServiceId],
            'frances_niveles_enviado' => '1',
            'frances_niveles'         => [],
        ])->assertRedirect(route('schools.index'));

        $this->get(route('seguimiento.colegio', ['frances', $school]))
            ->assertOk()
            ->assertSee('No hay niveles seleccionados para Francés.')
            ->assertDontSee('id="panel-nivel-' . $preescolar->id . '"', false);
    }
}
