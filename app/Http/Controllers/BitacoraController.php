<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = ActivityLog::with(['user', 'school'])->orderByDesc('created_at');

        // Consultor digital solo ve sus colegios
        if ($user->hasRole('consultor_digital')) {
            $consultant = Consultant::where('user_id', $user->id)->first();
            $schoolIds  = SchoolConsultant::where('consultant_id', $consultant?->id)
                ->where('role', 'digital')->pluck('school_id');
            $query->whereIn('school_id', $schoolIds);
        }

        // Filtros
        if ($request->filled('tipo'))      $query->where('tipo', $request->tipo);
        if ($request->filled('school_id')) $query->where('school_id', $request->school_id);
        if ($request->filled('desde'))     $query->whereDate('created_at', '>=', $request->desde);
        if ($request->filled('hasta'))     $query->whereDate('created_at', '<=', $request->hasta);

        // Sin filtros de fecha → últimos 30 días por defecto
        if (!$request->filled('desde') && !$request->filled('hasta')) {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $logs    = $query->paginate(20)->withQueryString();
        $schools = School::orderBy('name')->get(['id', 'name']);

        return view('bitacora.index', compact('logs', 'schools'));
    }
}
