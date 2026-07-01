<?php

namespace App\Http\Controllers;

use App\Models\ArchivoSI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HerramientasController extends Controller
{
    public function index()
    {
        $archivos = ArchivoSI::with('user')->latest()->get();
        return view('herramientas.index', compact('archivos'));
    }

    // ── Herramientas HTML ─────────────────────────────────────────────────────

    public function altasBundles()
    {
        return $this->servirHerramienta('altas-bundles.html');
    }

    public function accesos()
    {
        return $this->servirHerramienta('accesos.html');
    }

    public function consultaAccesos()
    {
        return $this->servirHerramienta('consulta-accesos.html');
    }

    private function servirHerramienta(string $archivo)
    {
        $base = realpath(resource_path('tools'));
        $path = realpath(resource_path("tools/{$archivo}"));

        abort_if(! $path || ! str_starts_with($path, $base . DIRECTORY_SEPARATOR), 404);

        return response()->make(
            file_get_contents($path),
            200,
            ['Content-Type' => 'text/html; charset=utf-8']
        );
    }

    // ── Archivos SI Colegios ──────────────────────────────────────────────────

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo'     => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,xlsx,xls,docx,doc',
        ], [
            'titulo.required'   => 'El título es obligatorio.',
            'archivo.max'       => 'El archivo no puede superar 10 MB.',
            'archivo.mimes'     => 'Solo se permiten PDF, imágenes, Excel o Word.',
        ]);

        $archivoPath = null;
        $archivoNombre = null;
        $archivoTipo = null;

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $archivoNombre = basename($file->getClientOriginalName());
            $mime = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $archivoTipo = 'image';
            } elseif ($mime === 'application/pdf') {
                $archivoTipo = 'pdf';
            } else {
                $archivoTipo = 'other';
            }

            $archivoPath = $file->store('archivos-si', 'public');
        }

        ArchivoSI::create([
            'titulo'        => $data['titulo'],
            'descripcion'   => $data['descripcion'] ?? null,
            'archivo'       => $archivoPath,
            'archivo_nombre'=> $archivoNombre,
            'archivo_tipo'  => $archivoTipo,
            'user_id'       => auth()->id(),
        ]);

        return redirect()->route('herramientas.index')
            ->with('success', 'Archivo publicado correctamente.');
    }

    public function update(Request $request, ArchivoSI $archivoSI)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo'     => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,xlsx,xls,docx,doc',
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'archivo.max'     => 'El archivo no puede superar 10 MB.',
            'archivo.mimes'   => 'Solo se permiten PDF, imágenes, Excel o Word.',
        ]);

        $updates = [
            'titulo'      => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
        ];

        if ($request->hasFile('archivo')) {
            $file      = $request->file('archivo');
            $mime      = $file->getMimeType();
            $rutaVieja = $archivoSI->archivo;

            // Guardar primero; solo borrar el viejo si el store tuvo éxito
            $nuevaRuta = $file->store('archivos-si', 'public');

            $updates['archivo_nombre'] = basename($file->getClientOriginalName());
            $updates['archivo_tipo']   = str_starts_with($mime, 'image/') ? 'image'
                                        : ($mime === 'application/pdf'    ? 'pdf' : 'other');
            $updates['archivo']        = $nuevaRuta;

            if ($rutaVieja) {
                Storage::disk('public')->delete($rutaVieja);
            }
        }

        $archivoSI->update($updates);

        return redirect()->route('herramientas.index')
            ->with('success', 'Archivo actualizado correctamente.');
    }

    public function destroy(ArchivoSI $archivoSI)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        if ($archivoSI->archivo) {
            Storage::disk('public')->delete($archivoSI->archivo);
        }

        $archivoSI->delete();

        return redirect()->route('herramientas.index')
            ->with('success', 'Archivo eliminado.');
    }
}
