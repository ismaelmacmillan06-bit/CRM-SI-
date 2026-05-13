<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Consultant;
use App\Models\Ticket;
use App\Models\Visit;

class DashboardController extends Controller
{
    public function index()
    {
        // Cards principales
        $totalSchools     = School::count();
        $totalTeachers    = Teacher::count();
        $totalStudents    = Student::count();
        $totalConsultants = Consultant::count();

        // Tickets abiertos
        $ticketsAbiertos  = Ticket::where('status', 'open')->count();
        $ticketsEnProceso = Ticket::where('status', 'in_progress')->count();

        // Visitas pendientes
        $visitasPendientes = Visit::where('status', 'pendiente')->count();

        // Colegios con su info para las cards
        $schools = School::with([
            'consultant.user',
            'meeAdmins',
            'schoolLevels.processes'
        ])->get();

        // Docentes por materia
        $docentesELT = Teacher::where('subject', 'ELT')->count();
        $docentesECA = Teacher::where('subject', 'ECA')->count();

        // Colegios por status
        $colegiosActivos    = School::where('status', 'activo')->count();
        $colegiosProspecto  = School::where('status', 'prospecto')->count();
        $colegiosInactivos  = School::where('status', 'inactivo')->count();

        // Colegios por estado para el mapa
        $colegiosPorEstado = School::selectRaw('city, count(*) as total')
        ->whereNotNull('city')
        ->groupBy('city')
        ->pluck('total', 'city')
        ->toArray();

        return view('dashboard', compact(
        'totalSchools', 'totalTeachers', 'totalStudents', 'totalConsultants',
        'ticketsAbiertos', 'ticketsEnProceso', 'visitasPendientes',
        'schools', 'docentesELT', 'docentesECA',
        'colegiosActivos', 'colegiosProspecto', 'colegiosInactivos',
        'colegiosPorEstado'
));
       
    }
}