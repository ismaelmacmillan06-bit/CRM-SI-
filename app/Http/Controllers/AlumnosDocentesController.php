<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AlumnosDocentesController extends Controller
{
    const NIVELES = ['Maternal', 'Preescolar', 'Primaria', 'Secundaria', 'Preparatoria', 'Licenciatura'];

    public function index()
    {
        return view('alumnos-docentes.index', [
            'filas'   => $this->construirFilas(),
            'niveles' => self::NIVELES,
        ]);
    }

    public function exportar()
    {
        $filas = $this->construirFilas();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Alumnos por nivel');

        $headers = array_merge(['Colegio'], self::NIVELES, ['Otros', 'Total']);
        foreach ($headers as $ci => $header) {
            $sheet->setCellValue($this->col($ci) . '1', $header);
        }
        $ultimaCol = $this->col(count($headers) - 1);
        $sheet->getStyle("A1:{$ultimaCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        foreach ($filas as $fila) {
            $valores = array_merge(
                [$fila['school']->name],
                array_values($fila['niveles']),
                [$fila['otros'], $fila['total']]
            );
            foreach ($valores as $ci => $val) {
                $sheet->setCellValue($this->col($ci) . $row, $val);
            }
            $row++;
        }

        // Fila de totales generales
        $sheet->setCellValue('A' . $row, 'Total general');
        foreach (self::NIVELES as $i => $nivel) {
            $sheet->setCellValue($this->col($i + 1) . $row, $filas->sum(fn($f) => $f['niveles'][$nivel]));
        }
        $sheet->setCellValue($this->col(count(self::NIVELES) + 1) . $row, $filas->sum('otros'));
        $sheet->setCellValue($this->col(count(self::NIVELES) + 2) . $row, $filas->sum('total'));
        $sheet->getStyle("A{$row}:{$ultimaCol}{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
        ]);

        $sheet->getColumnDimension('A')->setWidth(38);
        foreach (range(1, count($headers) - 1) as $ci) {
            $sheet->getColumnDimension($this->col($ci))->setWidth(14);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'alumnos_docentes_');
        try {
            (new Xlsx($spreadsheet))->save($tempFile);
            return response()->download($tempFile, 'alumnos-por-nivel.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            @unlink($tempFile);
            return back()->with('error', 'No se pudo generar el Excel. Intenta de nuevo.');
        }
    }

    private function construirFilas()
    {
        $schools = School::withCount('students')->orderBy('name')->get();

        $porColegio = Student::select('school_id', 'level')->get()->groupBy('school_id');

        $catalogNorm = collect(self::NIVELES)->mapWithKeys(fn($n) => [$this->normalizar($n) => $n]);

        return $schools->map(function ($school) use ($porColegio, $catalogNorm) {
            $conteos = array_fill_keys(self::NIVELES, 0);
            $otros   = 0;

            foreach ($porColegio->get($school->id, collect()) as $student) {
                $norm = $this->normalizar($student->level);
                if (isset($catalogNorm[$norm])) {
                    $conteos[$catalogNorm[$norm]]++;
                } else {
                    $otros++;
                }
            }

            return [
                'school'  => $school,
                'niveles' => $conteos,
                'otros'   => $otros,
                'total'   => $school->students_count,
            ];
        });
    }

    /**
     * Convierte un índice 0-based de columna a letra de Excel (0 -> A, 1 -> B, ...).
     */
    private function col(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
    }

    public function buscar(Request $request)
    {
        $q = trim((string) $request->query('usuario'));
        if ($q === '') {
            return response()->json([]);
        }

        $alumnos = Student::with('school')
            ->where('mee_username', 'like', "%{$q}%")
            ->limit(20)->get()
            ->map(fn($s) => [
                'tipo'        => 'Alumno',
                'nombre'      => trim($s->name . ' ' . $s->last_name),
                'usuario'     => $s->mee_username,
                'contrasena'  => $s->mee_password,
                'school_id'   => $s->school_id,
                'school_name' => $s->school?->name ?? '—',
            ]);

        $docentes = Teacher::with('school')
            ->where('mee_username', 'like', "%{$q}%")
            ->limit(20)->get()
            ->map(fn($t) => [
                'tipo'        => 'Docente',
                'nombre'      => trim($t->name . ' ' . $t->last_name),
                'usuario'     => $t->mee_username,
                'contrasena'  => $t->mee_password,
                'school_id'   => $t->school_id,
                'school_name' => $t->school?->name ?? '—',
            ]);

        $resultados = $alumnos->concat($docentes)->sortBy('usuario')->values();

        return response()->json($resultados);
    }

    /**
     * Normaliza un valor de nivel para compararlo sin acentos ni mayúsculas
     * (ej. "primaria" o "PRIMARIA " -> "primaria"), tolerando variaciones
     * de captura en cargas masivas antiguas.
     */
    private function normalizar($valor): string
    {
        $s = (string) $valor;
        $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
        $s = preg_replace('/\p{Mn}/u', '', $s);
        $s = mb_strtolower(trim($s));
        return preg_replace('/[^a-z0-9]/', '', $s);
    }
}
