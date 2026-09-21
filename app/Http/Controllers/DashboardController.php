<?php

namespace App\Http\Controllers;

use App\Helpers\Zonas;
use App\Models\BundleResurtido;
use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherRole;
use App\Models\Student;
use App\Models\Consultant;
use App\Models\SchoolConsultant;
use App\Models\Ticket;
use App\Models\Visit;
use App\Models\Level;
use App\Models\SchoolServiceType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends Controller
{
    // IDs de colegios asignados al usuario si es consultor_digital, null si ve todo
    private function resolveSchoolIds()
    {
        $user = auth()->user();
        if (!$user->hasRole('consultor_digital')) {
            return null;
        }
        $consultant = Consultant::where('user_id', $user->id)->first();
        return SchoolConsultant::where('consultant_id', $consultant?->id)
            ->where('role', 'digital')
            ->pluck('school_id');
    }

    public function index()
    {
        $schoolIds = $this->resolveSchoolIds();

        // Scope helper: filtra por schoolIds si aplica
        $schoolScope   = fn($q) => $schoolIds ? $q->whereIn('school_id', $schoolIds) : $q;
        $schoolScopeId = fn($q) => $schoolIds ? $q->whereIn('id', $schoolIds) : $q;

        // Cards principales
        $totalSchools     = $schoolScopeId(School::query())->count();
        $totalTeachers    = $schoolScope(Teacher::query())->count();
        $totalStudents    = $schoolScope(Student::query())->count();
        $totalConsultants = $schoolIds ? null : Consultant::count();

        // Tickets
        $ticketsAbiertos  = $schoolScope(Ticket::where('status', 'open'))->count();
        $ticketsEnProceso = $schoolScope(Ticket::where('status', 'in_progress'))->count();
        $ticketsResueltos = $schoolScope(Ticket::where('status', 'closed'))->count();

        // Visitas
        $visitasPendientes = $schoolScope(Visit::where('status', 'pendiente'))->count();
        $totalVisitas      = $schoolScope(Visit::query())->count();

        // Directores y Admins MEE (via teacher_roles)
        $teacherScope = fn($q) => $schoolIds
            ? $q->whereHas('teacher', fn($tq) => $tq->whereIn('school_id', $schoolIds))
            : $q;

        $totalDirectores = $teacherScope(
            TeacherRole::whereIn('role', ['director_general', 'director_nivel'])
        )->count();

        $totalAdminsMee = $teacherScope(
            TeacherRole::where('role', 'admin_mee')
        )->count();

        // Docentes por materia
        $docentesELT = $schoolScope(Teacher::where('subject', 'ELT'))->count();
        $docentesECA = $schoolScope(Teacher::where('subject', 'ECA'))->count();

        // Colegios por status
        $colegiosActivos   = $schoolScopeId(School::where('status', 'activo'))->count();
        $colegiosProspecto = $schoolScopeId(School::where('status', 'prospecto'))->count();
        $colegiosInactivos = $schoolScopeId(School::where('status', 'inactivo'))->count();

        // Docentes Registrados Servicios: colegios con "Libro del profesor" completado en ≥1 nivel
        $libroProfesorDetalle = $schoolScopeId(
            School::with([
                'schoolLevels.level',
                'schoolLevels.processes' => fn($q) => $q->where('process_id', 5)->where('status', 'done'),
            ])
        )->get()
        ->map(fn($school) => [
            'id'     => $school->id,
            'name'   => $school->name,
            'levels' => $school->schoolLevels
                ->filter(fn($sl) => $sl->processes->isNotEmpty())
                ->map(fn($sl) => $sl->level->name ?? 'Sin nivel')
                ->values(),
        ])
        ->filter(fn($s) => count($s['levels']) > 0)
        ->sortBy('name')
        ->values();

        $colegiosDocentesRegistrados = $libroProfesorDetalle->count();

        // Colegios entregados: tienen al menos un proceso y todos están en 'done'
        $colegiosEntregados = $schoolScopeId(
            School::whereHas('schoolLevels.processes')
                  ->whereDoesntHave('schoolLevels', fn($q) =>
                      $q->whereHas('processes', fn($q2) => $q2->where('status', '!=', 'done'))
                  )
        )->count();

        // Colegios por estado para el mapa (state tiene prioridad sobre city)
        $colegiosPorEstado = $schoolScopeId(School::selectRaw('COALESCE(state, city) as estado, count(*) as total'))
            ->whereRaw('COALESCE(state, city) IS NOT NULL')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // Colegios por zona (regiones reales de Macmillan SI)
        $colegiosPorZona = array_fill_keys(array_keys(Zonas::map()), 0);
        $colegiosPorZona['Sin zona'] = 0;
        $schoolScopeId(School::select('id', 'city', 'state'))->get()->each(function ($school) use (&$colegiosPorZona) {
            $zona = Zonas::detectZona($school->state ?? $school->city ?? '');
            $colegiosPorZona[$zona]++;
        });

        // Conteo de alumnos por nivel (filtrado igual que el resto)
        $conteoNiveles = $schoolScope(
            Student::selectRaw('LOWER(TRIM(level)) as lvl, COUNT(*) as total')
        )->groupBy('lvl')->pluck('total', 'lvl');

        // Total de resurtidos
        $totalResurtidos = $schoolIds
            ? BundleResurtido::whereIn('school_id', $schoolIds)->count()
            : BundleResurtido::count();

        // Colegios por nivel educativo
        $levels = Level::orderBy('id')->get();
        $colegiosPorNivel = $levels->map(function ($level) use ($schoolIds) {
            $q = \DB::table('school_level')->where('level_id', $level->id);
            if ($schoolIds) $q->whereIn('school_id', $schoolIds);
            return ['name' => $level->name, 'total' => $q->count()];
        });

        // Colegios por servicio contable
        $serviceTypes = SchoolServiceType::active()->get();
        $colegiosPorServicio = $serviceTypes->map(function ($type) use ($schoolIds) {
            $ids = \DB::table('school_service')
                ->where('school_service_type_id', $type->id)
                ->when($schoolIds, fn($q) => $q->whereIn('school_id', $schoolIds))
                ->pluck('school_id');

            $schoolsForType = School::whereIn('id', $ids)
                ->orderBy('name')
                ->get(['id', 'name', 'state', 'city']);

            return [
                'name'    => $type->name,
                'icon'    => $type->icon,
                'color'   => $type->color,
                'total'   => $schoolsForType->count(),
                'schools' => $schoolsForType,
            ];
        });

        // Acciones de arranque: progreso agregado por acción, sumado en todos los colegios
        ['acciones' => $accionesArranque, 'formatos' => $formatosCapacitaciones, 'detalle' => $accionesDetalle]
            = $this->computeAccionesArranque($schoolIds);

        // Análisis: línea de tiempo de arranque — acciones completadas por día (últimos 30 días)
        $timelineArranque = $this->computeTimelineArranque($schoolIds);

        return view('dashboard', compact(
            'totalSchools', 'totalTeachers', 'totalStudents', 'totalConsultants',
            'ticketsAbiertos', 'ticketsEnProceso', 'ticketsResueltos',
            'visitasPendientes', 'totalVisitas',
            'totalDirectores', 'totalAdminsMee',
            'docentesELT', 'docentesECA',
            'colegiosActivos', 'colegiosProspecto', 'colegiosInactivos',
            'colegiosPorEstado', 'colegiosPorZona', 'conteoNiveles',
            'totalResurtidos',
            'colegiosEntregados',
            'colegiosPorNivel', 'colegiosPorServicio',
            'colegiosDocentesRegistrados', 'libroProfesorDetalle',
            'accionesArranque', 'formatosCapacitaciones', 'accionesDetalle',
            'timelineArranque'
        ));
    }

    // Íconos compartidos por acción de arranque (mismo slug del catálogo `processes`).
    private static function procesoIconos(): array
    {
        return [
            'alta_bundles'            => '🔓',
            'capacitacion_admin'      => '🎓',
            'registrar_profesores'    => '👩‍🏫',
            'creacion_clases'         => '🏫',
            'libro_profesor'          => '📘',
            'alta_alumnos'            => '🧑‍🎓',
            'asignacion_libro_alumno' => '📗',
            'generar_contrasenas'     => '🔑',
            'alta_servicios_alumno'   => '🧾',
            'entrega_colegio'         => '✅',
        ];
    }

    // Análisis: línea de tiempo de arranque — qué acciones se completaron cada día,
    // en todos los colegios, en los últimos 30 días. El filtro semana/mes en la vista
    // es puramente client-side (muestra/oculta días ya traídos), así no hace falta
    // un endpoint aparte.
    private function computeTimelineArranque($schoolIds)
    {
        $procesoIconos = self::procesoIconos();
        $hoy   = now()->startOfDay();
        $desde = $hoy->copy()->subDays(29);

        $rows = \DB::table('school_level_process')
            ->join('processes', 'processes.id', '=', 'school_level_process.process_id')
            ->join('school_level', 'school_level.id', '=', 'school_level_process.school_level_id')
            ->when($schoolIds, fn($q) => $q->whereIn('school_level.school_id', $schoolIds))
            ->where('school_level_process.status', 'done')
            ->whereNotNull('school_level_process.completed_at')
            ->where('school_level_process.completed_at', '>=', $desde)
            ->selectRaw('DATE(school_level_process.completed_at) as dia, processes.id as proceso_id,
                         processes.name as accion, processes.slug as slug, COUNT(*) as total')
            ->groupBy('dia', 'processes.id', 'processes.name', 'processes.slug')
            ->orderByDesc('dia')
            ->get();

        return $rows->groupBy('dia')
            ->map(function ($items, $dia) use ($procesoIconos, $hoy) {
                $fecha      = \Carbon\Carbon::parse($dia);
                $fechaCorta = self::fechaEsCorta($fecha);
                $label      = $fecha->isToday() ? 'Hoy, ' . $fechaCorta
                            : ($fecha->isYesterday() ? 'Ayer, ' . $fechaCorta
                            : self::diaSemanaEs($fecha) . ' ' . $fechaCorta);
                return [
                    'fecha'     => $dia,
                    'label'     => $label,
                    'diasAtras' => $hoy->diffInDays($fecha),
                    'total'     => (int) $items->sum('total'),
                    'acciones'  => $items->map(fn ($i) => [
                        'nombre' => $i->accion,
                        'icon'   => $procesoIconos[$i->slug] ?? '📌',
                        'total'  => (int) $i->total,
                    ])->sortByDesc('total')->values(),
                ];
            })
            ->sortByDesc('fecha')
            ->values();
    }

    // Formateo de fechas en español, independiente del locale de la app (que está en 'en').
    private static function diaSemanaEs(\Carbon\Carbon $fecha): string
    {
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        return $dias[$fecha->dayOfWeek];
    }

    private static function fechaEsCorta(\Carbon\Carbon $fecha): string
    {
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                  'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return $fecha->day . ' de ' . $meses[$fecha->month - 1];
    }

    // Progreso agregado por acción de arranque + detalle por colegio (con su consultor digital).
    // Se comparte entre la vista del dashboard y la exportación a Excel.
    private function computeAccionesArranque($schoolIds): array
    {
        $procesoIconos = self::procesoIconos();
        // Interpola un color de rojo (<=55%) a verde (100%) según el % de avance
        $pctColor = function (int $pct): string {
            $t   = max(0, min(1, ($pct - 55) / 45));
            $hue = round($t * 145);
            return "hsl({$hue}, 68%, 42%)";
        };

        $accionesArranque = \DB::table('school_level_process')
            ->join('processes', 'processes.id', '=', 'school_level_process.process_id')
            ->join('school_level', 'school_level.id', '=', 'school_level_process.school_level_id')
            ->when($schoolIds, fn($q) => $q->whereIn('school_level.school_id', $schoolIds))
            ->selectRaw('processes.id, processes.name, processes.slug, processes.order, COUNT(*) as total,
                         SUM(CASE WHEN school_level_process.status = "done" THEN 1 ELSE 0 END) as done')
            ->groupBy('processes.id', 'processes.name', 'processes.slug', 'processes.order')
            ->orderBy('processes.order')
            ->get()
            ->map(function ($row) use ($procesoIconos, $pctColor) {
                $total = (int) $row->total;
                $done  = (int) $row->done;
                $pct   = $total > 0 ? (int) round($done / $total * 100) : 0;
                return [
                    'id'    => $row->id,
                    'slug'  => $row->slug,
                    'name'  => $row->name,
                    'icon'  => $procesoIconos[$row->slug] ?? '📌',
                    'total' => $total,
                    'done'  => $done,
                    'pct'   => $pct,
                    'color' => $pctColor($pct),
                ];
            });

        // Cards de "Formatos y capacitaciones": mismas acciones, resaltadas aparte
        $formatosCapacitaciones = collect([
            ['slug' => 'registrar_profesores', 'label' => 'Formatos Docentes',        'icon' => '📝', 'color' => '#3b82f6'],
            ['slug' => 'alta_alumnos',         'label' => 'Formatos Alumno',          'icon' => '🧑‍🎓', 'color' => '#10b981'],
            ['slug' => 'capacitacion_admin',   'label' => 'Capacitaciones realizadas', 'icon' => '🎓', 'color' => '#8b5cf6'],
        ])->map(function ($cfg) use ($accionesArranque) {
            $row = $accionesArranque->firstWhere('slug', $cfg['slug']);
            return [
                'id'    => $row['id'] ?? null,
                'label' => $cfg['label'],
                'icon'  => $cfg['icon'],
                'color' => $cfg['color'],
                'total' => $row['total'] ?? 0,
                'done'  => $row['done'] ?? 0,
                'pct'   => $row['pct'] ?? 0,
            ];
        });

        // Detalle por colegio/nivel de cada acción de arranque (para el modal del ojito y el
        // Excel): separa colegios que ya la completaron de los que aún no, con su consultor digital.
        $accionesDetalle = \DB::table('school_level_process')
            ->join('school_level', 'school_level.id', '=', 'school_level_process.school_level_id')
            ->join('schools', 'schools.id', '=', 'school_level.school_id')
            ->join('levels', 'levels.id', '=', 'school_level.level_id')
            ->leftJoin('school_consultants', function ($join) {
                $join->on('school_consultants.school_id', '=', 'schools.id')
                     ->where('school_consultants.role', '=', 'digital');
            })
            ->leftJoin('consultants', 'consultants.id', '=', 'school_consultants.consultant_id')
            ->leftJoin('users', 'users.id', '=', 'consultants.user_id')
            ->when($schoolIds, fn($q) => $q->whereIn('schools.id', $schoolIds))
            ->select(
                'school_level_process.process_id',
                'school_level_process.status',
                'schools.id as school_id',
                'schools.name as school_name',
                'schools.state',
                'schools.city',
                'levels.name as level_name',
                'users.name as consultor_digital'
            )
            ->orderBy('schools.name')
            ->get()
            ->groupBy('process_id')
            ->map(fn($rows) => [
                'done'    => $rows->where('status', 'done')->values(),
                'pending' => $rows->where('status', '!=', 'done')->values(),
            ]);

        return [
            'acciones' => $accionesArranque,
            'formatos' => $formatosCapacitaciones,
            'detalle'  => $accionesDetalle,
        ];
    }

    public function exportAccionesArranqueExcel()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $schoolIds = $this->resolveSchoolIds();
        ['acciones' => $accionesArranque, 'detalle' => $accionesDetalle] = $this->computeAccionesArranque($schoolIds);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        // Hoja 1: Resumen por acción
        $ws1 = $spreadsheet->createSheet(0);
        $ws1->setTitle('Resumen');
        $ws1->fromArray(['Acción', 'Completados', 'Total', '% avance'], null, 'A1');
        $ws1->getStyle('A1:D1')->getFont()->setBold(true);
        $r = 2;
        foreach ($accionesArranque as $accion) {
            $ws1->fromArray([$accion['name'], $accion['done'], $accion['total'], $accion['pct'] . '%'], null, 'A' . $r);
            $r++;
        }
        foreach (range('A', 'D') as $col) {
            $ws1->getColumnDimension($col)->setAutoSize(true);
        }

        // Hoja 2: Detalle por colegio, con el consultor digital responsable
        $ws2 = $spreadsheet->createSheet(1);
        $ws2->setTitle('Detalle');
        $headers = ['Colegio', 'Estado/Ciudad', 'Nivel', 'Acción', 'Estado', 'Consultor Digital'];
        $ws2->fromArray($headers, null, 'A1');
        $ws2->getStyle('A1:F1')->getFont()->setBold(true);
        $r = 2;
        foreach ($accionesArranque as $accion) {
            $detalle = $accionesDetalle[$accion['id']] ?? ['done' => collect(), 'pending' => collect()];
            foreach (['done' => 'Completado', 'pending' => 'Pendiente'] as $key => $label) {
                foreach ($detalle[$key] as $row) {
                    $ws2->fromArray([
                        $row->school_name,
                        $row->state ?? $row->city ?? '—',
                        $row->level_name,
                        $accion['name'],
                        $label,
                        $row->consultor_digital ?? 'Sin asignar',
                    ], null, 'A' . $r);
                    $r++;
                }
            }
        }
        foreach (range('A', 'F') as $col) {
            $ws2->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'acciones-arranque-' . now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'crm_');

        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
