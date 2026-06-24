<?php

namespace App\Http\Controllers;

use App\Models\Comunicado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComunicadoController extends Controller
{
    public function index()
    {
        $activos = Comunicado::activos()->with('user')->latest()->get();
        $pasados = Comunicado::pasados()->with('user')->latest()->get();
        return view('tablero.index', compact('activos', 'pasados'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $request->validate([
            'titulo'        => 'required|string|max:255',
            'descripcion'   => 'required|string',
            'archivo'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'fecha_termino' => 'nullable|date|after:today',
        ], [
            'titulo.required'      => 'El título es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'archivo.mimes'        => 'Solo se permiten PDF, JPG o PNG (máx. 5 MB).',
            'fecha_termino.after'  => 'La fecha de término debe ser posterior a hoy.',
        ]);

        $data = [
            'titulo'        => $request->titulo,
            'descripcion'   => $request->descripcion,
            'fecha_termino' => $request->fecha_termino ?: null,
            'user_id'       => auth()->id(),
        ];

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $data['archivo']        = $file->store('comunicados', 'public');
            $data['archivo_nombre'] = $file->getClientOriginalName();
            $data['archivo_tipo']   = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'pdf';
        }

        Comunicado::create($data);

        return redirect()->route('tablero.index')->with('success', 'Comunicado publicado correctamente.');
    }

    public function destroy(Comunicado $comunicado)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        if ($comunicado->archivo) {
            Storage::disk('public')->delete($comunicado->archivo);
        }

        $comunicado->delete();

        return redirect()->route('tablero.index')->with('success', 'Comunicado eliminado.');
    }
}
