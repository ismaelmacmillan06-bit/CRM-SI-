<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Bundle;
use Illuminate\Http\Request;

class SchoolBundleController extends Controller
{
    public function index(School $school)
    {
        $schoolBundles = $school->bundles()->orderBy('type')->orderBy('serie')->get();
        $series = Bundle::select('serie', 'type')->distinct()->orderBy('type')->orderBy('serie')->get();
        return view('bundles.school', compact('school', 'schoolBundles', 'series'));
    }

    public function getBundlesBySeries(Request $request)
    {
        $series = $request->series ?? [];
        $bundles = Bundle::whereIn('serie', $series)
                        ->orderBy('type')
                        ->orderBy('serie')
                        ->orderBy('level')
                        ->orderBy('grade')
                        ->orderBy('role')
                        ->get()
                        ->groupBy('serie');
        return response()->json($bundles);
    }

    public function store(Request $request, School $school)
    {
        $request->validate([
            'bundle_ids'   => 'required|array',
            'bundle_ids.*' => 'exists:bundles,id',
            'acquired_at'  => 'nullable|date',
        ]);

        foreach ($request->bundle_ids as $bundleId) {
            $quantity = $request->quantities[$bundleId] ?? 1;
            // Evitar duplicados
            if (!$school->bundles()->where('bundle_id', $bundleId)->exists()) {
                $school->bundles()->attach($bundleId, [
                    'quantity'    => $quantity,
                    'acquired_at' => $request->acquired_at,
                ]);
            }
        }

        return redirect()->route('schools.bundles.index', $school)
                         ->with('success', 'Bundles agregados correctamente.');
    }

    public function destroy(School $school, Bundle $bundle)
    {
        $school->bundles()->detach($bundle->id);
        return back()->with('success', 'Bundle eliminado correctamente.');
    }
}