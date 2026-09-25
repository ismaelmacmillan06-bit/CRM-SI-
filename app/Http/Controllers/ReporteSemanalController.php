<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\Process;
use App\Models\School;
use App\Models\WeeklyReportEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReporteSemanalController extends Controller
{
    private const CATEGORIAS = [
        'red_roo_reads'     => ['label' => 'Red Roo Reads',      'icon' => '📚'],
        'equipamiento_lego' => ['label' => 'Equipamiento y LEGO', 'icon' => '🧩'],
        'meta'              => ['label' => 'Meta',                'icon' => '📈'],
        'exams_lab'         => ['label' => 'Exams Lab',           'icon' => '🧪'],
        'frances'           => ['label' => 'Francés',             'icon' => '🇫🇷'],
        'extras'            => ['label' => 'Extras',              'icon' => '✨'],
    ];

    public function index(Request $request)
    {
        [$weekStart, $weekEnd] = $this->resolverSemana($request->query('week'));
        $weekOptions = $this->opcionesDeSemana($weekStart);

        $consultores = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))
            ->with('user')
            ->get()
            ->sortBy(fn($c) => $c->user->name)
            ->values();

        $entradas = WeeklyReportEntry::where('week_start', $weekStart->toDateString())->get();

        $grid = [];
        foreach (self::CATEGORIAS as $slug => $meta) {
            foreach ($consultores as $consultor) {
                $grid[$slug][$consultor->id] = '';
            }
        }
        foreach ($entradas as $entrada) {
            $grid[$entrada->category][$entrada->consultant_id] = $entrada->content ?? '';
        }

        $ultimoGuardado = $entradas->max('updated_at');

        [$colegiosSinAlumno, $colegiosSinDocente] = $this->colegiosPendientes();

        $totalCampos = count(self::CATEGORIAS) * max($consultores->count(), 1);
        $novedadesCapturadas = $entradas->filter(fn($e) => trim((string) $e->content) !== '')->count();
        $avance = $totalCampos > 0 ? (int) round($novedadesCapturadas / $totalCampos * 100) : 0;

        $esAdmin = auth()->user()->hasRole('admin');
        $miConsultorId = optional(Consultant::where('user_id', auth()->id())->first())->id;

        return view('reporte-semanal.index', [
            'esAdmin'             => $esAdmin,
            'miConsultorId'       => $miConsultorId,
            'categorias'          => self::CATEGORIAS,
            'consultores'         => $consultores,
            'grid'                => $grid,
            'weekStart'           => $weekStart,
            'weekEnd'             => $weekEnd,
            'weekLabel'           => $this->formatearRangoSemana($weekStart, $weekEnd),
            'weekOptions'         => $weekOptions,
            'esSemanaActual'      => $weekStart->toDateString() === now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'weekPrev'            => $weekStart->copy()->subWeek()->toDateString(),
            'weekNext'            => $weekStart->copy()->addWeek()->toDateString(),
            'ultimoGuardado'      => $ultimoGuardado,
            'colegiosSinAlumno'   => $colegiosSinAlumno,
            'colegiosSinDocente'  => $colegiosSinDocente,
            'novedadesCapturadas' => $novedadesCapturadas,
            'totalCampos'         => $totalCampos,
            'avance'              => $avance,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'consultor_digital']), 403);

        $request->validate([
            'payload' => 'required|string',
            'week'    => 'required|date',
        ]);

        $entradas = json_decode($request->input('payload'), true);
        if (!is_array($entradas)) {
            return back()->with('error', 'No se pudo leer la información enviada.');
        }

        $weekStart = Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)->toDateString();
        $categoriasValidas = array_keys(self::CATEGORIAS);
        $consultorIdsValidos = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))
            ->pluck('id')
            ->toArray();

        $esAdmin = auth()->user()->hasRole('admin');
        $miConsultorId = optional(Consultant::where('user_id', auth()->id())->first())->id;

        $guardados = 0;
        foreach ($entradas as $entrada) {
            $categoria = $entrada['category'] ?? null;
            $consultantId = $entrada['consultant_id'] ?? null;

            if (!in_array($categoria, $categoriasValidas, true) || !in_array((int) $consultantId, $consultorIdsValidos, true)) {
                continue;
            }

            // Cada consultor digital solo puede guardar su propia columna;
            // el admin puede editar todas.
            if (!$esAdmin && (int) $consultantId !== (int) $miConsultorId) {
                continue;
            }

            WeeklyReportEntry::updateOrCreate(
                [
                    'week_start'    => $weekStart,
                    'category'      => $categoria,
                    'consultant_id' => $consultantId,
                ],
                [
                    'content'    => $entrada['content'] ?? null,
                    'updated_by' => auth()->id(),
                ]
            );
            $guardados++;
        }

        return back()->with('success', "Reporte guardado ({$guardados} celda(s) actualizadas).");
    }

    public function exportar(Request $request)
    {
        [$weekStart, $weekEnd] = $this->resolverSemana($request->query('week'));

        $consultores = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))
            ->with('user')
            ->get()
            ->sortBy(fn($c) => $c->user->name)
            ->values();

        $entradas = WeeklyReportEntry::where('week_start', $weekStart->toDateString())->get()
            ->keyBy(fn($e) => $e->category . '_' . $e->consultant_id);

        [$colegiosSinAlumno, $colegiosSinDocente] = $this->colegiosPendientes();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte semanal');

        $col = fn(int $index) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
        $ultimaCol = $col($consultores->count()); // 0 = "Seguimiento"

        $sheet->setCellValue('A1', 'Reporte semanal — ' . $weekStart->format('d/m/Y') . ' al ' . $weekEnd->format('d/m/Y'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->setCellValue('A3', 'Colegios que aún no envían su formato de alumno');
        $sheet->setCellValue('B3', $colegiosSinAlumno->count());
        $sheet->setCellValue('A4', 'Colegios que aún no envían su formato de docente');
        $sheet->setCellValue('B4', $colegiosSinDocente->count());
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);

        $filaHeader = 6;
        $sheet->setCellValue('A' . $filaHeader, 'Seguimiento');
        foreach ($consultores as $i => $consultor) {
            $sheet->setCellValue($col($i + 1) . $filaHeader, $consultor->user->name);
        }
        $sheet->getStyle("A{$filaHeader}:{$ultimaCol}{$filaHeader}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $fila = $filaHeader + 1;
        foreach (self::CATEGORIAS as $slug => $meta) {
            $sheet->setCellValue('A' . $fila, $meta['label']);
            $sheet->getStyle('A' . $fila)->getFont()->setBold(true);
            foreach ($consultores as $i => $consultor) {
                $entrada = $entradas->get($slug . '_' . $consultor->id);
                $celda = $col($i + 1) . $fila;
                $sheet->setCellValue($celda, $entrada->content ?? '');
                $sheet->getStyle($celda)->getAlignment()->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            }
            $sheet->getRowDimension($fila)->setRowHeight(45);
            $fila++;
        }

        $fila += 1;
        $sheet->setCellValue('A' . $fila, 'Listado de colegios que faltan de entregar formato docente');
        $sheet->getStyle('A' . $fila)->getFont()->setBold(true);
        $fila++;
        foreach ($colegiosSinDocente as $colegio) {
            $sheet->setCellValue('A' . $fila, $colegio->name);
            $fila++;
        }

        $fila += 1;
        $sheet->setCellValue('A' . $fila, 'Listado de colegios que faltan de entregar formato alumno');
        $sheet->getStyle('A' . $fila)->getFont()->setBold(true);
        $fila++;
        foreach ($colegiosSinAlumno as $colegio) {
            $sheet->setCellValue('A' . $fila, $colegio->name);
            $fila++;
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        foreach (range(1, $consultores->count()) as $i) {
            $sheet->getColumnDimension($col($i))->setWidth(32);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'reporte_semanal_');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tempFile);
            $filename = 'reporte_semanal_' . $weekStart->format('Y-m-d') . '.xlsx';
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            @unlink($tempFile);
            return back()->with('error', 'No se pudo generar el Excel. Intenta de nuevo.');
        }
    }

    /**
     * Convierte el parámetro ?week= (cualquier fecha) en el lunes de esa
     * semana; si no viene o es inválido, usa la semana actual.
     */
    private function resolverSemana(?string $weekParam): array
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        if ($weekParam) {
            try {
                $weekStart = Carbon::parse($weekParam)->startOfWeek(Carbon::MONDAY);
            } catch (\Throwable $e) {
                // fecha inválida: se queda con la semana actual
            }
        }

        return [$weekStart, $weekStart->copy()->addDays(4)];
    }

    /**
     * Opciones para el selector de semana: un rango fijo relativo a hoy
     * (12 semanas atrás, 4 adelante) más cualquier semana que ya tenga
     * datos guardados, para no perder acceso al historial aunque quede
     * fuera de ese rango.
     */
    private function opcionesDeSemana(Carbon $semanaSeleccionada): \Illuminate\Support\Collection
    {
        $hoyLunes = now()->startOfWeek(Carbon::MONDAY);

        $fechas = collect(range(-12, 4))
            ->map(fn($i) => $hoyLunes->copy()->addWeeks($i)->toDateString());

        $conDatos = WeeklyReportEntry::query()
            ->selectRaw('DISTINCT week_start')
            ->pluck('week_start')
            ->map(fn($d) => Carbon::parse($d)->toDateString());

        return $fechas->merge($conDatos)
            ->push($semanaSeleccionada->toDateString())
            ->unique()
            ->sortDesc()
            ->values()
            ->map(function ($fecha) use ($hoyLunes) {
                $inicio = Carbon::parse($fecha);
                $fin = $inicio->copy()->addDays(4);
                $label = $this->formatearRangoCorto($inicio, $fin);
                if ($fecha === $hoyLunes->toDateString()) {
                    $label .= ' (actual)';
                }
                return ['value' => $fecha, 'label' => $label];
            });
    }

    private function formatearRangoCorto(Carbon $inicio, Carbon $fin): string
    {
        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        if ($inicio->month === $fin->month) {
            return $inicio->day . '–' . $fin->day . ' ' . $meses[$fin->month - 1] . ' ' . $fin->year;
        }

        return $inicio->day . ' ' . $meses[$inicio->month - 1]
             . ' – ' . $fin->day . ' ' . $meses[$fin->month - 1] . ' ' . $fin->year;
    }

    private function formatearRangoSemana(Carbon $inicio, Carbon $fin): string
    {
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                  'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        if ($inicio->month === $fin->month) {
            return $inicio->day . ' al ' . $fin->day . ' de ' . $meses[$fin->month - 1] . ' de ' . $fin->year;
        }

        return $inicio->day . ' de ' . $meses[$inicio->month - 1]
             . ' al ' . $fin->day . ' de ' . $meses[$fin->month - 1] . ' de ' . $fin->year;
    }

    /**
     * Colegios que aún no tienen marcado como "done" el proceso de alta de
     * alumnos / registro de profesores en ninguno de sus niveles.
     */
    private function colegiosPendientes(): array
    {
        $procesoAlumnoId  = Process::where('slug', 'alta_alumnos')->value('id');
        $procesoDocenteId = Process::where('slug', 'registrar_profesores')->value('id');

        $colegiosSinAlumno = School::whereDoesntHave('schoolLevels.processes', function ($q) use ($procesoAlumnoId) {
                $q->where('process_id', $procesoAlumnoId)->where('status', 'done');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $colegiosSinDocente = School::whereDoesntHave('schoolLevels.processes', function ($q) use ($procesoDocenteId) {
                $q->where('process_id', $procesoDocenteId)->where('status', 'done');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return [$colegiosSinAlumno, $colegiosSinDocente];
    }
}
