<?php

namespace App\Http\Controllers;

use App\Helpers\Zonas;
use App\Models\BundleResurtido;
use App\Models\Consultant;
use App\Models\MeeAdmin;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\Teacher;
use App\Models\TeacherRole;
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

    // ─────────────────────────────────────────────────────────────────────
    // Reporte General (4 hojas: Colegios, Directores, Admins MEE, Docentes)
    // ─────────────────────────────────────────────────────────────────────
    public function reporteGeneral()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // quitar hoja vacía por defecto

        $generado = now()->format('d/m/Y H:i');

        // ── HOJA 1: COLEGIOS ─────────────────────────────────────────────
        $schools = School::with([
            'schoolConsultants.consultant.user',
            'schoolLevels.level',
            'schoolLevels.processes',
            'students',
        ])->orderBy('name')->get();

        $ws1 = $spreadsheet->createSheet(0);
        $ws1->setTitle('Colegios');

        $this->sheetTitle($ws1, "MacmillanSI — Colegios ({$schools->count()})", 'C0392B', 10, $generado);

        $headers1 = ['Nombre', 'Ciudad / Estado', 'Estatus', 'Consultor Digital',
                     'Consultor ECA', 'Consultor ELT', 'Rep. Ventas',
                     'Niveles', 'Progreso %', 'Alumnos SI'];
        $this->writeHeaders($ws1, 4, $headers1, 'C0392B');

        $row = 5;
        foreach ($schools as $i => $s) {
            $cons = $s->schoolConsultants;
            $digital = $cons->where('role','digital')->first()?->consultant->user->name ?? '—';
            $eca     = $cons->where('role','eca')    ->first()?->consultant->user->name ?? '—';
            $elt     = $cons->where('role','elt')    ->first()?->consultant->user->name ?? '—';
            $ventas  = $cons->where('role','ventas') ->first()?->consultant->user->name ?? '—';

            $totalP = 0; $doneP = 0;
            foreach ($s->schoolLevels as $sl) {
                $totalP += $sl->processes->count();
                $doneP  += $sl->processes->where('status','done')->count();
            }
            $pct     = $totalP > 0 ? round(($doneP / $totalP) * 100) . '%' : '0%';
            $niveles = $s->schoolLevels->map(fn($sl) => $sl->level->name ?? '')->filter()->join(', ');

            $ws1->fromArray([
                $s->name, $s->city ?? '—', ucfirst($s->status),
                $digital, $eca, $elt, $ventas, $niveles, $pct, $s->students->count(),
            ], null, "A{$row}");

            if ($i % 2) $this->stripeRow($ws1, $row, 10);
            $row++;
        }

        $this->setWidths($ws1, [38,22,12,26,26,26,26,30,12,12]);

        // ── HOJA 2: DIRECTORES ───────────────────────────────────────────
        $directorRoles = TeacherRole::whereIn('role', ['director_general','director_nivel'])
            ->with(['teacher.school.schoolConsultants.consultant.user'])
            ->get();

        $ws2 = $spreadsheet->createSheet(1);
        $ws2->setTitle('Directores');

        $this->sheetTitle($ws2, "MacmillanSI — Directores ({$directorRoles->count()})", '7C3AED', 8, $generado);

        $headers2 = ['Nombre', 'Apellidos', 'Email', 'Colegio', 'Ciudad', 'Rol', 'Materia', 'Consultor Digital'];
        $this->writeHeaders($ws2, 4, $headers2, '7C3AED');

        $row = 5;
        foreach ($directorRoles as $i => $tr) {
            $t       = $tr->teacher;
            $school  = $t->school;
            $rolLabel = Teacher::ROLES[$tr->role] ?? $tr->role;
            $digital  = $school?->schoolConsultants->where('role','digital')->first()?->consultant->user->name ?? '—';

            $ws2->fromArray([
                $t->name, $t->last_name, $t->email ?? '—',
                $school?->name ?? '—', $school?->city ?? '—',
                $rolLabel, strtoupper($t->subject ?? '—'), $digital,
            ], null, "A{$row}");

            if ($i % 2) $this->stripeRow($ws2, $row, 8);
            $row++;
        }

        $this->setWidths($ws2, [22,22,30,34,20,22,14,26]);

        // ── HOJA 3: ADMINS MEE ───────────────────────────────────────────
        $ws3 = $spreadsheet->createSheet(2);
        $ws3->setTitle('Admins MEE');

        $meeAdmins    = MeeAdmin::with('school')->orderBy('school_id')->get();
        $teacherAdmins = TeacherRole::where('role','admin_mee')
            ->with('teacher.school')
            ->get();

        $totalAdmins = $meeAdmins->count() + $teacherAdmins->count();
        $this->sheetTitle($ws3, "MacmillanSI — Admins MEE ({$totalAdmins})", '059669', 6, $generado);

        // Sección A: Cuentas MEE de colegio
        $ws3->setCellValue('A4', 'CUENTAS MEE DE COLEGIO');
        $ws3->getStyle('A4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '065F46']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
        ]);
        $ws3->mergeCells('A4:F4');

        $headers3a = ['Colegio', 'Ciudad', 'Usuario MEE', 'Contraseña MEE', 'Consultor Digital', 'Estatus Colegio'];
        $this->writeHeaders($ws3, 5, $headers3a, '059669');

        $row = 6;
        foreach ($meeAdmins as $i => $ma) {
            $school  = $ma->school;
            $digital = $school?->schoolConsultants?->where('role','digital')->first()?->consultant->user->name ?? '—';

            $ws3->fromArray([
                $school?->name ?? '—', $school?->city ?? '—',
                $ma->username, $ma->password_plain,
                $digital, ucfirst($school?->status ?? '—'),
            ], null, "A{$row}");

            if ($i % 2) $this->stripeRow($ws3, $row, 6);
            $row++;
        }

        // Sección B: Docentes con rol Admin MEE
        $row += 2;
        $ws3->setCellValue("A{$row}", 'DOCENTES CON ROL ADMINISTRADOR MEE');
        $ws3->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '065F46']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
        ]);
        $ws3->mergeCells("A{$row}:F{$row}");
        $row++;

        $headers3b = ['Nombre', 'Apellidos', 'Email', 'Colegio', 'Usuario MEE', 'Contraseña MEE'];
        $this->writeHeaders($ws3, $row, $headers3b, '059669');
        $row++;

        foreach ($teacherAdmins as $i => $tr) {
            $t = $tr->teacher;
            $ws3->fromArray([
                $t->name, $t->last_name, $t->email ?? '—',
                $t->school?->name ?? '—', $t->mee_username ?? '—', $t->mee_password ?? '—',
            ], null, "A{$row}");

            if ($i % 2) $this->stripeRow($ws3, $row, 6);
            $row++;
        }

        $this->setWidths($ws3, [34,20,28,28,24,20]);

        // ── HOJA 4: DOCENTES ─────────────────────────────────────────────
        $teachers = Teacher::with(['school','roles'])->orderBy('school_id')->orderBy('name')->get();

        $ws4 = $spreadsheet->createSheet(3);
        $ws4->setTitle('Docentes');

        $this->sheetTitle($ws4, "MacmillanSI — Docentes ({$teachers->count()})", '1D4ED8', 10, $generado);

        $headers4 = ['Nombre', 'Apellidos', 'Email', 'Colegio', 'Ciudad',
                     'Grado', 'Roles', 'Materia', 'Usuario MEE', 'Contraseña MEE'];
        $this->writeHeaders($ws4, 4, $headers4, '1D4ED8');

        $row = 5;
        foreach ($teachers as $i => $t) {
            $roles = $t->roles->map(fn($r) => Teacher::ROLES[$r->role] ?? $r->role)->join(', ');

            $ws4->fromArray([
                $t->name, $t->last_name, $t->email ?? '—',
                $t->school?->name ?? '—', $t->school?->city ?? '—',
                $t->grade ?? '—', $roles ?: '—', strtoupper($t->subject ?? '—'),
                $t->mee_username ?? '—', $t->mee_password ?? '—',
            ], null, "A{$row}");

            if ($i % 2) $this->stripeRow($ws4, $row, 10);
            $row++;
        }

        $this->setWidths($ws4, [22,22,30,32,20,14,36,10,22,22]);

        // ── Exportar ─────────────────────────────────────────────────────
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'reporte-general-' . now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'crm_');

        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Reporte de Resurtidos (agrupado por colegio)
    // ─────────────────────────────────────────────────────────────────────
    public function reporteResurtidos()
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['admin', 'consultor_digital'])) {
            abort(403);
        }

        $schoolIds = null;
        if ($user->hasRole('consultor_digital')) {
            $consultant = Consultant::where('user_id', $user->id)->first();
            $schoolIds  = SchoolConsultant::where('consultant_id', $consultant?->id)
                ->where('role', 'digital')->pluck('school_id');
        }

        $query = BundleResurtido::with(['school', 'bundle', 'user'])
            ->orderBy('school_id')
            ->orderBy('fecha', 'desc');

        if ($schoolIds) {
            $query->whereIn('school_id', $schoolIds);
        }

        $allResurtidos = $query->get();
        $porColegio    = $allResurtidos->groupBy('school_id');
        $generado      = now()->format('d/m/Y H:i');
        $ncols         = 9;
        $lastCol       = chr(64 + $ncols);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resurtidos');

        $this->sheetTitle(
            $sheet,
            'MacmillanSI — Reporte de Resurtidos (' . $allResurtidos->count() . ' registros)',
            'F97316',
            $ncols,
            $generado
        );

        $headers = ['Colegio', 'Bundle', 'Serie', 'Fecha', 'Cant. Anterior', 'Resurtido', 'Nueva Cant.', 'Autorizado Por', 'Registrado Por'];
        $row = 4;

        if ($porColegio->isEmpty()) {
            $sheet->setCellValue("A{$row}", 'No hay resurtidos registrados.');
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB('999999');
        } else {
            foreach ($porColegio as $schoolId => $resurtidos) {
                $schoolName = $resurtidos->first()->school->name ?? 'Colegio #' . $schoolId;

                // School header
                $sheet->setCellValue("A{$row}", strtoupper($schoolName) . '   (' . $resurtidos->count() . ' resurtido(s))');
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F97316']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;

                $this->writeHeaders($sheet, $row, $headers, 'F97316');
                $row++;

                foreach ($resurtidos as $idx => $r) {
                    $sheet->fromArray([
                        $r->school->name ?? '—',
                        $r->bundle->name ?? '—',
                        $r->bundle->serie ?? '—',
                        $r->fecha ? $r->fecha->format('d/m/Y') : '—',
                        $r->cantidad_anterior,
                        $r->cantidad_resurtido,
                        $r->cantidad_nueva,
                        $r->autorizado_por ?? '—',
                        $r->user->name ?? '—',
                    ], null, "A{$row}");

                    if ($idx % 2) $this->stripeRow($sheet, $row, $ncols);
                    $row++;
                }

                $row++;
            }
        }

        $this->setWidths($sheet, [32, 36, 18, 14, 16, 12, 16, 22, 22]);

        $filename = 'reporte-resurtidos-' . now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'crm_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Helpers de estilo ─────────────────────────────────────────────────

    private function sheetTitle($ws, string $title, string $color, int $cols, string $generado): void
    {
        $lastCol = chr(64 + $cols);
        $ws->setCellValue('A1', $title);
        $ws->setCellValue('A2', 'Generado el ' . $generado);
        $ws->mergeCells("A1:{$lastCol}1");
        $ws->mergeCells("A2:{$lastCol}2");

        $ws->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '666666']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
        ]);
        $ws->getRowDimension(1)->setRowHeight(28);
        $ws->getRowDimension(2)->setRowHeight(16);
    }

    private function writeHeaders($ws, int $row, array $headers, string $color): void
    {
        foreach ($headers as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . $row, $h);
        }
        $last = chr(64 + count($headers));
        $ws->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $ws->getRowDimension($row)->setRowHeight(18);
    }

    private function stripeRow($ws, int $row, int $cols): void
    {
        $last = chr(64 + $cols);
        $ws->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7F7F7']],
        ]);
    }

    private function setWidths($ws, array $widths): void
    {
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension(chr(65 + $i))->setWidth($w);
        }
    }
}
