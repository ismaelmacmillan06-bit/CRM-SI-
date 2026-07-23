<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bundle;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BundleController extends Controller
{
    public function index()
    {
        $bundles = Bundle::orderBy('type')->orderBy('serie')->orderBy('level')->orderBy('grade')->get();
        $series  = Bundle::select('serie', 'type')->distinct()->orderBy('type')->orderBy('serie')->get();
        $tipos   = Bundle::select('type')->distinct()->pluck('type');
        return view('bundles.index', compact('bundles', 'series', 'tipos'));
    }

    public function create()
    {
        return view('bundles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'serie' => 'required|string|max:255',
            'name'  => 'required|string|max:255',
            'grade' => 'nullable|string|max:50',
            'level' => 'nullable|string|max:100',
            'role'  => 'required|in:student,teacher',
            'type'  => 'required|in:ELT,Plan Lector,Imagina,Wikids,Pienso Contigo,Complemento,Entrelineas,Palabrario,Frances',
        ]);

        $bundle = Bundle::create($request->all());

        ActivityLog::log('bundle', "Bundle \"{$bundle->name}\" registrado en catálogo", null, '📚');

        return redirect()->route('bundles.index')
                         ->with('success', 'Bundle registrado correctamente.');
    }

    public function importar(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $request->validate(
            ['archivo' => 'required|file|mimes:xlsx,xls|max:10240'],
            [
                'archivo.required' => 'Selecciona un archivo Excel.',
                'archivo.mimes'    => 'Solo se permiten archivos .xlsx o .xls.',
                'archivo.max'      => 'El archivo no puede superar 10 MB.',
            ]
        );

        try {
            $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
        } catch (\Exception $e) {
            return back()->withErrors(['archivo' => 'No se pudo leer el archivo. Asegúrate de que sea un Excel válido.']);
        }

        $sheet        = $spreadsheet->getActiveSheet();
        $tiposValidos = ['ELT', 'Plan Lector', 'Imagina', 'Wikids', 'Pienso Contigo', 'Complemento', 'Entrelineas', 'Palabrario', 'Frances'];
        $agregados    = 0;
        $duplicados   = 0;
        $omitidos     = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $ri     = $row->getRowIndex();
            $serie  = trim((string) $sheet->getCell("A{$ri}")->getValue());
            $name   = trim((string) $sheet->getCell("B{$ri}")->getValue());
            $grade  = trim((string) $sheet->getCell("C{$ri}")->getValue());
            $level  = trim((string) $sheet->getCell("D{$ri}")->getValue());
            $rolRaw = trim((string) $sheet->getCell("E{$ri}")->getValue());
            $tipo   = trim((string) $sheet->getCell("F{$ri}")->getValue());

            if ($serie === '' && $name === '') continue;

            if ($serie === '') { $omitidos[] = "Fila {$ri}: Serie vacía."; continue; }
            if ($name === '')  { $omitidos[] = "Fila {$ri}: Nombre vacío."; continue; }

            $rolNorm = mb_strtolower($rolRaw);
            if (str_contains($rolNorm, 'alumno') || str_contains($rolNorm, 'student')) {
                $role = 'student';
            } elseif (str_contains($rolNorm, 'docente') || str_contains($rolNorm, 'teacher') || str_contains($rolNorm, 'maestro')) {
                $role = 'teacher';
            } else {
                $omitidos[] = "Fila {$ri}: Rol '{$rolRaw}' no válido (usa Alumno o Docente).";
                continue;
            }

            if (!in_array($tipo, $tiposValidos)) {
                $omitidos[] = "Fila {$ri}: Tipo '{$tipo}' no válido. Usa: " . implode(', ', $tiposValidos);
                continue;
            }

            if (Bundle::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                $duplicados++;
                continue;
            }

            Bundle::create([
                'serie' => $serie,
                'name'  => $name,
                'grade' => $grade ?: null,
                'level' => $level ?: null,
                'role'  => $role,
                'type'  => $tipo,
            ]);
            $agregados++;
        }

        $msg = "Se agregaron {$agregados} bundle(s) al catálogo.";
        if ($duplicados)     $msg .= " {$duplicados} ya existían (omitidos).";
        if (count($omitidos)) $msg .= ' ' . count($omitidos) . ' fila(s) con error.';

        return back()
            ->with('success', $msg)
            ->with('importar_omitidos', $omitidos);
    }

    public function plantilla()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bundles SI');

        $headers = ['Serie', 'Nombre del Bundle', 'Grado', 'Nivel', 'Rol', 'Tipo'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        $ejemplos = [
            ['Academy Stars Second Edition', "Academy Stars Second Edition Level 1 Digital Pupil's Book with Navio App", '1°', 'Primaria', 'Alumno', 'ELT'],
            ['Academy Stars Second Edition', "Academy Stars Second Edition Level 1 Digital Teacher's Book with App",     '1°', 'Primaria', 'Docente', 'ELT'],
            ['Reading Plan Primaria',        'Reading Plan The Big Bad Monster Student with App',                        '1°', 'Primaria', 'Alumno', 'Plan Lector'],
        ];
        foreach ($ejemplos as $ri => $data) {
            foreach ($data as $ci => $val) {
                $sheet->setCellValue(chr(65 + $ci) . ($ri + 2), $val);
            }
        }

        $sheet->setCellValue('A6', '↑ Borra las filas de ejemplo antes de importar');
        $sheet->getStyle('A6')->getFont()->setItalic(true)->getColor()->setRGB('999999');

        $sheet->setCellValue('A8', 'Roles válidos: Alumno | Docente');
        $sheet->setCellValue('A9', 'Tipos válidos: ELT | Plan Lector | Imagina | Wikids | Pienso Contigo | Complemento | Entrelineas | Palabrario | Frances');
        $sheet->setCellValue('A10', 'Niveles válidos: Preescolar | Primaria | Secundaria | Preparatoria (dejar vacío si no aplica)');

        foreach (['A' => 50, 'B' => 85, 'C' => 12, 'D' => 16, 'E' => 18, 'F' => 22] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $writer   = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'plantilla_bundles_');
        $writer->save($tempFile);

        return response()->download($tempFile, 'plantilla-bundles-si.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function edit(Bundle $bundle)
    {
        return response()->json($bundle);
    }

    public function update(Request $request, Bundle $bundle)
    {
        $request->validate([
            'serie' => 'required|string|max:255',
            'name'  => 'required|string|max:255',
            'grade' => 'nullable|string|max:50',
            'level' => 'nullable|string|max:100',
            'role'  => 'required|in:student,teacher',
            'type'  => 'required|in:ELT,Plan Lector,Imagina,Wikids,Pienso Contigo,Complemento,Entrelineas,Palabrario,Frances',
        ]);

        $bundle->update($request->all());

        return redirect()->route('bundles.index')
                         ->with('success', 'Bundle actualizado correctamente.');
    }

    public function destroy(Bundle $bundle)
    {
        $nombre = $bundle->name;
        $bundle->delete();
        ActivityLog::log('bundle', "Bundle \"{$nombre}\" eliminado del catálogo", null, '🗑️');
        return redirect()->route('bundles.index')
                         ->with('success', 'Bundle eliminado correctamente.');
    }
}