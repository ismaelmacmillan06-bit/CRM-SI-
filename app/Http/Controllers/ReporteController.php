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
use App\Models\Visit;
use App\Models\Ticket;
use App\Models\Student;
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
            $zona = Zonas::detectZona($school->state ?? $school->city ?? '');
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
                $sheet->setCellValue("B{$row}", $school->state ?? $school->city ?? '—');
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
                $s->name, $s->state ?? $s->city ?? '—', ucfirst($s->status),
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

        $headers2 = ['Nombre', 'Apellidos', 'Email', 'Colegio', 'Estado', 'Rol', 'Materia', 'Consultor Digital'];
        $this->writeHeaders($ws2, 4, $headers2, '7C3AED');

        $row = 5;
        foreach ($directorRoles as $i => $tr) {
            $t       = $tr->teacher;
            $school  = $t->school;
            $rolLabel = Teacher::ROLES[$tr->role] ?? $tr->role;
            $digital  = $school?->schoolConsultants->where('role','digital')->first()?->consultant->user->name ?? '—';

            $ws2->fromArray([
                $t->name, $t->last_name, $t->email ?? '—',
                $school?->name ?? '—', $school?->state ?? $school?->city ?? '—',
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

        $headers3a = ['Colegio', 'Estado', 'Usuario MEE', 'Contraseña MEE', 'Consultor Digital', 'Estatus Colegio'];
        $this->writeHeaders($ws3, 5, $headers3a, '059669');

        $row = 6;
        foreach ($meeAdmins as $i => $ma) {
            $school  = $ma->school;
            $digital = $school?->schoolConsultants?->where('role','digital')->first()?->consultant->user->name ?? '—';

            $ws3->fromArray([
                $school?->name ?? '—', $school?->state ?? $school?->city ?? '—',
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

        $headers4 = ['Nombre', 'Apellidos', 'Email', 'Colegio', 'Estado',
                     'Grado', 'Roles', 'Materia', 'Usuario MEE', 'Contraseña MEE'];
        $this->writeHeaders($ws4, 4, $headers4, '1D4ED8');

        $row = 5;
        foreach ($teachers as $i => $t) {
            $roles = $t->roles->map(fn($r) => Teacher::ROLES[$r->role] ?? $r->role)->join(', ');

            $ws4->fromArray([
                $t->name, $t->last_name, $t->email ?? '—',
                $t->school?->name ?? '—', $t->school?->state ?? $t->school?->city ?? '—',
                $t->grade ?? '—', $roles ?: '—', strtoupper($t->subject ?? '—'),
                $t->mee_username ?? '—', $t->mee_password ?? '—',
            ], null, "A{$row}");

            if ($i % 2) $this->stripeRow($ws4, $row, 10);
            $row++;
        }

        $this->setWidths($ws4, [22,22,30,32,20,14,36,10,22,22]);

        // ── HOJA 5: COLEGIOS ENTREGADOS ──────────────────────────────────
        $entregados = School::with([
            'schoolConsultants.consultant.user',
            'schoolLevels.level',
            'schoolLevels.processes',
            'students',
            'meeAdmins',
        ])->whereHas('schoolLevels.processes')
          ->whereDoesntHave('schoolLevels', fn($q) =>
              $q->whereHas('processes', fn($q2) => $q2->where('status', '!=', 'done'))
          )
          ->orderBy('name')
          ->get();

        $ws5 = $spreadsheet->createSheet(4);
        $ws5->setTitle('Colegios Entregados');

        $this->sheetTitle($ws5, "MacmillanSI — Colegios Entregados ({$entregados->count()})", '16A34A', 10, $generado);

        $headers5 = ['Nombre', 'Estado', 'Nexus ID', 'Consultor Digital', 'Consultor ECA',
                     'Consultor ELT', 'Rep. Ventas', 'Niveles', 'Alumnos SI', 'Admin MEE'];
        $this->writeHeaders($ws5, 4, $headers5, '16A34A');

        $row = 5;
        foreach ($entregados as $i => $s) {
            $cons    = $s->schoolConsultants;
            $digital = $cons->where('role','digital')->first()?->consultant->user->name ?? '—';
            $eca     = $cons->where('role','eca')    ->first()?->consultant->user->name ?? '—';
            $elt     = $cons->where('role','elt')    ->first()?->consultant->user->name ?? '—';
            $ventas  = $cons->where('role','ventas') ->first()?->consultant->user->name ?? '—';
            $niveles = $s->schoolLevels->map(fn($sl) => $sl->level->name ?? '')->filter()->join(', ');
            $meeAdmin = $s->meeAdmins->first()?->username ?? '—';

            $ws5->fromArray([
                $s->name, $s->state ?? $s->city ?? '—', $s->nexus_id ?? '—',
                $digital, $eca, $elt, $ventas, $niveles,
                $s->students->count(), $meeAdmin,
            ], null, "A{$row}");

            if ($i % 2) $this->stripeRow($ws5, $row, 10);
            $row++;
        }

        $this->setWidths($ws5, [38,22,16,26,26,26,26,30,12,24]);

        // ── HOJA 6: PERSONALIZACIÓN COLEGIO ──────────────────────────────
        $personalizados = School::with([
            'schoolConsultants.consultant.user',
            'schoolLevels.level',
            'schoolLevels.processes',
            'students',
            'meeAdmins',
        ])->where('custom_passwords', true)
          ->orderBy('name')
          ->get();

        $ws6 = $spreadsheet->createSheet(5);
        $ws6->setTitle('Personalización Colegio');

        $this->sheetTitle($ws6, "MacmillanSI — Personalización Colegio ({$personalizados->count()})", '0891B2', 10, $generado);

        $headers6 = ['Nombre', 'Estado', 'Nexus ID', 'Estatus', 'Consultor Digital',
                     'Consultor ECA', 'Consultor ELT', 'Niveles', 'Progreso %', 'Alumnos SI'];
        $this->writeHeaders($ws6, 4, $headers6, '0891B2');

        $row = 5;
        foreach ($personalizados as $i => $s) {
            $cons    = $s->schoolConsultants;
            $digital = $cons->where('role','digital')->first()?->consultant->user->name ?? '—';
            $eca     = $cons->where('role','eca')    ->first()?->consultant->user->name ?? '—';
            $elt     = $cons->where('role','elt')    ->first()?->consultant->user->name ?? '—';
            $niveles = $s->schoolLevels->map(fn($sl) => $sl->level->name ?? '')->filter()->join(', ');

            $totalP = 0; $doneP = 0;
            foreach ($s->schoolLevels as $sl) {
                $totalP += $sl->processes->count();
                $doneP  += $sl->processes->where('status','done')->count();
            }
            $pct = $totalP > 0 ? round(($doneP / $totalP) * 100) . '%' : '0%';

            $ws6->fromArray([
                $s->name, $s->state ?? $s->city ?? '—', $s->nexus_id ?? '—',
                ucfirst($s->status), $digital, $eca, $elt,
                $niveles, $pct, $s->students->count(),
            ], null, "A{$row}");

            if ($i % 2) $this->stripeRow($ws6, $row, 10);
            $row++;
        }

        $this->setWidths($ws6, [38,22,16,14,26,26,26,30,12,12]);

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

    // ─────────────────────────────────────────────────────────────────────
    // Report Master — reporte completo de un colegio (7 hojas)
    // ─────────────────────────────────────────────────────────────────────
    public function reporteMaster(School $school)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'consultor_digital']), 403);

        $school->load([
            'schoolConsultants.consultant.user',
            'schoolLevels.level',
            'schoolLevels.processes.process',
            'teachers.roles',
            'students',
            'tickets',
            'visits.consultant.user',
            'bundles',
        ]);

        $generado  = now()->format('d/m/Y H:i');
        $RED       = 'C0392B';
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        // ── Stats generales ──────────────────────────────────────────────
        $totalProcesos = 0; $doneProcesos = 0;
        foreach ($school->schoolLevels as $sl) {
            $totalProcesos += $sl->processes->count();
            $doneProcesos  += $sl->processes->where('status', 'done')->count();
        }
        $pctProcesos = $totalProcesos > 0 ? round(($doneProcesos / $totalProcesos) * 100) : 0;

        $totalTickets    = $school->tickets->count();
        $abiertos        = $school->tickets->where('status', 'abierto')->count();
        $enProceso       = $school->tickets->where('status', 'en_proceso')->count();
        $resueltos       = $school->tickets->where('status', 'resuelto')->count();
        $totalVisitas    = $school->visits->count();
        $visitasTerminadas = $school->visits->where('status', 'terminada')->count();
        $totalDocentes   = $school->teachers->count();
        $totalAlumnos    = $school->students->count();
        $totalBundles    = $school->bundles->count();

        $digital = $school->schoolConsultants->where('role','digital')->first()?->consultant->user->name ?? '—';
        $eca     = $school->schoolConsultants->where('role','eca')->first()?->consultant->user->name ?? '—';
        $elt     = $school->schoolConsultants->where('role','elt')->first()?->consultant->user->name ?? '—';
        $ventas  = $school->schoolConsultants->where('role','ventas')->first()?->consultant->user->name ?? '—';

        // ══════════════════════════════════════════════════════════════════
        // HOJA 1: RESUMEN
        // ══════════════════════════════════════════════════════════════════
        $ws = $spreadsheet->createSheet(0);
        $ws->setTitle('Resumen');

        // Título
        $ws->setCellValue('A1', '📋 REPORT MASTER — ' . strtoupper($school->name));
        $ws->setCellValue('A2', 'Generado el ' . $generado . '   |   Nexus ID: ' . ($school->nexus_id ?? '—'));
        $ws->mergeCells('A1:F1');
        $ws->mergeCells('A2:F2');
        $ws->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $RED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '555555']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF5F5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(36);
        $ws->getRowDimension(2)->setRowHeight(18);

        // ── Información del colegio ──
        $row = 4;
        $ws->setCellValue("A{$row}", 'INFORMACIÓN DEL COLEGIO');
        $ws->mergeCells("A{$row}:F{$row}");
        $ws->getStyle("A{$row}:F{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $RED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $ws->getRowDimension($row)->setRowHeight(20);
        $row++;

        $infoRows = [
            ['Estado / Ciudad', $school->state ?? $school->city ?? '—', 'Estatus', ucfirst($school->status ?? '—'), 'Teléfono', $school->phone ?? '—'],
            ['Email', $school->email ?? '—', 'Consultor Digital', $digital, 'Consultor ECA', $eca],
            ['Consultor ELT', $elt, 'Rep. Ventas', $ventas, 'Dirección', $school->address ?? '—'],
        ];
        foreach ($infoRows as $r) {
            $ws->fromArray($r, null, "A{$row}");
            $ws->getStyle("A{$row}")->getFont()->setBold(true)->getColor()->setRGB('555555');
            $ws->getStyle("C{$row}")->getFont()->setBold(true)->getColor()->setRGB('555555');
            $ws->getStyle("E{$row}")->getFont()->setBold(true)->getColor()->setRGB('555555');
            $ws->getStyle("A{$row}:F{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF2F2']],
            ]);
            $row++;
        }

        // ── Tarjetas de resumen ──
        $row++;
        $ws->setCellValue("A{$row}", 'RESUMEN GENERAL');
        $ws->mergeCells("A{$row}:F{$row}");
        $ws->getStyle("A{$row}:F{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $RED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $ws->getRowDimension($row)->setRowHeight(20);
        $row++;

        $this->writeHeaders($ws, $row, ['Sección', 'Total', 'Detalle', '', '', ''], $RED);
        $ws->mergeCells("C{$row}:F{$row}");
        $row++;

        $statsCards = [
            ['⚙️ Acciones de Arranque', "{$doneProcesos} / {$totalProcesos}", "Completadas: {$doneProcesos}  |  Pendientes: " . ($totalProcesos - $doneProcesos) . "  |  Progreso: {$pctProcesos}%"],
            ['👨‍🏫 Docentes',            $totalDocentes,   'Docentes registrados en el sistema'],
            ['👨‍🎓 Alumnos SI',           $totalAlumnos,    'Alumnos registrados en MEE'],
            ['🎫 Tickets',               $totalTickets,    "Abiertos: {$abiertos}  |  En proceso: {$enProceso}  |  Resueltos: {$resueltos}"],
            ['📅 Visitas',               $totalVisitas,    "Realizadas: {$visitasTerminadas}  |  Pendientes: " . ($totalVisitas - $visitasTerminadas)],
            ['📚 Bundles',               $totalBundles,    'Bundles asignados al colegio'],
        ];

        foreach ($statsCards as $i => $card) {
            $ws->setCellValue("A{$row}", $card[0]);
            $ws->setCellValue("B{$row}", $card[1]);
            $ws->setCellValue("C{$row}", $card[2]);
            $ws->mergeCells("C{$row}:F{$row}");
            $bg = $i % 2 === 0 ? 'FFFFFF' : 'F9F9F9';
            $ws->getStyle("A{$row}:F{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
            ]);
            $ws->getStyle("A{$row}")->getFont()->setBold(true);
            $ws->getStyle("B{$row}")->getFont()->setBold(true)->setSize(13);
            $ws->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->getRowDimension($row)->setRowHeight(20);
            $row++;
        }

        $this->setWidths($ws, [28, 16, 60, 1, 1, 1]);

        // ══════════════════════════════════════════════════════════════════
        // HOJA 2: ACCIONES DE ARRANQUE
        // ══════════════════════════════════════════════════════════════════
        $ws2 = $spreadsheet->createSheet(1);
        $ws2->setTitle('Acciones de Arranque');
        $this->sheetTitle($ws2, "Acciones de Arranque — {$school->name}", $RED, 7, $generado);

        $row = 4;
        foreach ($school->schoolLevels as $sl) {
            $levelName = $sl->level->name ?? 'Nivel';
            $doneL = $sl->processes->where('status','done')->count();
            $totalL = $sl->processes->count();

            $ws2->setCellValue("A{$row}", strtoupper($levelName) . "   ({$doneL}/{$totalL} completados)");
            $ws2->mergeCells("A{$row}:G{$row}");
            $ws2->getStyle("A{$row}:G{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7F1D1D']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
            ]);
            $ws2->getRowDimension($row)->setRowHeight(20);
            $row++;

            $this->writeHeaders($ws2, $row, ['#', 'Proceso', 'Status', 'Completado Por', 'Fecha', 'Notas', 'Evidencia'], $RED);
            $row++;

            foreach ($sl->processes->sortBy('process.order') as $idx => $slp) {
                $status = match($slp->status) {
                    'done'        => '✅ Completado',
                    'in_progress' => '🔄 En proceso',
                    'reopened'    => '🔁 Reabierto',
                    default       => '⏳ Pendiente',
                };
                $ws2->fromArray([
                    $slp->process->order ?? ($idx + 1),
                    $slp->process->name ?? '—',
                    $status,
                    $slp->completed_by ?? '—',
                    $slp->completed_at ? \Carbon\Carbon::parse($slp->completed_at)->format('d/m/Y') : '—',
                    $slp->notes ?? '—',
                ], null, "A{$row}");

                $hasImg = false;
                if ($slp->evidence) {
                    $hasImg = $this->embedOrLink($ws2, "G{$row}", $slp->evidence);
                } else {
                    $ws2->setCellValue("G{$row}", '—');
                }

                $bg = $slp->status === 'done' ? 'F0FFF4' : ($idx % 2 === 0 ? 'FFFFFF' : 'F9F9F9');
                $ws2->getStyle("A{$row}:G{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                ]);
                if ($hasImg) $ws2->getRowDimension($row)->setRowHeight(55);
                $row++;
            }
            $row++;
        }
        $this->setWidths($ws2, [5, 36, 18, 22, 14, 40, 40]);

        // ══════════════════════════════════════════════════════════════════
        // HOJA 3: DOCENTES
        // ══════════════════════════════════════════════════════════════════
        $ws3 = $spreadsheet->createSheet(2);
        $ws3->setTitle('Docentes');
        $this->sheetTitle($ws3, "Docentes — {$school->name} ({$totalDocentes})", '1D4ED8', 8, $generado);
        $this->writeHeaders($ws3, 4, ['Nombre', 'Apellidos', 'Email', 'Grado', 'Materia', 'Roles', 'Usuario MEE', 'Contraseña MEE'], '1D4ED8');

        $row = 5;
        foreach ($school->teachers->sortBy('name')->values() as $i => $t) {
            $roles = $t->roles->map(fn($r) => Teacher::ROLES[$r->role] ?? $r->role)->join(', ');
            $ws3->fromArray([
                $t->name, $t->last_name ?? '—', $t->email ?? '—',
                $t->grade ?? '—', strtoupper($t->subject ?? '—'), $roles ?: '—',
                $t->mee_username ?? '—', $t->mee_password ?? '—',
            ], null, "A{$row}");
            if ($i % 2) $this->stripeRow($ws3, $row, 8);
            $row++;
        }
        $this->setWidths($ws3, [22, 22, 30, 12, 12, 32, 22, 22]);

        // ══════════════════════════════════════════════════════════════════
        // HOJA 4: ALUMNOS
        // ══════════════════════════════════════════════════════════════════
        $ws4 = $spreadsheet->createSheet(3);
        $ws4->setTitle('Alumnos');
        $this->sheetTitle($ws4, "Alumnos — {$school->name} ({$totalAlumnos})", '059669', 6, $generado);
        $this->writeHeaders($ws4, 4, ['Nombre', 'Apellidos', 'Nivel', 'Grado', 'Usuario MEE', 'Contraseña MEE'], '059669');

        $row = 5;
        foreach ($school->students->sortBy(['level','name'])->values() as $i => $s) {
            $ws4->fromArray([
                $s->name, $s->last_name ?? '—', ucfirst($s->level ?? '—'),
                $s->grade ?? '—', $s->mee_username ?? '—', $s->mee_password ?? '—',
            ], null, "A{$row}");
            if ($i % 2) $this->stripeRow($ws4, $row, 6);
            $row++;
        }
        $this->setWidths($ws4, [22, 22, 18, 12, 24, 24]);

        // ══════════════════════════════════════════════════════════════════
        // HOJA 5: TICKETS
        // ══════════════════════════════════════════════════════════════════
        $ws5 = $spreadsheet->createSheet(4);
        $ws5->setTitle('Tickets');
        $this->sheetTitle($ws5, "Tickets — {$school->name} ({$totalTickets})", 'D97706', 7, $generado);
        $this->writeHeaders($ws5, 4, ['Título', 'Descripción', 'Status', 'Prioridad', 'Medio', 'Fecha', 'Evidencia'], 'D97706');

        $row = 5;
        foreach ($school->tickets->sortByDesc('created_at')->values() as $i => $t) {
            $status = match($t->status) {
                'abierto'    => '🔴 Abierto',
                'en_proceso' => '🟡 En proceso',
                'resuelto'   => '🟢 Resuelto',
                default      => $t->status,
            };
            $ws5->fromArray([
                $t->title, $t->description ?? '—', $status,
                ucfirst($t->priority ?? '—'), ucfirst($t->medium ?? '—'),
                $t->created_at->format('d/m/Y'),
            ], null, "A{$row}");

            $hasImg = false;
            if ($t->evidence) {
                $hasImg = $this->embedOrLink($ws5, "G{$row}", $t->evidence);
            } else {
                $ws5->setCellValue("G{$row}", '—');
            }

            if ($i % 2) $this->stripeRow($ws5, $row, 7);
            if ($hasImg) $ws5->getRowDimension($row)->setRowHeight(55);
            $row++;
        }
        $this->setWidths($ws5, [30, 50, 16, 14, 16, 14, 40]);

        // ══════════════════════════════════════════════════════════════════
        // HOJA 6: VISITAS
        // ══════════════════════════════════════════════════════════════════
        $ws6 = $spreadsheet->createSheet(5);
        $ws6->setTitle('Visitas');
        $this->sheetTitle($ws6, "Visitas — {$school->name} ({$totalVisitas})", '7C3AED', 8, $generado);
        $this->writeHeaders($ws6, 4, ['Fecha Programada', 'Fecha Real', 'Status', 'Consultor', 'Notas', 'Resumen', 'Próxima Visita', 'Evidencia'], '7C3AED');

        $row = 5;
        foreach ($school->visits->sortByDesc('scheduled_date') as $i => $v) {
            $statusV = match($v->status) {
                'terminada' => '✅ Terminada',
                'en_curso'  => '🔄 En curso',
                default     => '⏳ Pendiente',
            };
            $ws6->fromArray([
                $v->scheduled_date ? \Carbon\Carbon::parse($v->scheduled_date)->format('d/m/Y') : '—',
                $v->visit_date     ? \Carbon\Carbon::parse($v->visit_date)->format('d/m/Y') : '—',
                $statusV,
                $v->consultant?->user->name ?? '—',
                $v->notes ?? '—',
                $v->summary ?? '—',
                $v->next_visit_date ? \Carbon\Carbon::parse($v->next_visit_date)->format('d/m/Y') : '—',
            ], null, "A{$row}");

            $hasImg = false;
            if ($v->evidence) {
                $hasImg = $this->embedOrLink($ws6, "H{$row}", $v->evidence);
            } else {
                $ws6->setCellValue("H{$row}", '—');
            }

            if ($i % 2) $this->stripeRow($ws6, $row, 8);
            if ($hasImg) $ws6->getRowDimension($row)->setRowHeight(55);
            $row++;
        }
        $this->setWidths($ws6, [18, 14, 16, 26, 40, 40, 18, 40]);

        // ══════════════════════════════════════════════════════════════════
        // HOJA 7: BUNDLES
        // ══════════════════════════════════════════════════════════════════
        $ws7 = $spreadsheet->createSheet(6);
        $ws7->setTitle('Bundles');
        $this->sheetTitle($ws7, "Bundles — {$school->name} ({$totalBundles})", '0E7490', 7, $generado);
        $this->writeHeaders($ws7, 4, ['Título', 'Serie', 'Tipo', 'Nivel', 'Grado', 'Rol', 'Cantidad'], '0E7490');

        $row = 5;
        foreach ($school->bundles->sortBy(['level','grade'])->values() as $i => $b) {
            $ws7->fromArray([
                $b->name, $b->serie ?? '—', strtoupper($b->type ?? '—'),
                ucfirst($b->level ?? '—'), $b->grade ?? '—',
                ucfirst($b->role ?? '—'), $b->pivot->quantity ?? '—',
            ], null, "A{$row}");
            if ($i % 2) $this->stripeRow($ws7, $row, 7);
            $row++;
        }
        $this->setWidths($ws7, [50, 28, 10, 16, 10, 14, 12]);

        // ── Exportar ─────────────────────────────────────────────────────
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'report-master-' . \Str::slug($school->name) . '-' . now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'crm_master_');
        try {
            (new Xlsx($spreadsheet))->save($tempFile);
        } catch (\Throwable $e) {
            @unlink($tempFile);
            throw $e;
        }

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Helper: incrustar imagen o poner URL según tipo de archivo ─────────
    private function embedOrLink(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws, string $cell, string $storagePath): bool
    {
        $fullPath = storage_path('app/public/' . $storagePath);
        if (!file_exists($fullPath)) {
            $ws->setCellValue($cell, url('storage/' . $storagePath));
            return false;
        }
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
            try {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setPath($fullPath);
                $drawing->setHeight(60);
                $drawing->setCoordinates($cell);
                $drawing->setOffsetX(4);
                $drawing->setOffsetY(4);
                $drawing->setWorksheet($ws);
                return true;
            } catch (\Throwable) {
                // Si falla la imagen, cae al enlace
            }
        }
        $ws->setCellValue($cell, url('storage/' . $storagePath));
        return false;
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
