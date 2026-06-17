<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\TareaSI;
use App\Models\TareaColegio;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    public function index(Request $request)
    {
        $tareas  = TareaSI::with('creator')->latest()->get();
        $tarea   = $request->tarea_id ? TareaSI::find($request->tarea_id) : $tareas->first();
        $schools = School::orderBy('name')->get();

        // Mapa school_id → estado para la tarea activa
        $statusMap = [];
        if ($tarea) {
            $statusMap = TareaColegio::where('tarea_si_id', $tarea->id)
                ->pluck('status', 'school_id')
                ->toArray();
        }

        return view('tareas.index', compact('tareas', 'tarea', 'schools', 'statusMap'));
    }

    public function store(Request $request)
    {
        $request->validate(['titulo' => 'required|string|max:255']);

        $tarea = TareaSI::create([
            'titulo'      => $request->titulo,
            'descripcion' => $request->descripcion,
            'created_by'  => auth()->id(),
        ]);

        // Crear entrada pendiente para cada colegio existente
        $schoolIds = School::pluck('id');
        $rows = $schoolIds->map(fn($id) => [
            'tarea_si_id' => $tarea->id,
            'school_id'   => $id,
            'status'      => 'pendiente',
            'updated_by'  => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ])->toArray();

        TareaColegio::insert($rows);

        return redirect()->route('tareas.index', ['tarea_id' => $tarea->id])
            ->with('success', "Tarea \"{$tarea->titulo}\" creada para {$schoolIds->count()} colegios.");
    }

    public function updateStatus(Request $request, TareaSI $tarea, School $school)
    {
        $request->validate(['status' => 'required|in:pendiente,en_proceso,realizada']);

        TareaColegio::updateOrCreate(
            ['tarea_si_id' => $tarea->id, 'school_id' => $school->id],
            ['status' => $request->status, 'updated_by' => auth()->id()]
        );

        return response()->json(['ok' => true, 'status' => $request->status]);
    }

    public function destroy(TareaSI $tarea)
    {
        $tarea->delete();
        return redirect()->route('tareas.index')->with('success', 'Tarea eliminada.');
    }
}
