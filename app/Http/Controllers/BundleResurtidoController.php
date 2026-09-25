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

    public function update(Request $request, School $school, Bundle $bundle, BundleResurtido $resurtido)
    {
        if ($resurtido->school_id !== $school->id || $resurtido->bundle_id !== $bundle->id) {
            abort(404);
        }

        $request->validate([
            'cantidad_resurtido' => 'required|integer|min:1',
            'autorizado_por'     => 'nullable|string|max:255',
            'fecha'              => 'required|date',
        ]);

        $base = $this->baseQuantity($school, $bundle);

        $resurtido->update([
            'cantidad_resurtido' => $request->cantidad_resurtido,
            'autorizado_por'     => $request->autorizado_por,
            'fecha'              => $request->fecha,
        ]);

        $cantidadFinal = $this->recalcularCadena($school, $bundle, $base);

        ActivityLog::log(
            'bundle',
            "Resurtido editado: \"{$bundle->name}\" ahora +{$request->cantidad_resurtido}" . ($request->autorizado_por ? " — Autorizado por: {$request->autorizado_por}" : ''),
            $school->id,
            '✏️'
        );

        return back()->with('success', "Resurtido actualizado. Total actual: {$cantidadFinal}.");
    }

    public function destroy(School $school, Bundle $bundle, BundleResurtido $resurtido)
    {
        if ($resurtido->school_id !== $school->id || $resurtido->bundle_id !== $bundle->id) {
            abort(404);
        }

        $detalle = "+{$resurtido->cantidad_resurtido} del " . $resurtido->fecha->format('d/m/Y');
        $base = $this->baseQuantity($school, $bundle);

        $resurtido->delete();

        $cantidadFinal = $this->recalcularCadena($school, $bundle, $base);

        ActivityLog::log(
            'bundle',
            "Resurtido eliminado: \"{$bundle->name}\" ({$detalle})",
            $school->id,
            '🗑️'
        );

        return back()->with('success', "Resurtido eliminado. Total actual: {$cantidadFinal}.");
    }

    /**
     * Cantidad que tenía el bundle antes de CUALQUIER resurtido, es decir la
     * cantidad actual menos la suma de todos los resurtidos existentes. Se usa
     * como ancla estable para recalcular la cadena, porque el cantidad_anterior
     * guardado en cada registro queda obsoleto en cuanto se edita o borra otro.
     */
    private function baseQuantity(School $school, Bundle $bundle): int
    {
        $quantityActual = $school->bundles()->where('bundle_id', $bundle->id)->first()->pivot->quantity ?? 0;
        $sumaResurtidos = BundleResurtido::where('school_id', $school->id)
            ->where('bundle_id', $bundle->id)
            ->sum('cantidad_resurtido');

        return $quantityActual - $sumaResurtidos;
    }

    /**
     * Recalcula cantidad_anterior/cantidad_nueva en orden cronológico de creación
     * (no por la fecha editable) a partir de la base estable, y actualiza la
     * cantidad del bundle en el colegio.
     */
    private function recalcularCadena(School $school, Bundle $bundle, int $base): int
    {
        $registros = BundleResurtido::where('school_id', $school->id)
            ->where('bundle_id', $bundle->id)
            ->orderBy('id')
            ->get();

        $cantidad = $base;
        foreach ($registros as $r) {
            $r->cantidad_anterior = $cantidad;
            $cantidad += $r->cantidad_resurtido;
            $r->cantidad_nueva = $cantidad;
            $r->save();
        }

        $school->bundles()->updateExistingPivot($bundle->id, ['quantity' => $cantidad]);

        return $cantidad;
    }
}
