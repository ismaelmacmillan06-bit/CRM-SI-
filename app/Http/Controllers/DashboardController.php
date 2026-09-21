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

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Para consultor_digital: obtener solo los IDs de sus colegios asignados
        $schoolIds = null;
        if ($user->hasRole('consultor_digital')) {
            $consultant = Consultant::where('user_id', $user->id)->first();
            $schoolIds  = SchoolConsultant::where('consultant_id', $consultant?->id)
                ->where('role', 'digital')
                ->pluck('school_id');
        }

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
        $procesoIconos = [
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
                'label' => $cfg['label'],
                'icon'  => $cfg['icon'],
                'color' => $cfg['color'],
                'total' => $row['total'] ?? 0,
                'done'  => $row['done'] ?? 0,
                'pct'   => $row['pct'] ?? 0,
            ];
        });

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
            'accionesArranque', 'formatosCapacitaciones'
        ));
    }
}
