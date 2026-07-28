<?php

namespace App\Http\Controllers;

use App\Helpers\Zonas;
use App\Models\Bundle;
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

        // Colegios con usuarios y contraseñas personalizadas
        $colegiosCustomPasswords = $schoolScopeId(School::where('custom_passwords', true))->count();

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
            $q = \DB::table('school_service')->where('school_service_type_id', $type->id);
            if ($schoolIds) $q->whereIn('school_id', $schoolIds);
            return [
                'name'  => $type->name,
                'icon'  => $type->icon,
                'color' => $type->color,
                'total' => $q->count(),
            ];
        });

        // Series de bundles adoptadas por colegios (para filtro en dashboard)
        $seriesDisponibles = Bundle::select('serie')
            ->whereHas('schools', fn($q) => $schoolIds ? $q->whereIn('schools.id', $schoolIds) : $q)
            ->distinct()
            ->orderBy('serie')
            ->pluck('serie')
            ->filter();

        // Colegios con info completa para las cards
        $schools = $schoolScopeId(School::with([
            'schoolConsultants.consultant.user',
            'meeAdmins',
            'schoolLevels.processes',
            'bundles',
        ]))->get();

        return view('dashboard', compact(
            'totalSchools', 'totalTeachers', 'totalStudents', 'totalConsultants',
            'ticketsAbiertos', 'ticketsEnProceso', 'ticketsResueltos',
            'visitasPendientes', 'totalVisitas',
            'totalDirectores', 'totalAdminsMee',
            'schools', 'docentesELT', 'docentesECA',
            'colegiosActivos', 'colegiosProspecto', 'colegiosInactivos',
            'colegiosPorEstado', 'colegiosPorZona', 'conteoNiveles',
            'seriesDisponibles', 'totalResurtidos',
            'colegiosCustomPasswords', 'colegiosEntregados',
            'colegiosPorNivel', 'colegiosPorServicio'
        ));
    }
}
