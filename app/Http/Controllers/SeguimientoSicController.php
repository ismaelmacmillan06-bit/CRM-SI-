<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\School;
use Illuminate\Http\Request;

class SeguimientoSicController extends Controller
{
    public function index(Request $request)
    {
        $consultorId      = $request->query('consultor');
        $soloIncompletos  = $request->boolean('incompletos');

        $query = School::noInactivos()
            ->with(['consultorDigital.user'])
            ->withCount(['teachers', 'students', 'bundles'])
            ->orderBy('name');

        if ($consultorId) {
            $query->whereHas('schoolConsultants', function ($q) use ($consultorId) {
                $q->where('role', 'digital')->where('consultant_id', $consultorId);
            });
        }

        $schools = $query->get()->map(function ($school) {
            $school->docentes_ok = $school->teachers_count > 0;
            $school->alumnos_ok  = $school->students_count > 0;
            $school->bundles_ok  = $school->bundles_count > 0;
            $school->completo    = $school->docentes_ok && $school->alumnos_ok && $school->bundles_ok;
            return $school;
        });

        $totalColegios  = $schools->count();
        $completos      = $schools->where('completo', true)->count();
        $incompletos    = $totalColegios - $completos;

        if ($soloIncompletos) {
            $schools = $schools->reject(fn($s) => $s->completo)->values();
        }

        $consultores = Consultant::whereHas('schoolConsultants', fn($q) => $q->where('role', 'digital'))
            ->with('user')
            ->get()
            ->filter(fn($c) => $c->user)
            ->sortBy('user.name')
            ->values();

        return view('seguimiento-sic.index', compact(
            'schools', 'consultores', 'consultorId', 'soloIncompletos',
            'totalColegios', 'completos', 'incompletos'
        ));
    }
}
