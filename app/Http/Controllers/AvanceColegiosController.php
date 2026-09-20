<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;

class AvanceColegiosController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Para consultor_digital: solo sus colegios asignados (igual que el Dashboard)
        $schoolIds = null;
        if ($user->hasRole('consultor_digital')) {
            $consultant = Consultant::where('user_id', $user->id)->first();
            $schoolIds  = SchoolConsultant::where('consultant_id', $consultant?->id)
                ->where('role', 'digital')
                ->pluck('school_id');
        }

        $schoolScopeId = fn($q) => $schoolIds ? $q->whereIn('id', $schoolIds) : $q;

        $schools = $schoolScopeId(School::with([
            'schoolConsultants.consultant.user',
            'meeAdmins',
            'schoolLevels.level',
            'schoolLevels.processes',
            'bundles',
        ]))->orderBy('name')->get();

        $seriesDisponibles = Bundle::select('serie')
            ->whereHas('schools', fn($q) => $schoolIds ? $q->whereIn('schools.id', $schoolIds) : $q)
            ->distinct()
            ->orderBy('serie')
            ->pluck('serie')
            ->filter();

        return view('avance-colegios.index', compact('schools', 'seriesDisponibles'));
    }
}
