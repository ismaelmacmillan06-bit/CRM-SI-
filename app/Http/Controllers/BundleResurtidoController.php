<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bundle;
use App\Models\BundleResurtido;
use App\Models\School;
use Illuminate\Http\Request;

class BundleResurtidoController extends Controller
{
    public function store(Request $request, School $school, Bundle $bundle)
    {
        $request->validate([
            'cantidad_resurtido' => 'required|integer|min:1',
            'autorizado_por'     => 'nullable|string|max:255',
            'fecha'              => 'required|date',
        ]);

        $pivot = $school->bundles()->where('bundle_id', $bundle->id)->first();

        if (!$pivot) {
            return back()->with('error', 'Bundle no encontrado en este colegio.');
        }

        $cantidadAnterior = $pivot->pivot->quantity;
        $cantidadNueva    = $cantidadAnterior + $request->cantidad_resurtido;

        $school->bundles()->updateExistingPivot($bundle->id, ['quantity' => $cantidadNueva]);

        BundleResurtido::create([
            'school_id'          => $school->id,
            'bundle_id'          => $bundle->id,
            'cantidad_anterior'  => $cantidadAnterior,
            'cantidad_resurtido' => $request->cantidad_resurtido,
            'cantidad_nueva'     => $cantidadNueva,
            'autorizado_por'     => $request->autorizado_por,
            'fecha'              => $request->fecha,
            'user_id'            => auth()->id(),
        ]);

        ActivityLog::log(
            'bundle',
            "Resurtido: \"{$bundle->name}\" +{$request->cantidad_resurtido} (de {$cantidadAnterior} a {$cantidadNueva})" . ($request->autorizado_por ? " — Autorizado por: {$request->autorizado_por}" : ''),
            $school->id,
            '🔄'
        );

        return back()->with('success', "Resurtido registrado: +{$request->cantidad_resurtido} unidad(es). Total actual: {$cantidadNueva}.");
    }
}
