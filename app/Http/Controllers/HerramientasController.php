<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ArchivoSI;
use App\Services\NotificationService;
use App\Services\ScriptReporteAccesos;
use App\Services\SectionNovedadesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HerramientasController extends Controller
{
    public function index()
    {
        $archivos = ArchivoSI::with('user')->latest()->get();
        if ($user = auth()->user()) {
            app(SectionNovedadesService::class)->consumeHerramientas($user);
        }
        return view('herramientas.index', compact('archivos'));
    }

    // ── Herramientas HTML ─────────────────────────────────────────────────────

    public function altasBundles()
    {
        return $this->servirHerramienta('altas-bundles.html');
    }

    public function formatoEntrega()
    {
        return view('herramientas.formato-entrega');
    }

    public function accesos()
    {
        return $this->servirHerramienta('accesos.html');
    }

    public function consultaAccesos()
    {
        return $this->servirHerramienta('consulta-accesos.html');
    }

    public function borrarUsuarios()
    {
        return $this->servirHerramienta('borrar-usuarios.html');
    }

    public function cambioUsuario()
    {
        return $this->servirHerramienta('cambio-usuario.html');
    }

    public function cambioPassword()
    {
        return $this->servirHerramienta('cambio-password.html');
    }

    public function eliminacionBundles()
    {
        return $this->servirHerramienta('eliminacion-bundles.html');
    }

    public function reporteAccesos()
    {
        return $this->servirHerramienta(ScriptReporteAccesos::ARCHIVO_HERRAMIENTA);
    }

    // ── Script de consola del Reporte de Accesos (solo admin) ─────────────────

    public function editarScriptReporte(ScriptReporteAccesos $script)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        return view('herramientas.script-reporte-accesos', ['actual' => $script->actual()]);
    }

    public function guardarScriptReporte(Request $request, ScriptReporteAccesos $script)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $data = $request->validate(['script' => 'required|string|max:200000'], [
            'script.required' => 'El script no puede quedar vacío. Si ya no se usa, elimínalo con el botón Eliminar.',
            'script.max'      => 'El script es demasiado largo.',
        ]);

        $script->guardar($data['script'], auth()->user());
        ActivityLog::log('herramienta', 'Script del Reporte de Accesos actualizado', null, '🛠️');

        return redirect()->route('herramientas.reporte-accesos.script')
            ->with('success', 'Script actualizado. Ya lo ven todos en la herramienta.');
    }

    public function eliminarScriptReporte(ScriptReporteAccesos $script)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $script->eliminar(auth()->user());
        ActivityLog::log('herramienta', 'Script del Reporte de Accesos eliminado', null, '🗑️');

        return redirect()->route('herramientas.reporte-accesos.script')
            ->with('success', 'Script eliminado. La herramienta mostrará un aviso en su lugar.');
    }

    public function restaurarScriptReporte(ScriptReporteAccesos $script)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $script->restaurar();
        ActivityLog::log('herramienta', 'Script del Reporte de Accesos restaurado al original', null, '↩️');

        return redirect()->route('herramientas.reporte-accesos.script')
            ->with('success', 'Se restauró el script original.');
    }

    public function vinculacionSso()
    {
        return $this->servirHerramienta('vinculacion-sso.html');
    }

    public function templateBienvenida()
    {
        return $this->servirHerramienta('template-bienvenida.html');
    }

    public function templateConfirmacion()
    {
        return $this->servirHerramienta('template-confirmacion.html');
    }

    private function servirHerramienta(string $archivo)
    {
        $base = realpath(resource_path('tools'));
        $path = realpath(resource_path("tools/{$archivo}"));

        abort_if(! $path || ! str_starts_with($path, $base . DIRECTORY_SEPARATOR), 404);

        $html = file_get_contents($path);

        // Reporte de Accesos: script vigente (editable por el admin) + acceso a editarlo
        if ($archivo === ScriptReporteAccesos::ARCHIVO_HERRAMIENTA) {
            $html = app(ScriptReporteAccesos::class)->aplicar($html);
            if (auth()->user()?->hasRole('admin')) {
                // Marcado con <!--sic-admin-->: "Descargar HTML" lo quita del reporte que se comparte
                $boton = '<!--sic-admin--><a href="' . e(route('herramientas.reporte-accesos.script')) . '" target="_blank" '
                    . 'class="btn btn-ghost" style="margin-top:8px;width:100%;justify-content:center;text-decoration:none">'
                    . '✏️ Editar script (admin)</a><!--/sic-admin-->';
                $html = preg_replace_callback(
                    '#<button type="button" class="btn btn-ghost" id="copyScriptBtn"[^>]*>.*?</button>#s',
                    fn ($m) => $m[0] . $boton,
                    $html,
                    1
                );
            }
        }
        $faviconUrl = asset('favicon.png') . '?v=3';
        $html = str_replace('href="/favicon.png?v=3"', 'href="' . e($faviconUrl) . '"', $html);

        return response()->make(
            $html,
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

        $registro = new ArchivoSI([
            'titulo'        => $data['titulo'],
            'descripcion'   => $data['descripcion'] ?? null,
            'archivo'       => $archivoPath,
            'archivo_nombre'=> $archivoNombre,
            'archivo_tipo'  => $archivoTipo,
        ]);
        $registro->user_id = auth()->id();
        $registro->save();

        app(NotificationService::class)->notifyArchivoSiUploaded($registro, false);

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

        app(NotificationService::class)->notifyArchivoSiUploaded($archivoSI, true);

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
