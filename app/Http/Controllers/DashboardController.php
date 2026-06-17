<?php

namespace App\Http\Controllers;

use App\Helpers\Zonas;
use App\Models\Bundle;
use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherRole;
use App\Models\Student;
use App\Models\Consultant;
use App\Models\SchoolConsultant;
use App\Models\Ticket;
use App\Models\Visit;

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

        // Colegios por estado para el mapa
        $colegiosPorEstado = $schoolScopeId(School::selectRaw('city, count(*) as total'))
            ->whereNotNull('city')
            ->groupBy('city')
            ->pluck('total', 'city')
            ->toArray();

        // Colegios por zona (regiones reales de Macmillan SI)
        $colegiosPorZona = array_fill_keys(array_keys(Zonas::map()), 0);
        $colegiosPorZona['Sin zona'] = 0;
        $schoolScopeId(School::select('id', 'city'))->get()->each(function ($school) use (&$colegiosPorZona) {
            $zona = Zonas::detectZona($school->city ?? '');
            $colegiosPorZona[$zona]++;
        });

        // Conteo de alumnos por nivel (filtrado igual que el resto)
        $conteoNiveles = $schoolScope(
            Student::selectRaw('LOWER(TRIM(level)) as lvl, COUNT(*) as total')
        )->groupBy('lvl')->pluck('total', 'lvl');

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
            'seriesDisponibles'
        ));
    }
}
