<?php

namespace App\Http\Controllers;

use App\Helpers\Zonas;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteController extends Controller
{
    public function reporteZonas()
    {
        $user = auth()->user();
        $schoolIds = null;
        if ($user->hasRole('consultor_digital')) {
            $consultant = Consultant::where('user_id', $user->id)->first();
            $schoolIds  = SchoolConsultant::where('consultant_id', $consultant?->id)
                ->where('role', 'digital')->pluck('school_id');
        }

        $query = School::with([
            'schoolConsultants.consultant.user',
            'schoolLevels.processes',
        ])->withCount('students');

        if ($schoolIds) {
            $query->whereIn('id', $schoolIds);
        }

        $schools = $query->get();

        // Group by zona
        $porZona = array_fill_keys(array_keys(Zonas::map()), []);
        $porZona['Sin zona'] = [];

        foreach ($schools as $school) {
            $zona = Zonas::detectZona($school->city ?? '');
            $porZona[$zona][] = $school;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte por Zona');

        // Title rows
        $sheet->setCellValue('A1', 'MacmillanSI CRM — Reporte de Colegios por Zona');
        $sheet->setCellValue('A2', 'Generado el ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '555555']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F8F8']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(16);

        $row = 4;
        $colors = Zonas::colors();

        foreach ($porZona as $zona => $colegios) {
            if (empty($colegios) && $zona === 'Sin zona') continue;

            $color = $colors[$zona] ?? ['excel' => '888888'];

            // Zone header
            $sheet->setCellValue("A{$row}", strtoupper($zona) . '   (' . count($colegios) . ' colegios)');
            $sheet->mergeCells("A{$row}:G{$row}");
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color['excel']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;

            if (empty($colegios)) {
                $sheet->setCellValue("A{$row}", 'Sin colegios en esta zona');
                $sheet->mergeCells("A{$row}:G{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB('999999');
                $row += 2;
                continue;
            }

            // Column headers
            $headers = ['Colegio', 'Estado / Ciudad', 'Estatus', 'Consultor Digital', 'Progreso', 'Alumnos SI', 'Niveles'];
            foreach ($headers as $i => $h) {
                $col = chr(65 + $i);
                $sheet->setCellValue("{$col}{$row}", $h);
            }
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'font'    => ['bold' => true, 'size' => 10],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFEFEF']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
            $row++;

            foreach ($colegios as $idx => $school) {
                $consultor = $school->schoolConsultants
                    ->where('role', 'digital')->first()?->consultant->user->name ?? '—';

                $totalProcesos = 0;
                $totalDone     = 0;
                foreach ($school->schoolLevels as $sl) {
                    $totalProcesos += $sl->processes->count();
                    $totalDone     += $sl->processes->where('status', 'done')->count();
                }
                $pct    = $totalProcesos > 0 ? round(($totalDone / $totalProcesos) * 100) . '%' : '0%';
                $niveles = $school->schoolLevels->map(fn($sl) => $sl->level->name ?? '')->filter()->join(', ');

                $sheet->setCellValue("A{$row}", $school->name);
                $sheet->setCellValue("B{$row}", $school->city ?? '—');
                $sheet->setCellValue("C{$row}", ucfirst($school->status));
                $sheet->setCellValue("D{$row}", $consultor);
                $sheet->setCellValue("E{$row}", $pct);
                $sheet->setCellValue("F{$row}", $school->students_count ?? 0);
                $sheet->setCellValue("G{$row}", $niveles ?: '—');

                if ($idx % 2 === 1) {
                    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9F9F9']],
                    ]);
                }

                $row++;
            }

            $row++; // blank row between zones
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(36);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(40);

        $filename = 'reporte-zonas-' . now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'crm_');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
