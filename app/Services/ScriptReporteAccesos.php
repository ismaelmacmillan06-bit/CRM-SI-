<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Script de consola de la herramienta "Reporte de Accesos".
 *
 * El script original vive dentro de resources/tools/reporte-accesos.html. Si el admin lo
 * edita o lo elimina desde el CRM, el cambio se guarda en storage (no en el repo ni en la
 * base de datos), así que actualizarlo no requiere subir código ni migraciones.
 */
class ScriptReporteAccesos
{
    public const ARCHIVO_HERRAMIENTA = 'reporte-accesos.html';
    private const RUTA = 'herramientas/reporte-accesos-script.json';
    private const PATRON_PRE = '#(<pre id="scriptCode">)(.*?)(</pre>)#s';
    public const AVISO_SIN_SCRIPT = 'No hay script disponible por ahora. Pide al administrador que lo cargue.';

    private function disco()
    {
        return Storage::disk('local');
    }

    /** Script original, tal como viene en la herramienta. */
    public function original(): string
    {
        $html = file_get_contents(resource_path('tools/' . self::ARCHIVO_HERRAMIENTA));

        return preg_match(self::PATRON_PRE, $html, $m)
            ? html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : '';
    }

    /**
     * Estado actual: 'original' (sin cambios), 'editado' o 'eliminado'.
     * @return array{estado:string, script:string, usuario:?string, fecha:?string}
     */
    public function actual(): array
    {
        $guardado = null;
        if ($this->disco()->exists(self::RUTA)) {
            $guardado = json_decode((string) $this->disco()->get(self::RUTA), true);
        }

        if (!is_array($guardado) || !array_key_exists('script', $guardado)) {
            return ['estado' => 'original', 'script' => $this->original(), 'usuario' => null, 'fecha' => null];
        }

        $script = (string) $guardado['script'];

        return [
            'estado'  => trim($script) === '' ? 'eliminado' : 'editado',
            'script'  => $script,
            'usuario' => $guardado['usuario'] ?? null,
            'fecha'   => $guardado['fecha'] ?? null,
        ];
    }

    public function guardar(string $script, User $user): void
    {
        $this->escribir(str_replace("\r\n", "\n", $script), $user);
    }

    /** Elimina el script: la herramienta muestra un aviso en lugar del código. */
    public function eliminar(User $user): void
    {
        $this->escribir('', $user);
    }

    /** Vuelve al script original de la herramienta. */
    public function restaurar(): void
    {
        $this->disco()->delete(self::RUTA);
    }

    /** Pone el script vigente dentro del HTML de la herramienta. */
    public function aplicar(string $html): string
    {
        $actual = $this->actual();
        if ($actual['estado'] === 'original') {
            return $html;
        }

        $texto = $actual['estado'] === 'eliminado' ? '// ' . self::AVISO_SIN_SCRIPT : $actual['script'];
        $html = preg_replace_callback(self::PATRON_PRE, fn ($m) => $m[1] . e($texto) . $m[3], $html, 1);

        if ($actual['estado'] === 'eliminado') {
            $html = str_replace(
                'id="copyScriptBtn">📋 Copiar script',
                'id="copyScriptBtn" disabled style="opacity:.5;cursor:not-allowed">📋 Sin script disponible',
                $html
            );
        }

        return $html;
    }

    private function escribir(string $script, User $user): void
    {
        $this->disco()->put(self::RUTA, json_encode([
            'script'  => $script,
            'usuario' => $user->name,
            'fecha'   => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
