<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SeguimientoRecurso;
use App\Models\SeguimientoRecursoAudiencia;
use App\Services\SeguimientoRecursoPreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeguimientoRecursoController extends Controller
{
    public const CATEGORIAS_POR_PLATAFORMA = [
        'meta'          => ['manuales'],
        'global-educa'  => ['formatos', 'manuales'],
        'frances'       => ['formatos', 'manuales'],
        'red-roo-reads' => ['formatos', 'manuales'],
        'equipamiento'  => ['formatos', 'manuales'],
    ];

    public function store(Request $request, string $plataforma)
    {
        abort_unless(SeguimientoRecurso::puedeGestionar(auth()->user()), 403);

        $categoriasPermitidas = self::CATEGORIAS_POR_PLATAFORMA[$plataforma] ?? [];
        abort_if(empty($categoriasPermitidas), 404);

        $extensiones = implode(',', SeguimientoRecurso::EXTENSIONES_PERMITIDAS);
        $audienciasValidas = implode(',', array_keys(SeguimientoRecurso::AUDIENCIAS));

        $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria'   => 'required|in:' . implode(',', $categoriasPermitidas),
            'audiencia'   => 'required|array|min:1',
            'audiencia.*' => 'in:' . $audienciasValidas,
            'archivo'     => "required|file|max:25600|extensions:{$extensiones}",
        ], [
            'titulo.required'    => 'El nombre del archivo es obligatorio.',
            'audiencia.required' => 'Selecciona al menos un rol con acceso.',
            'audiencia.min'      => 'Selecciona al menos un rol con acceso.',
            'archivo.required'   => 'Debes seleccionar un archivo para subir.',
            'archivo.max'        => 'El archivo no puede pesar más de 25 MB.',
            'archivo.extensions' => 'Formato no permitido. Se aceptan PDF, Excel, Word, PowerPoint, imágenes, TXT y comprimidos.',
        ]);

        $file = $request->file('archivo');
        $categoria = $request->input('categoria');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $ruta = $file->store("seguimiento-recursos/{$plataforma}/{$categoria}", 'local');

        try {
            DB::transaction(function () use ($request, $plataforma, $categoria, $file, $extension, $ruta) {
                $recurso = SeguimientoRecurso::create([
                    'plataforma'     => $plataforma,
                    'categoria'      => $categoria,
                    'titulo'         => $request->titulo,
                    'descripcion'    => $request->descripcion ?: null,
                    'archivo'        => $ruta,
                    'archivo_nombre' => $file->getClientOriginalName(),
                    'extension'      => $extension,
                    'mime_type'      => $file->getMimeType() ?: $file->getClientMimeType(),
                    'tamano'         => $file->getSize() ?: 0,
                    'user_id'        => auth()->id(),
                ]);

                $this->guardarAudiencia($recurso, $request->audiencia);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($ruta);
            throw $e;
        }

        $etiqueta = $categoria === 'formatos' ? 'Formato' : 'Manual';
        ActivityLog::log('seguimiento_recurso', "{$etiqueta} \"{$request->titulo}\" subido en {$plataforma}", null, '📁');

        return redirect()
            ->route('seguimiento.show', $plataforma)
            ->with('success', "{$etiqueta} subido correctamente.")
            ->with('tab', $categoria);
    }

    public function update(Request $request, SeguimientoRecurso $recurso)
    {
        abort_unless(SeguimientoRecurso::puedeGestionar(auth()->user()), 403);

        $extensiones = implode(',', SeguimientoRecurso::EXTENSIONES_PERMITIDAS);
        $audienciasValidas = implode(',', array_keys(SeguimientoRecurso::AUDIENCIAS));

        $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'audiencia'   => 'required|array|min:1',
            'audiencia.*' => 'in:' . $audienciasValidas,
            'archivo'     => "nullable|file|max:25600|extensions:{$extensiones}",
        ], [
            'titulo.required'    => 'El nombre del archivo es obligatorio.',
            'audiencia.required' => 'Selecciona al menos un rol con acceso.',
            'audiencia.min'      => 'Selecciona al menos un rol con acceso.',
            'archivo.max'        => 'El archivo no puede pesar más de 25 MB.',
            'archivo.extensions' => 'Formato no permitido. Se aceptan PDF, Excel, Word, PowerPoint, imágenes, TXT y comprimidos.',
        ]);

        $archivoAnterior = $recurso->archivo;
        $nuevaRuta = null;

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $nuevaRuta = $file->store("seguimiento-recursos/{$recurso->plataforma}/{$recurso->categoria}", 'local');
        }

        try {
            DB::transaction(function () use ($request, $recurso, $nuevaRuta) {
                $datos = [
                    'titulo'      => $request->titulo,
                    'descripcion' => $request->descripcion ?: null,
                ];

                if ($nuevaRuta !== null) {
                    $file = $request->file('archivo');
                    $datos['archivo'] = $nuevaRuta;
                    $datos['archivo_nombre'] = $file->getClientOriginalName();
                    $datos['extension'] = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                    $datos['mime_type'] = $file->getMimeType() ?: $file->getClientMimeType();
                    $datos['tamano'] = $file->getSize() ?: 0;
                }

                $recurso->update($datos);
                $this->guardarAudiencia($recurso, $request->audiencia);
            });
        } catch (\Throwable $e) {
            if ($nuevaRuta !== null) {
                Storage::disk('local')->delete($nuevaRuta);
            }
            throw $e;
        }

        if ($nuevaRuta !== null && $archivoAnterior && Storage::disk('local')->exists($archivoAnterior)) {
            Storage::disk('local')->delete($archivoAnterior);
        }

        ActivityLog::log('seguimiento_recurso', "Recurso \"{$recurso->titulo}\" actualizado", null, '✏️');

        return redirect()
            ->route('seguimiento.show', $recurso->plataforma)
            ->with('success', 'Archivo actualizado correctamente.')
            ->with('tab', $recurso->categoria);
    }

    public function updateAudiencia(Request $request, SeguimientoRecurso $recurso)
    {
        abort_unless(SeguimientoRecurso::puedeGestionar(auth()->user()), 403);

        $request->validate([
            'audiencia'   => 'required|array|min:1',
            'audiencia.*' => 'in:' . implode(',', array_keys(SeguimientoRecurso::AUDIENCIAS)),
        ], [
            'audiencia.required' => 'Selecciona al menos un rol con acceso.',
            'audiencia.min'      => 'Selecciona al menos un rol con acceso.',
        ]);

        $this->guardarAudiencia($recurso, $request->audiencia);

        ActivityLog::log('seguimiento_recurso', "Audiencia de \"{$recurso->titulo}\" actualizada", null, '👥');

        return redirect()
            ->route('seguimiento.show', $recurso->plataforma)
            ->with('success', 'Permisos de visualización actualizados.')
            ->with('tab', $recurso->categoria);
    }

    public function destroy(SeguimientoRecurso $recurso)
    {
        abort_unless(SeguimientoRecurso::puedeGestionar(auth()->user()), 403);

        if ($recurso->archivo && Storage::disk('local')->exists($recurso->archivo)) {
            Storage::disk('local')->delete($recurso->archivo);
        }

        $plataforma = $recurso->plataforma;
        $categoria = $recurso->categoria;
        $titulo = $recurso->titulo;
        $recurso->delete();

        ActivityLog::log('seguimiento_recurso', "Recurso \"{$titulo}\" eliminado de {$plataforma}", null, '🗑️');

        return redirect()
            ->route('seguimiento.show', $plataforma)
            ->with('success', 'Archivo eliminado correctamente.')
            ->with('tab', $categoria);
    }

    public function preview(Request $request, SeguimientoRecurso $recurso, SeguimientoRecursoPreviewService $previewService)
    {
        abort_unless($recurso->esVisiblePara(auth()->user()), 403);
        abort_unless(Storage::disk('local')->exists($recurso->archivo), 404);

        $fullPath = Storage::disk('local')->path($recurso->archivo);
        $ext = strtolower((string) $recurso->extension);
        $mode = $request->query('mode');

        if ($mode === 'sheet' && in_array($ext, ['xlsx', 'xls', 'csv', 'ods'], true)) {
            return response()->json($previewService->previewSpreadsheet($fullPath, $ext));
        }

        if ($mode === 'doc' && in_array($ext, ['docx', 'doc', 'odt'], true)) {
            return response()->json($previewService->previewDocument($fullPath, $ext));
        }

        if ($mode === 'slides' && in_array($ext, ['pptx', 'ppt', 'odp'], true)) {
            return response()->json($previewService->previewPresentation($fullPath, $ext));
        }

        if ($mode === 'archive' && in_array($ext, ['zip', 'rar', '7z'], true)) {
            return response()->json($previewService->previewArchive($fullPath, $ext));
        }

        $safeName = $this->nombreDescarga($recurso);
        $mime = $this->mimeInlinePara($ext, $recurso->mime_type);
        // Un .txt con HTML adentro se detecta como text/html y se ejecutaría en el
        // iframe de la vista previa: siempre se muestra como texto plano
        if ($ext === 'txt' || str_contains($mime, 'html') || str_contains($mime, 'xml')) {
            $mime = 'text/plain; charset=UTF-8';
        }

        $headers = [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline; filename="' . addslashes($safeName) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if (str_starts_with($mime, 'text/')) {
            // (No a PDF: Chrome no muestra PDFs en documentos con sandbox)
            $headers['Content-Security-Policy'] = 'sandbox';
        }

        return response()->file($fullPath, $headers);
    }

    public function download(SeguimientoRecurso $recurso)
    {
        abort_unless($recurso->esVisiblePara(auth()->user()), 403);
        abort_unless(Storage::disk('local')->exists($recurso->archivo), 404);

        $fullPath = Storage::disk('local')->path($recurso->archivo);

        return response()->download($fullPath, $this->nombreDescarga($recurso));
    }

    private function mimeInlinePara(string $ext, ?string $storedMime): string
    {
        return match ($ext) {
            'pdf'         => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            default       => $storedMime ?: 'application/octet-stream',
        };
    }

    private function guardarAudiencia(SeguimientoRecurso $recurso, array $roles): void
    {
        $roles = in_array('todos', $roles, true) ? ['todos'] : array_values(array_unique($roles));

        $recurso->audiencias()->delete();
        foreach ($roles as $rol) {
            SeguimientoRecursoAudiencia::create([
                'seguimiento_recurso_id' => $recurso->id,
                'rol'                    => $rol,
            ]);
        }
    }

    private function nombreDescarga(SeguimientoRecurso $recurso): string
    {
        $slug = Str::slug($recurso->titulo, '_');
        if ($slug !== '' && $recurso->extension !== '') {
            return "{$slug}.{$recurso->extension}";
        }

        return $recurso->archivo_nombre ?: ('archivo.' . ($recurso->extension ?: 'bin'));
    }
}
