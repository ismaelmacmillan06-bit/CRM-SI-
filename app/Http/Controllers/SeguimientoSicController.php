<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\School;
use Illuminate\Http\Request;

class SeguimientoSicController extends Controller
{
    // Umbrales mínimos para considerar un rubro "cargado" en el SIC
    const MIN_DOCENTES = 5;
    const MIN_ALUMNOS  = 40;
    const MIN_BUNDLES  = 40;

    public function index(Request $request)
    {
        $consultorId      = $request->query('consultor');
        $soloIncompletos  = $request->boolean('incompletos');

        $query = School::with(['consultorDigital.user'])
            ->withCount(['teachers', 'students', 'bundles'])
            ->orderBy('name');

        if ($consultorId) {
            $query->whereHas('schoolConsultants', function ($q) use ($consultorId) {
                $q->where('role', 'digital')->where('consultant_id', $consultorId);
            });
        }

        $schools = $query->get()->map(function ($school) {
            $school->docentes_ok = $school->teachers_count > self::MIN_DOCENTES;
            $school->alumnos_ok  = $school->students_count > self::MIN_ALUMNOS;
            $school->bundles_ok  = $school->bundles_count > self::MIN_BUNDLES;
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
