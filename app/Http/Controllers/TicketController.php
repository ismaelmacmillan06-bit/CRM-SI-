<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\School;
use App\Models\Consultant;
use App\Models\Visit;
use App\Traits\BloqueaColegioInactivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TicketController extends Controller
{
    use BloqueaColegioInactivo;

    public function index(School $school)
    {
        $tickets = $school->tickets()->with('consultant.user')->latest()->get();
        return view('tickets.index', compact('school', 'tickets'));
    }

    public function create(School $school)
    {
        if ($redirect = $this->bloqueadoPorInactivo($school)) {
            return $redirect;
        }

        $consultants = Consultant::with('user')->get();
        return view('tickets.create', compact('school', 'consultants'));
    }

    public function store(Request $request, School $school)
    {
        if ($redirect = $this->bloqueadoPorInactivo($school)) {
            return $redirect;
        }

        $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'priority'      => 'required|in:low,medium,high',
            'consultant_id' => 'required|exists:consultants,id',
            'medium'        => 'required|in:salesforce,whaticket,whatsapp',
            'productos'     => 'required|array|min:1',
            'productos.*'   => 'in:' . implode(',', Visit::PRODUCTOS),
            'evidence'      => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'evidence.mimes' => 'Solo se permiten imágenes JPG, PNG o WEBP (máx. 5 MB).',
            'productos.required' => 'Selecciona al menos un producto.',
        ]);

        $data = $request->only(['title', 'description', 'priority', 'consultant_id', 'medium', 'productos']);

        if ($request->hasFile('evidence')) {
            $data['evidence'] = $request->file('evidence')->store('tickets', 'public');
        }

        $ticket = $school->tickets()->create($data);

        $prioridades = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'];
        $prio = $prioridades[$request->priority] ?? $request->priority;
        ActivityLog::log('ticket', "Ticket creado: \"{$request->title}\" (prioridad: $prio)", $school->id, '🎫');

        return redirect()->route('schools.tickets.index', $school)
                         ->with('success', 'Ticket registrado correctamente.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['school', 'consultant.user']);
        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        $consultants = Consultant::with('user')->get();
        return view('tickets.edit', compact('ticket', 'consultants'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'priority'      => 'required|in:low,medium,high',
            'status'        => 'required|in:open,in_progress,closed',
            'consultant_id' => 'nullable|exists:consultants,id',
            'medium'        => 'required|in:salesforce,whaticket,whatsapp',
            'productos'     => 'required|array|min:1',
            'productos.*'   => 'in:' . implode(',', Visit::PRODUCTOS),
            'evidence'      => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'evidence.mimes' => 'Solo se permiten imágenes JPG, PNG o WEBP (máx. 5 MB).',
            'productos.required' => 'Selecciona al menos un producto.',
        ]);

        $data = $request->only(['title', 'description', 'priority', 'status', 'consultant_id', 'medium', 'productos']);

        if ($request->hasFile('evidence')) {
            if ($ticket->evidence) {
                Storage::disk('public')->delete($ticket->evidence);
            }
            $data['evidence'] = $request->file('evidence')->store('tickets', 'public');
        } elseif ($request->boolean('remove_evidence')) {
            if ($ticket->evidence) {
                Storage::disk('public')->delete($ticket->evidence);
            }
            $data['evidence'] = null;
        }

        if ($request->status === 'closed' && $ticket->status !== 'closed') {
            $data['resolved_at'] = now();
        } elseif ($request->status !== 'closed' && $ticket->status === 'closed') {
            $data['resolved_at'] = null;
        }

        $oldStatus = $ticket->status;
        $ticket->update($data);

        if ($oldStatus !== $request->status) {
            $statusLabel = ['open' => 'Abierto', 'in_progress' => 'En proceso', 'closed' => 'Cerrado'];
            $de  = $statusLabel[$oldStatus]        ?? $oldStatus;
            $a   = $statusLabel[$request->status]  ?? $request->status;
            $ico = $request->status === 'closed' ? '✅' : '🔄';
            ActivityLog::log('ticket', "Ticket \"{$ticket->title}\" cambió de $de → $a", $ticket->school_id, $ico);
        }

        return redirect()->route('schools.tickets.index', $ticket->school)
                         ->with('success', 'Ticket actualizado correctamente.');
    }

    public function destroy(Ticket $ticket)
    {
        $school = $ticket->school;
        $titulo = $ticket->title;
        if ($ticket->evidence) {
            Storage::disk('public')->delete($ticket->evidence);
        }
        $ticket->delete();
        ActivityLog::log('ticket', "Ticket \"{$titulo}\" eliminado", $school->id, '🗑️');
        return redirect()->route('schools.tickets.index', $school)
                         ->with('success', 'Ticket eliminado correctamente.');
    }

    public function reportPdf(Request $request, School $school)
    {
        $report = $this->buildReportData($school, $request);
        ActivityLog::log('ticket', "Reporte PDF de tickets consultado para {$school->name}", $school->id, '📄');
        return view('tickets.report-pdf', $report);
    }

    public function reportExcel(Request $request, School $school)
    {
        $report = $this->buildReportData($school, $request);
        $tickets = $report['tickets'];

        $spreadsheet = new Spreadsheet();
        $resumen = $spreadsheet->getActiveSheet();
        $resumen->setTitle('Resumen y Gráficos');

        // Encabezado ejecutivo con degradado completo y logo blanco transparente
        $resumen->setCellValue('A1', 'REPORTE DE TICKETS — ' . strtoupper($school->name));
        $resumen->mergeCells('A1:G1');
        $resumen->getStyle('A1:G1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => [
                'fillType'   => Fill::FILL_GRADIENT_LINEAR,
                'rotation'   => 0,
                'startColor' => ['rgb' => '181311'],
                'endColor'   => ['rgb' => 'E2231A'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $resumen->getRowDimension(1)->setRowHeight(36);

        $resumen->setCellValue('A2', 'Periodo: ' . $report['periodoLabel'] . '   |   ID Nexus: ' . ($school->nexus_id ?: 'Sin Nexus ID') . '   |   Generado: ' . $report['generado']);
        $resumen->mergeCells('A2:G2');
        $resumen->getStyle('A2:G2')->applyFromArray([
            'font'      => ['size' => 10, 'italic' => true, 'color' => ['rgb' => 'F8FAFC']],
            'fill'      => [
                'fillType'   => Fill::FILL_GRADIENT_LINEAR,
                'rotation'   => 0,
                'startColor' => ['rgb' => '2D1B18'],
                'endColor'   => ['rgb' => 'B31810'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'FBBF24']]],
        ]);
        $resumen->getRowDimension(2)->setRowHeight(24);
        $this->addLogoSi($resumen, 'A1', 44);

        $resumen->setCellValue('A3', 'Total de tickets en el periodo:');
        $resumen->setCellValue('B3', $report['total']);
        $resumen->getStyle('A3:G3')->applyFromArray([
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
        $resumen->getRowDimension(3)->setRowHeight(22);
        $resumen->getStyle('A3')->getFont()->setBold(true);
        $resumen->getStyle('B3')->getFont()->setBold(true);
        $resumen->setShowGridlines(false);
        $resumen->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);

        // Tablas de resumen para los gráficos
        $writeSummaryTable = function (int $startRow, string $colA, string $colB, string $titulo, array $items) use ($resumen) {
            $resumen->setCellValue("{$colA}{$startRow}", $titulo);
            $resumen->setCellValue("{$colB}{$startRow}", 'Cantidad');
            $resumen->getStyle("{$colA}{$startRow}:{$colB}{$startRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '181311']],
            ]);
            $r = $startRow + 1;
            foreach ($items as $label => $count) {
                $resumen->setCellValue("{$colA}{$r}", $label);
                $resumen->setCellValue("{$colB}{$r}", $count);
                $resumen->getStyle("{$colA}{$r}:{$colB}{$r}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);
                $r++;
            }
            return [$startRow + 1, $r - 1];
        };

        [$stStart, $stEnd] = $writeSummaryTable(5, 'A', 'B', 'Estatus', $report['porStatus']);
        [$prStart, $prEnd] = $writeSummaryTable(10, 'A', 'B', 'Prioridad', $report['porPrioridad']);
        [$mdStart, $mdEnd] = $writeSummaryTable(15, 'A', 'B', 'Medio', $report['porMedio']);
        [$pdStart, $pdEnd] = $writeSummaryTable(20, 'A', 'B', 'Producto', $report['porProducto']);

        // Gráficos nativos de Excel
        $sheetTitle = "'Resumen y Gráficos'";

        // 1. Pastel de Estatus
        $statusChart = new Chart(
            'chart_status',
            new Title('Tickets por Estatus'),
            new Legend(Legend::POSITION_RIGHT, null, false),
            new PlotArea(null, [
                new DataSeries(
                    DataSeries::TYPE_PIECHART,
                    null,
                    [0],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetTitle}!\$B\$5", null, 1)],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetTitle}!\$A\${$stStart}:\$A\${$stEnd}", null, count($report['porStatus']))],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetTitle}!\$B\${$stStart}:\$B\${$stEnd}", null, count($report['porStatus']))]
                ),
            ])
        );
        $statusChart->setTopLeftPosition('D5');
        $statusChart->setBottomRightPosition('G14');
        $resumen->addChart($statusChart);

        // 2. Barras de Prioridad
        $prioChart = new Chart(
            'chart_prioridad',
            new Title('Tickets por Prioridad'),
            null,
            new PlotArea(null, [
                new DataSeries(
                    DataSeries::TYPE_BARCHART,
                    DataSeries::GROUPING_CLUSTERED,
                    [0],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetTitle}!\$B\$10", null, 1)],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetTitle}!\$A\${$prStart}:\$A\${$prEnd}", null, count($report['porPrioridad']))],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetTitle}!\$B\${$prStart}:\$B\${$prEnd}", null, count($report['porPrioridad']))]
                ),
            ])
        );
        $prioChart->setTopLeftPosition('D16');
        $prioChart->setBottomRightPosition('G24');
        $resumen->addChart($prioChart);

        // 3. Barras por Producto
        $prodChart = new Chart(
            'chart_producto',
            new Title('Tickets por Producto'),
            null,
            new PlotArea(null, [
                new DataSeries(
                    DataSeries::TYPE_BARCHART,
                    DataSeries::GROUPING_CLUSTERED,
                    [0],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetTitle}!\$B\$20", null, 1)],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetTitle}!\$A\${$pdStart}:\$A\${$pdEnd}", null, count($report['porProducto']))],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetTitle}!\$B\${$pdStart}:\$B\${$pdEnd}", null, count($report['porProducto']))]
                ),
            ])
        );
        $prodChart->setTopLeftPosition('D26');
        $prodChart->setBottomRightPosition('G36');
        $resumen->addChart($prodChart);

        foreach (['A' => 24, 'B' => 24, 'C' => 4, 'D' => 18, 'E' => 18, 'F' => 16, 'G' => 22] as $col => $w) {
            $resumen->getColumnDimension($col)->setWidth($w);
        }

        // Hoja 2: Detalle de Tickets
        $detalle = $spreadsheet->createSheet();
        $detalle->setTitle('Detalle de Tickets');
        $detalle->setCellValue('A1', 'DETALLE DE TICKETS — ' . strtoupper($school->name));
        $detalle->mergeCells('A1:J1');
        $detalle->getStyle('A1:J1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => [
                'fillType'   => Fill::FILL_GRADIENT_LINEAR,
                'rotation'   => 0,
                'startColor' => ['rgb' => '181311'],
                'endColor'   => ['rgb' => 'E2231A'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $detalle->getRowDimension(1)->setRowHeight(34);

        $detalle->setCellValue('A2', 'Periodo: ' . $report['periodoLabel'] . '   |   ID Nexus: ' . ($school->nexus_id ?: 'Sin Nexus ID'));
        $detalle->mergeCells('A2:J2');
        $detalle->getStyle('A2:J2')->applyFromArray([
            'font'      => ['size' => 10, 'italic' => true, 'color' => ['rgb' => 'F8FAFC']],
            'fill'      => [
                'fillType'   => Fill::FILL_GRADIENT_LINEAR,
                'rotation'   => 0,
                'startColor' => ['rgb' => '2D1B18'],
                'endColor'   => ['rgb' => 'B31810'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'FBBF24']]],
        ]);
        $detalle->getRowDimension(2)->setRowHeight(22);
        $this->addLogoSi($detalle, 'A1', 42);

        $headers = ['ID', 'Título', 'Descripción', 'Medio', 'Productos', 'Prioridad', 'Status', 'Consultor', 'Creado', 'Resuelto'];
        foreach ($headers as $ci => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $detalle->setCellValue("{$colLetter}3", $h);
        }
        $detalle->getStyle('A3:J3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '221A17']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $detalle->getRowDimension(3)->setRowHeight(22);

        $statusMap = ['open' => 'Abierto', 'in_progress' => 'En proceso', 'closed' => 'Cerrado'];
        $prioMap   = ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja'];
        $medMap    = ['salesforce' => 'Sales Force', 'whaticket' => 'Whaticket', 'whatsapp' => 'WhatsApp'];

        $row = 4;
        foreach ($tickets as $idx => $t) {
            $detalle->setCellValue("A{$row}", $t->id);
            $detalle->setCellValue("B{$row}", $t->title);
            $detalle->setCellValue("C{$row}", $t->description);
            $detalle->setCellValue("D{$row}", $medMap[$t->medium] ?? $t->medium);
            $detalle->setCellValue("E{$row}", implode(', ', $t->productos ?? []));
            $detalle->setCellValue("F{$row}", $prioMap[$t->priority] ?? $t->priority);
            $detalle->setCellValue("G{$row}", $statusMap[$t->status] ?? $t->status);
            $detalle->setCellValue("H{$row}", $t->consultant->user->name ?? 'Sin consultor');
            $detalle->setCellValue("I{$row}", $t->created_at?->format('d/m/Y H:i') ?? '—');
            $detalle->setCellValue("J{$row}", $t->resolved_at ? \Carbon\Carbon::parse($t->resolved_at)->format('d/m/Y H:i') : '—');
            $bg = $idx % 2 === 1 ? 'F8FAFC' : 'FFFFFF';
            $detalle->getStyle("A{$row}:J{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E4E0D8']]],
            ]);
            $row++;
        }

        $detalle->setShowGridlines(false);
        $detalle->freezePane('A4');
        $detalle->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
        foreach (['A' => 18, 'B' => 28, 'C' => 42, 'D' => 15, 'E' => 26, 'F' => 12, 'G' => 14, 'H' => 22, 'I' => 18, 'J' => 18] as $col => $w) {
            $detalle->getColumnDimension($col)->setWidth($w);
        }

        $spreadsheet->setActiveSheetIndex(0);
        ActivityLog::log('ticket', "Reporte Excel de tickets descargado para {$school->name}", $school->id, '📊');

        $safeName = trim(str_replace(['<', '>', ':', '"', '/', '\\', '|', '?', '*'], '-', $school->name), ' .');
        $filename = "Reporte_Tickets_{$safeName}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'tickets_rep_');

        try {
            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            $writer->save($tempFile);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            @unlink($tempFile);
            return back()->with('error', 'No se pudo generar el reporte Excel. Intenta de nuevo.');
        }
    }

    private function buildReportData(School $school, ?Request $request = null): array
    {
        $desdeRaw = trim((string) ($request?->query('desde', '') ?? ''));
        $hastaRaw = trim((string) ($request?->query('hasta', '') ?? ''));

        $desde = null;
        $hasta = null;
        try {
            if ($desdeRaw !== '') {
                $desde = \Carbon\Carbon::createFromFormat('Y-m-d', $desdeRaw)->startOfDay();
            }
        } catch (\Throwable) {}
        try {
            if ($hastaRaw !== '') {
                $hasta = \Carbon\Carbon::createFromFormat('Y-m-d', $hastaRaw)->endOfDay();
            }
        } catch (\Throwable) {}

        if ($desde && $hasta && $desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        $tickets = $school->tickets()
            ->with('consultant.user')
            ->when($desde, fn ($q) => $q->where('created_at', '>=', $desde))
            ->when($hasta, fn ($q) => $q->where('created_at', '<=', $hasta))
            ->latest()
            ->get();

        if ($desde && $hasta) {
            $periodoLabel = 'Del ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y');
        } elseif ($desde) {
            $periodoLabel = 'Desde el ' . $desde->format('d/m/Y');
        } elseif ($hasta) {
            $periodoLabel = 'Hasta el ' . $hasta->format('d/m/Y');
        } else {
            $periodoLabel = 'Histórico completo';
        }

        $porStatus = [
            'Abierto'    => $tickets->where('status', 'open')->count(),
            'En proceso' => $tickets->where('status', 'in_progress')->count(),
            'Cerrado'    => $tickets->where('status', 'closed')->count(),
        ];

        $porPrioridad = [
            'Alta'  => $tickets->where('priority', 'high')->count(),
            'Media' => $tickets->where('priority', 'medium')->count(),
            'Baja'  => $tickets->where('priority', 'low')->count(),
        ];

        $porMedio = [
            'Sales Force' => $tickets->where('medium', 'salesforce')->count(),
            'Whaticket'   => $tickets->where('medium', 'whaticket')->count(),
            'WhatsApp'    => $tickets->where('medium', 'whatsapp')->count(),
        ];

        $porProducto = [];
        foreach (Visit::PRODUCTOS as $prod) {
            $porProducto[$prod] = $tickets->filter(fn ($t) => in_array($prod, $t->productos ?? [], true))->count();
        }

        return [
            'school'       => $school,
            'tickets'      => $tickets,
            'total'        => $tickets->count(),
            'porStatus'    => $porStatus,
            'porPrioridad' => $porPrioridad,
            'porMedio'     => $porMedio,
            'porProducto'  => $porProducto,
            'generado'     => now()->format('d/m/Y H:i'),
            'periodoLabel' => $periodoLabel,
            'desde'        => $desde?->format('Y-m-d') ?? '',
            'hasta'        => $hasta?->format('Y-m-d') ?? '',
        ];
    }

    private function addLogoSi($sheet, string $coord = 'A1', int $height = 46): void
    {
        $logoPath = public_path('images/logo-si-pdf-white.png');
        if (!is_file($logoPath)) {
            $logoPath = public_path('images/logo-si-pdf.png');
        }
        if (!is_file($logoPath)) {
            return;
        }
        $logo = new Drawing();
        $logo->setName('Macmillan SI');
        $logo->setDescription('Macmillan SI');
        $logo->setPath($logoPath);
        $logo->setCoordinates($coord);
        $logo->setHeight($height);
        $logo->setOffsetX(12);
        $logo->setOffsetY(6);
        $logo->setWorksheet($sheet);
    }
}