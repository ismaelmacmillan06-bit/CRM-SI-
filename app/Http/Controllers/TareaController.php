<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\TareaSI;
use App\Models\TareaColegio;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TareaController extends Controller
{
    public function index(Request $request)
    {
        $user   = auth()->user();
        $tareas = TareaSI::visiblePara($user)->with('creator')->latest()->get();
        $tarea  = $request->tarea_id ? $tareas->firstWhere('id', (int) $request->tarea_id) : $tareas->first();

        $schools = School::with('schoolConsultants.consultant.user')->orderBy('name');
        $statusMap = [];
        $marcables = [];

        if ($tarea) {
            $statusMap = TareaColegio::where('tarea_si_id', $tarea->id)
                ->pluck('status', 'school_id')
                ->toArray();
            $marcables = $tarea->colegiosMarcables($user);

            if ($tarea->esAcademica()) {
                // Solo los colegios a los que se dirigió la tarea; ECA/ELT ven solo los suyos
                $schools->whereIn('id', array_keys($statusMap));
                if ($marcables !== null && !$user->hasAnyRole(['coordinador', 'representante_ventas'])) {
                    $schools->whereIn('id', $marcables);
                }
            }
        }

        $schools = $schools->get();
        $audienciasPublicables = TareaSI::audienciasQuePuedePublicar($user);

        return view('tareas.index', compact('tareas', 'tarea', 'schools', 'statusMap', 'marcables', 'audienciasPublicables'));
    }

    public function store(Request $request)
    {
        $permitidas = TareaSI::audienciasQuePuedePublicar(auth()->user());
        abort_unless($permitidas, 403, 'No tienes permisos para publicar tareas.');

        $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'audiencia'   => ['nullable', Rule::in($permitidas)],
        ]);

        $audiencia = $request->audiencia ?: $permitidas[0];

        $tarea = TareaSI::create([
            'titulo'      => $request->titulo,
            'descripcion' => $request->descripcion,
            'audiencia'   => $audiencia,
            'created_by'  => auth()->id(),
        ]);

        // Digital: todos los colegios (como siempre). ECA/ELT: colegios activos
        // que tienen asignado un consultor de esa área.
        $schoolIds = $tarea->esAcademica()
            ? School::noInactivos()
                ->whereHas('schoolConsultants', fn ($q) => $q->whereIn('role', $tarea->rolesColegio()))
                ->pluck('id')
            : School::pluck('id');

        $rows = $schoolIds->map(fn($id) => [
            'tarea_si_id' => $tarea->id,
            'school_id'   => $id,
            'status'      => 'pendiente',
            'updated_by'  => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ])->toArray();

        foreach (array_chunk($rows, 500) as $lote) {
            TareaColegio::insert($lote);
        }

        $para = TareaSI::AUDIENCIAS[$audiencia];
        ActivityLog::log('tarea', "Tarea \"{$tarea->titulo}\" creada para {$para} ({$schoolIds->count()} colegios)", null, '✅');

        return redirect()->route('tareas.index', ['tarea_id' => $tarea->id])
            ->with('success', "Tarea \"{$tarea->titulo}\" publicada para {$para} en {$schoolIds->count()} colegios.");
    }

    public function update(Request $request, TareaSI $tarea)
    {
        abort_unless($tarea->puedeGestionar(auth()->user()), 403, 'No tienes permisos para editar esta tarea.');

        $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
        ]);

        $tarea->update([
            'titulo'      => $request->titulo,
            'descripcion' => $request->descripcion,
        ]);

        ActivityLog::log('tarea', "Tarea \"{$tarea->titulo}\" editada", null, '✏️');

        return redirect()->route('tareas.index', ['tarea_id' => $tarea->id])
            ->with('success', "Tarea \"{$tarea->titulo}\" actualizada correctamente.");
    }

    public function updateStatus(Request $request, TareaSI $tarea, School $school)
    {
        $user = auth()->user();
        abort_unless(TareaSI::visiblePara($user)->whereKey($tarea->id)->exists(), 403, 'No tienes acceso a esta tarea.');

        $marcables = $tarea->colegiosMarcables($user);
        abort_unless($marcables === null || in_array($school->id, $marcables, true), 403, 'No puedes cambiar el estado de este colegio.');

        $request->validate(['status' => 'required|in:pendiente,en_proceso,realizada']);

        if ($tarea->esAcademica()) {
            // Las tareas académicas solo aplican a los colegios a los que se dirigieron
            $fila = TareaColegio::where('tarea_si_id', $tarea->id)->where('school_id', $school->id)->first();
            abort_unless($fila, 404, 'Este colegio no forma parte de la tarea.');
            $fila->update(['status' => $request->status, 'updated_by' => $user->id]);
        } else {
            TareaColegio::updateOrCreate(
                ['tarea_si_id' => $tarea->id, 'school_id' => $school->id],
                ['status' => $request->status, 'updated_by' => $user->id]
            );
        }

        return response()->json(['ok' => true, 'status' => $request->status]);
    }

    public function destroy(TareaSI $tarea)
    {
        abort_unless($tarea->puedeGestionar(auth()->user()), 403, 'No tienes permisos para eliminar esta tarea.');

        $titulo = $tarea->titulo;
        $tarea->delete();
        ActivityLog::log('tarea', "Tarea \"{$titulo}\" eliminada", null, '🗑️');
        return redirect()->route('tareas.index')->with('success', 'Tarea eliminada.');
    }
}
