<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolFile;
use App\Traits\BloqueaColegioInactivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolFileController extends Controller
{
    use BloqueaColegioInactivo;

    public function index(School $school)
    {
        $files = $school->files()->with('uploader')->latest()->get();
        $consultor = $school->consultorDigital()->with('user')->first();
        $contactoConsultor = $consultor?->user ? [
            'nombre' => $consultor->user->name,
            'email'  => $consultor->user->email,
            'phone'  => $consultor->phone,
        ] : null;

        return view('repositorio.index', compact('school', 'files', 'consultor', 'contactoConsultor'));
    }

    public function store(Request $request, School $school)
    {
        if ($redirect = $this->bloqueadoPorInactivo($school)) {
            return $redirect;
        }
        abort_unless(auth()->user()->hasAnyRole(['admin', 'consultor_digital']), 403);

        $request->validate([
            'archivos'   => 'required|array|max:10',
            'archivos.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx',
        ], [
            'archivos.required'  => 'Selecciona al menos un archivo.',
            'archivos.*.max'     => 'Cada archivo no puede superar 10 MB.',
            'archivos.*.mimes'   => 'Solo se permiten PDF, Word o Excel.',
        ]);

        foreach ($request->file('archivos') as $archivo) {
            $ruta = $archivo->store("repositorio/{$school->id}", 'local');

            $school->files()->create([
                'uploaded_by' => auth()->id(),
                'nombre'      => basename($archivo->getClientOriginalName()),
                'ruta'        => $ruta,
                'mime'        => $archivo->getClientMimeType(),
                'tamano'      => $archivo->getSize(),
            ]);
        }

        return back()->with('success', 'Archivo(s) subido(s) correctamente.');
    }

    public function download(School $school, SchoolFile $file)
    {
        abort_unless($file->school_id === $school->id, 404);

        return Storage::disk('local')->download($file->ruta, $file->nombre);
    }

    public function destroy(School $school, SchoolFile $file)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'consultor_digital']), 403);
        abort_unless($file->school_id === $school->id, 404);

        Storage::disk('local')->delete($file->ruta);
        $file->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }
}
