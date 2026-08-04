<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SsaCapacitacion;
use Illuminate\Http\Request;

class SsaController extends Controller
{
    public function index(Request $request)
    {
        $schools = $this->resolveSchools();

        $schools->load(['ssaCapacitaciones' => function ($q) {
            $q->orderBy('fecha')->orderBy('hora');
        }]);

        $allCaps = $schools->flatMap->ssaCapacitaciones;

        $capsHoy = $schools->flatMap(function ($school) {
            return $school->ssaCapacitaciones
                ->filter(fn($c) => $c->fecha->isToday())
                ->map(function ($c) use ($school) {
                    $c->school_name = $school->name;
                    return $c;
                });
        })->sortBy('hora')->values();

        $stats = [
            'total_escuelas' => $schools->count(),
            'eca_proximas'   => $allCaps->where('tipo', 'eca')
                                        ->whereIn('estatus', ['pendiente', 'confirmado'])
                                        ->where('fecha', '>=', today())->count(),
            'elt_proximas'   => $allCaps->where('tipo', 'elt')
                                        ->whereIn('estatus', ['pendiente', 'confirmado'])
                                        ->where('fecha', '>=', today())->count(),
            'realizadas'     => $allCaps->where('estatus', 'realizado')->count(),
            'hoy'            => $capsHoy->count(),
        ];

        // Mapa school_id → nombre del consultor digital
        $consultoresDigitales = $schools->isNotEmpty()
            ? \DB::table('school_consultants')
                ->join('consultants', 'school_consultants.consultant_id', '=', 'consultants.id')
                ->join('users', 'consultants.user_id', '=', 'users.id')
                ->whereIn('school_consultants.school_id', $schools->pluck('id'))
                ->where('school_consultants.role', 'digital')
                ->select('school_consultants.school_id', 'users.name as nombre')
                ->get()->keyBy('school_id')
            : collect();

        return view('ssa.index', compact('schools', 'stats', 'capsHoy', 'consultoresDigitales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'fecha'     => 'required|date',
            'hora'      => 'nullable|date_format:H:i,H:i:s',
            'tipo'      => 'required|in:eca,elt',
            'estatus'   => 'required|in:pendiente,confirmado,realizado,cancelado',
            'notas'     => 'nullable|string|max:1000',
        ]);

        $this->authorizeSchool($data['school_id']);

        SsaCapacitacion::create(array_merge($data, ['user_id' => auth()->id()]));

        return back()->with('success', 'Capacitación registrada.');
    }

    public function update(Request $request, SsaCapacitacion $ssaCapacitacion)
    {
        $data = $request->validate([
            'fecha'   => 'required|date',
            'hora'    => 'nullable|date_format:H:i,H:i:s',
            'estatus' => 'required|in:pendiente,confirmado,realizado,cancelado',
            'notas'   => 'nullable|string|max:1000',
        ]);

        $this->authorizeSchool($ssaCapacitacion->school_id);

        $ssaCapacitacion->update($data);

        return back()->with('success', 'Capacitación actualizada.');
    }

    public function destroy(SsaCapacitacion $ssaCapacitacion)
    {
        $this->authorizeSchool($ssaCapacitacion->school_id);

        $ssaCapacitacion->delete();

        return back()->with('success', 'Capacitación eliminada.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveSchools()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return School::orderBy('name')->get();
        }

        $roleMap = [
            'consultor_digital'    => 'digital',
            'consultor_eca'        => 'eca',
            'consultor_elt'        => 'elt',
            'representante_ventas' => 'ventas',
        ];

        $consultant = Consultant::where('user_id', $user->id)->first();

        if (! $consultant) {
            return collect();
        }

        foreach ($roleMap as $spatieRole => $dbRole) {
            if ($user->hasRole($spatieRole)) {
                $schoolIds = SchoolConsultant::where('consultant_id', $consultant->id)
                    ->where('role', $dbRole)
                    ->pluck('school_id');

                return School::whereIn('id', $schoolIds)->orderBy('name')->get();
            }
        }

        return collect();
    }

    private function authorizeSchool(int $schoolId): void
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return;
        }

        $ids = $this->resolveSchools()->pluck('id');

        if (! $ids->contains($schoolId)) {
            abort(403, 'No tienes acceso a este colegio.');
        }
    }
}
