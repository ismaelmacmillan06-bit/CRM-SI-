<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\ExternalPlatform;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolExternalPlatformStep;
use Illuminate\Http\Request;

class SeguimientoExternoController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $schoolsQuery = School::query()->orderBy('name');

        if ($user->hasRole('consultor_digital')) {
            $consultant = Consultant::where('user_id', $user->id)->first();
            $ids = SchoolConsultant::where('consultant_id', $consultant?->id)
                ->where('role', 'digital')
                ->pluck('school_id');
            $schoolsQuery->whereIn('id', $ids);
        }

        $search = $request->get('q');
        if ($search) {
            $schoolsQuery->where('name', 'like', "%{$search}%");
        }

        $schools = $schoolsQuery->get(['id', 'name', 'state', 'city']);

        return view('seguimiento-externo.index', compact('schools', 'search'));
    }

    public function platforms(School $school)
    {
        $platforms = ExternalPlatform::orderBy('order')->get()->map(function ($platform) use ($school) {
            $stepIds = $platform->steps->pluck('id');
            $total   = $stepIds->count();
            $done    = SchoolExternalPlatformStep::where('school_id', $school->id)
                ->whereIn('external_platform_step_id', $stepIds)
                ->where('status', 'done')
                ->count();

            return [
                'platform' => $platform,
                'total'    => $total,
                'done'     => $done,
                'pct'      => $total > 0 ? round($done / $total * 100) : 0,
            ];
        });

        return view('seguimiento-externo.plataformas', compact('school', 'platforms'));
    }

    public function checklist(School $school, ExternalPlatform $platform)
    {
        // Asegura que exista un registro de seguimiento por cada paso de esta plataforma
        foreach ($platform->steps as $step) {
            SchoolExternalPlatformStep::firstOrCreate([
                'school_id'                 => $school->id,
                'external_platform_step_id' => $step->id,
            ]);
        }

        $items = SchoolExternalPlatformStep::with(['step', 'completedBy.user'])
            ->where('school_id', $school->id)
            ->whereIn('external_platform_step_id', $platform->steps->pluck('id'))
            ->get()
            ->sortBy('step.order')
            ->values();

        $total = $items->count();
        $done  = $items->where('status', 'done')->count();

        return view('seguimiento-externo.checklist', compact('school', 'platform', 'items', 'total', 'done'));
    }

    public function update(Request $request, School $school, ExternalPlatform $platform, SchoolExternalPlatformStep $item)
    {
        abort_unless($item->school_id === $school->id, 404);

        $request->validate([
            'status' => 'required|in:pending,done',
            'notes'  => 'nullable|string',
        ]);

        $data = [
            'status' => $request->status,
            'notes'  => $request->notes,
        ];

        if ($request->status === 'done') {
            $consultant = Consultant::where('user_id', auth()->id())->first();
            $data['completed_at'] = now();
            $data['completed_by'] = $consultant?->id;
        } else {
            $data['completed_at'] = null;
            $data['completed_by'] = null;
        }

        $item->update($data);

        return back()->with('success', 'Actualizado correctamente.');
    }
}
