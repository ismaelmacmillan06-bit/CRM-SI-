<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Comunicado;
use App\Models\ComunicadoAudiencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComunicadoController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();
        $base = fn() => Comunicado::visiblePara($usuario)->with(['user', 'audiencias']);

        $activos = $base()->activos()->latest()->get();
        $pasados = $base()->pasados()->latest()->get();

        return view('tablero.index', compact('activos', 'pasados'));
    }

    public function store(Request $request)
    {
        abort_unless(Comunicado::puedePublicar(auth()->user()), 403);

        $request->validate([
            'titulo'        => 'required|string|max:255',
            'descripcion'   => 'required|string',
            'audiencia'     => 'required|array|min:1',
            'audiencia.*'   => 'in:' . implode(',', array_keys(Comunicado::AUDIENCIAS)),
            'archivo'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'enlace'        => 'nullable|url|max:2048',
            'enlace_texto'  => 'nullable|string|max:100',
            'fecha_termino' => 'nullable|date|after:today',
        ], [
            'titulo.required'      => 'El título es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'audiencia.required'   => 'Selecciona a quién va dirigido el comunicado.',
            'audiencia.min'        => 'Selecciona a quién va dirigido el comunicado.',
            'archivo.mimes'        => 'Solo se permiten PDF, JPG o PNG (máx. 5 MB).',
            'enlace.url'           => 'El enlace debe ser una URL válida (ej: https://...).',
            'fecha_termino.after'  => 'La fecha de término debe ser posterior a hoy.',
        ]);

        $data = [
            'titulo'        => $request->titulo,
            'descripcion'   => $request->descripcion,
            'enlace'        => $request->enlace ?: null,
            'enlace_texto'  => $request->enlace_texto ?: null,
            'fecha_termino' => $request->fecha_termino ?: null,
            'user_id'       => auth()->id(),
        ];

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $data['archivo']        = $file->store('comunicados', 'public');
            $data['archivo_nombre'] = $file->getClientOriginalName();
            $data['archivo_tipo']   = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'pdf';
        }

        DB::transaction(function () use ($data, $request) {
            $comunicado = Comunicado::create($data);
            $this->guardarAudiencia($comunicado, $request->audiencia);
        });

        ActivityLog::log('comunicado', "Comunicado \"{$request->titulo}\" publicado", null, '📢');

        return redirect()->route('tablero.index')->with('success', 'Comunicado publicado correctamente.');
    }

    public function updateAudiencia(Request $request, Comunicado $comunicado)
    {
        abort_unless(Comunicado::puedePublicar(auth()->user()), 403);

        $request->validate([
            'audiencia'   => 'required|array|min:1',
            'audiencia.*' => 'in:' . implode(',', array_keys(Comunicado::AUDIENCIAS)),
        ], [
            'audiencia.required' => 'Selecciona a quién va dirigido el comunicado.',
            'audiencia.min'      => 'Selecciona a quién va dirigido el comunicado.',
        ]);

        $this->guardarAudiencia($comunicado, $request->audiencia);

        ActivityLog::log('comunicado', "Audiencia del comunicado \"{$comunicado->titulo}\" actualizada", null, '👥');

        return redirect()->route('tablero.index')->with('success', 'Audiencia actualizada correctamente.');
    }

    public function destroy(Comunicado $comunicado)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        if ($comunicado->archivo) {
            Storage::disk('public')->delete($comunicado->archivo);
        }

        $titulo = $comunicado->titulo;
        $comunicado->delete();

        ActivityLog::log('comunicado', "Comunicado \"{$titulo}\" eliminado", null, '🗑️');

        return redirect()->route('tablero.index')->with('success', 'Comunicado eliminado.');
    }

    // Si se marca "Todos", la audiencia es solo esa (no tiene sentido combinarla)
    private function guardarAudiencia(Comunicado $comunicado, array $roles): void
    {
        $roles = in_array('todos', $roles, true) ? ['todos'] : array_values(array_unique($roles));

        $comunicado->audiencias()->delete();
        foreach ($roles as $rol) {
            ComunicadoAudiencia::create(['comunicado_id' => $comunicado->id, 'rol' => $rol]);
        }
    }
}
