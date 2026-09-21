<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ProduccionItem;
use App\Models\ProduccionMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProduccionController extends Controller
{
    public function index()
    {
        $meta = ProduccionMeta::with('cargadoPor')->first();

        $data = [];
        if ($meta) {
            $data = ProduccionItem::query()
                ->select('nivel', 'grado', 'colegio_id', 'colegio', 'tipo_pago', 'serie', 'empresa', 'titulo', 'codigo_isbn', 'codigo_gs1', 'cantidad')
                ->get()
                ->map(fn($i) => [
                    'n'    => $i->nivel,
                    'g'    => $i->grado,
                    'ci'   => $i->colegio_id,
                    'co'   => $i->colegio,
                    'tp'   => $i->tipo_pago,
                    's'    => $i->serie,
                    'e'    => $i->empresa,
                    't'    => $i->titulo,
                    'isbn' => $i->codigo_isbn,
                    'gs1'  => $i->codigo_gs1,
                    'c'    => (int) $i->cantidad,
                ]);
        }

        return view('produccion.index', compact('meta', 'data'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ], [
            'excel_file.mimes' => 'Solo se aceptan archivos Excel (.xlsx, .xls) o CSV.',
            'excel_file.max'   => 'El archivo no puede superar 20 MB.',
        ]);

        try {
            $file = $request->file('excel_file');
            $ext  = strtolower($file->getClientOriginalExtension());

            if ($ext === 'csv') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                $reader->setDelimiter(',');
                $spreadsheet = $reader->load($file->getPathname());
            } else {
                $spreadsheet = IOFactory::load($file->getPathname());
            }

            [$rows, $cols] = $this->detectarHojaDeDatos($spreadsheet);

            if ($rows === null) {
                return back()->with('error', 'No encontré una hoja con las columnas esperadas (Nivel, Colegio, Serie, Cantidad). Verifica el archivo.');
            }

            array_shift($rows); // encabezado

            $filas = [];
            foreach ($rows as $row) {
                $cantidad = isset($cols['cantidad']) ? (int) round((float) ($row[$cols['cantidad']] ?? 0)) : 0;
                $serie    = isset($cols['serie']) ? trim((string) ($row[$cols['serie']] ?? '')) : '';
                $colegio  = isset($cols['colegio']) ? trim((string) ($row[$cols['colegio']] ?? '')) : '';

                if ($serie === '' && $colegio === '' && $cantidad === 0) {
                    continue; // fila vacía
                }

                $filas[] = [
                    'nivel'       => isset($cols['nivel'])      ? trim((string) ($row[$cols['nivel']] ?? ''))      : null,
                    'grado'       => isset($cols['grado'])      ? trim((string) ($row[$cols['grado']] ?? ''))      : null,
                    'colegio_id'  => isset($cols['colegio_id']) ? trim((string) ($row[$cols['colegio_id']] ?? '')) : null,
                    'colegio'     => $colegio ?: null,
                    'tipo_pago'   => isset($cols['tipo_pago'])  ? trim((string) ($row[$cols['tipo_pago']] ?? ''))  : null,
                    'serie'       => $serie ?: null,
                    'empresa'     => isset($cols['empresa'])    ? trim((string) ($row[$cols['empresa']] ?? ''))   : null,
                    'titulo'      => isset($cols['titulo'])     ? trim((string) ($row[$cols['titulo']] ?? ''))    : null,
                    'codigo_isbn' => isset($cols['isbn'])       ? trim((string) ($row[$cols['isbn']] ?? ''))      : null,
                    'codigo_gs1'  => isset($cols['gs1'])        ? trim((string) ($row[$cols['gs1']] ?? ''))       : null,
                    'cantidad'    => $cantidad,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            if (empty($filas)) {
                return back()->with('error', 'El archivo no tiene filas de datos válidas.');
            }

            $nombreOriginal = $file->getClientOriginalName();
            $corte = null;
            if (preg_match('/(\d{1,2})[_\-](\d{1,2})[_\-](\d{4})/', $nombreOriginal, $m)) {
                $corte = sprintf('%02d/%02d/%04d', $m[1], $m[2], $m[3]);
            }

            DB::transaction(function () use ($filas, $nombreOriginal, $corte) {
                ProduccionItem::query()->delete();
                foreach (array_chunk($filas, 500) as $chunk) {
                    ProduccionItem::insert($chunk);
                }

                ProduccionMeta::query()->delete();
                ProduccionMeta::create([
                    'archivo_nombre' => pathinfo($nombreOriginal, PATHINFO_FILENAME),
                    'corte'          => $corte,
                    'total_filas'    => count($filas),
                    'cargado_por_id' => auth()->id(),
                    'cargado_en'     => now(),
                ]);
            });

            ActivityLog::log('produccion', "Dashboard de Producción cargado desde \"{$nombreOriginal}\" (" . count($filas) . ' filas)', null, '📈');

            return redirect()->route('produccion.index')
                ->with('success', '✅ Dashboard de producción actualizado con ' . number_format(count($filas)) . ' filas.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    public function destroy()
    {
        DB::transaction(function () {
            ProduccionItem::query()->delete();
            ProduccionMeta::query()->delete();
        });

        ActivityLog::log('produccion', 'Dashboard de Producción borrado', null, '🗑️');

        return redirect()->route('produccion.index')->with('success', 'Dashboard de producción borrado.');
    }

    /**
     * Un Excel de producción puede traer varias hojas (ej. un resumen/pivote
     * además de la hoja con el detalle). Buscamos la primera hoja cuyo
     * encabezado tenga al menos Nivel, Colegio, Serie y Cantidad.
     */
    private function detectarHojaDeDatos($spreadsheet): array
    {
        $NIVEL_KEYS      = ['nivel'];
        $GRADO_KEYS      = ['grado'];
        $COLEGIOID_KEYS  = ['colegioid', 'idcolegio', 'clavecolegio'];
        $COLEGIO_KEYS    = ['colegio', 'nombrecolegio', 'escuela'];
        $TIPOPAGO_KEYS   = ['tipopago', 'tipodepago', 'pago'];
        $SERIE_KEYS      = ['serie'];
        $EMPRESA_KEYS    = ['empresa'];
        $TITULO_KEYS     = ['titulo'];
        $ISBN_KEYS       = ['codigoisbn', 'isbn'];
        $GS1_KEYS        = ['codigogs1', 'gs1'];
        $CANTIDAD_KEYS   = ['cantidad', 'sumadecantidad', 'piezas'];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $rows   = $sheet->toArray(null, true, true, false);
            $header = $rows[0] ?? [];
            if (!$header) continue;

            $cols = [];
            foreach ($header as $ci => $cell) {
                $h = $this->normalizar($cell);
                if ($h === '') continue;
                if (!isset($cols['nivel'])      && in_array($h, $NIVEL_KEYS, true))     $cols['nivel'] = $ci;
                if (!isset($cols['grado'])      && in_array($h, $GRADO_KEYS, true))     $cols['grado'] = $ci;
                if (!isset($cols['colegio_id']) && in_array($h, $COLEGIOID_KEYS, true)) $cols['colegio_id'] = $ci;
                if (!isset($cols['colegio'])    && in_array($h, $COLEGIO_KEYS, true))   $cols['colegio'] = $ci;
                if (!isset($cols['tipo_pago'])  && in_array($h, $TIPOPAGO_KEYS, true))  $cols['tipo_pago'] = $ci;
                if (!isset($cols['serie'])      && in_array($h, $SERIE_KEYS, true))     $cols['serie'] = $ci;
                if (!isset($cols['empresa'])    && in_array($h, $EMPRESA_KEYS, true))   $cols['empresa'] = $ci;
                if (!isset($cols['titulo'])     && in_array($h, $TITULO_KEYS, true))    $cols['titulo'] = $ci;
                if (!isset($cols['isbn'])       && in_array($h, $ISBN_KEYS, true))      $cols['isbn'] = $ci;
                if (!isset($cols['gs1'])        && in_array($h, $GS1_KEYS, true))       $cols['gs1'] = $ci;
                if (!isset($cols['cantidad'])   && in_array($h, $CANTIDAD_KEYS, true))  $cols['cantidad'] = $ci;
            }

            $tieneMinimos = isset($cols['nivel'], $cols['colegio'], $cols['serie'], $cols['cantidad']);
            if ($tieneMinimos) {
                return [$rows, $cols];
            }
        }

        return [null, null];
    }

    private function normalizar($valor): string
    {
        $s = (string) $valor;
        $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
        $s = preg_replace('/\p{Mn}/u', '', $s);
        $s = mb_strtolower(trim($s));
        return preg_replace('/[^a-z0-9]/', '', $s);
    }
}
