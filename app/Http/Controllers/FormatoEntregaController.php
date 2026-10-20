<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FormatoEntregaController extends Controller
{
    private const HEADERS = [
        'Nombre', 'Apellido paterno', 'Apellido materno', 'Usuario',
        'Contraseña', 'Nivel', 'Grado', 'Grupo', 'Género',
    ];

    private const TEACHER_HEADERS = [
        'Nombre', 'Apellido paterno', 'Apellido materno', 'Usuario',
        'Contraseña', 'Grado', 'Grupo', 'Género',
    ];

    public function download(School $school, Request $request)
    {
        set_time_limit(120);

        $tipo = $request->query('tipo', 'alumnos');
        abort_unless(in_array($tipo, ['alumnos', 'docentes'], true), 422);
        $alumnos = $tipo === 'alumnos' ? $this->studentData($school) : null;
        $docentes = $tipo === 'docentes' ? $this->teacherData($school) : null;

        if (($alumnos !== null && $alumnos['rows'] === []) || ($docentes !== null && $docentes['rows'] === [])) {
            return response()->json([
                'message' => $tipo === 'alumnos'
                    ? 'No hay alumnos cargados para generar el formato.'
                    : 'No hay docentes cargados para generar el formato.',
            ], 422);
        }

        $spreadsheet = new Spreadsheet();
        $usedTitles = [];
        if ($alumnos !== null) {
            $summary = $spreadsheet->getActiveSheet();
            $summary->setTitle($this->uniqueSheetTitle('Todos los alumnos', $usedTitles));
            $this->drawSheet($summary, $school, 'Concentrado general', $this->countLabel(count($alumnos['rows']), 'alumno', 'alumnos'), $alumnos['rows']);
            foreach ($alumnos['classes'] as $class => $rows) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle($this->uniqueSheetTitle($class, $usedTitles));
                $this->drawSheet($sheet, $school, 'Listado de alumnos', $class . '   |   ' . $this->countLabel(count($rows), 'alumno', 'alumnos'), $rows);
            }
        }

        if ($docentes !== null) {
            $summary = $spreadsheet->getActiveSheet();
            $summary->setTitle($this->uniqueSheetTitle('Todos los docentes', $usedTitles));
            $this->drawSheet(
                $summary,
                $school,
                'Concentrado de docentes',
                $this->countLabel(count($docentes['rows']), 'docente', 'docentes'),
                $docentes['rows'],
                self::TEACHER_HEADERS,
                8,
                [6, 7, 8],
            );

            foreach ($docentes['classes'] as $grade => $rows) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle($this->uniqueSheetTitle('Docentes - ' . $grade, $usedTitles));
                $this->drawSheet(
                    $sheet,
                    $school,
                    'Listado de docentes',
                    'Grado ' . $grade . '   |   ' . $this->countLabel(count($rows), 'docente', 'docentes'),
                    $rows,
                    self::TEACHER_HEADERS,
                    8,
                    [6, 7, 8],
                );
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        ActivityLog::log('formato_entrega', "Formato de entrega (Excel) de {$tipo} descargado para \"{$school->name}\"", $school->id, '📥');
        $tempFile = tempnam(sys_get_temp_dir(), 'formato_entrega_');
        (new Xlsx($spreadsheet))->save($tempFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $safeSchoolName = str_replace(['<', '>', ':', '"', '/', '\\', '|', '?', '*'], '-', $school->name);
        $safeSchoolName = trim($safeSchoolName, " .");
        $schoolIdentifier = $school->nexus_id ?: $school->id;
        $safeSchoolIdentifier = str_replace(['<', '>', ':', '"', '/', '\\', '|', '?', '*'], '-', (string) $schoolIdentifier);
        $safeSchoolIdentifier = trim($safeSchoolIdentifier, " .");
        $filenamePrefix = $tipo === 'docentes' ? 'Cuentas de Docentes' : 'Cuentas de Alumno';
        $filename = $filenamePrefix . '_' . $safeSchoolName . '_' . $safeSchoolIdentifier . '.xlsx';

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'X-Alumnos-Por-Revisar' => $alumnos['reviewCount'] ?? 0,
            'X-Clases-Alumnos' => $alumnos['classCount'] ?? 0,
            'X-Docentes-Por-Revisar' => $docentes['reviewCount'] ?? 0,
            'X-Grados-Docentes' => $docentes['classCount'] ?? 0,
        ])->deleteFileAfterSend(true);
    }

    public function accesos(School $school, Request $request)
    {
        $tipo = $request->query('tipo', 'alumnos');
        abort_unless(in_array($tipo, ['alumnos', 'docentes'], true), 422);
        $data = $tipo === 'alumnos' ? $this->studentData($school) : $this->teacherData($school);

        if ($data['rows'] === []) {
            return response()->json(['message' => 'No hay registros para generar las tarjetas.'], 422);
        }

        $groups = [];
        $omitidos = 0;
        foreach ($data['classes'] as $class => $rows) {
            $cards = [];
            foreach ($rows as $r) {
                if (trim((string) $r[3]) === '' || trim((string) $r[4]) === '') {
                    $omitidos++;
                    continue;
                }
                $cards[] = [
                    'name' => trim(implode(' ', array_filter([$r[0], $r[1], $r[2]]))),
                    'username' => $r[3],
                    'password' => $r[4],
                ];
            }
            if ($cards !== []) {
                $groups[] = [
                    'clase' => $tipo === 'docentes' ? 'Docentes - ' . $class : $class,
                    'alumnos' => $cards,
                ];
            }
        }

        if ($groups === []) {
            return response()->json(['message' => 'Ningún registro tiene usuario y contraseña para generar tarjetas.'], 422);
        }

        ActivityLog::log('formato_entrega', "Tarjetas de acceso de {$tipo} generadas para \"{$school->name}\"", $school->id, '🪪');

        return response()->json([
            'school' => $school->name,
            'link' => config('services.global_educa.access_url') ?: null,
            'omitidos' => $omitidos,
            'groups' => $groups,
        ]);
    }

    public function summaryForSchool(School $school): array
    {
        $alumnos = $this->studentData($school, false);
        $docentes = $this->teacherData($school);

        return [
            'reviewCount' => $alumnos['reviewCount'],
            'classCount' => $alumnos['classCount'],
            'studentReviewCount' => $alumnos['reviewCount'],
            'studentClassCount' => $alumnos['classCount'],
            'teacherReviewCount' => $docentes['reviewCount'],
            'teacherClassCount' => $docentes['classCount'],
            'studentCount' => $alumnos['recordCount'],
            'teacherCount' => $docentes['recordCount'],
        ];
    }

    private function studentData(School $school, bool $includeCredentials = true): array
    {
        $columns = $includeCredentials
            ? ['name', 'last_name', 'mee_username', 'mee_password', 'level', 'grade']
            : ['name', 'level', 'grade'];
        $students = $school->students()
            ->orderBy('level')->orderBy('grade')->orderBy('last_name')->orderBy('name')
            ->get($columns);
        $classes = [];
        $rows = [];
        $reviewCount = 0;

        foreach ($students as $student) {
            [$grade, $group] = $this->splitGradeAndGroup((string) $student->grade);
            $level = trim((string) $student->level);
            $class = trim(implode(' ', array_filter([
                $level ?: 'Sin nivel',
                $grade !== '' ? $grade . ($group !== '' ? '°' . $group : '') : 'Sin grado',
            ])));
            $classes[$class] ??= [];

            if (!$includeCredentials) {
                if ($this->inferGender((string) $student->name) === 'Revisar') {
                    $reviewCount++;
                }
                continue;
            }

            $row = [
                trim((string) $student->name),
                ...$this->splitSurnames((string) $student->last_name),
                trim((string) $student->mee_username),
                trim((string) $student->mee_password),
                $level,
                $grade,
                $group,
                $this->inferGender((string) $student->name),
            ];

            if ($row[8] === 'Revisar') {
                $reviewCount++;
            }

            $classes[$class][] = $row;
            $rows[] = $row;
        }

        uksort($classes, fn ($a, $b) => strnatcasecmp($a, $b));

        return [
            'classes' => $classes,
            'rows' => $rows,
            'reviewCount' => $reviewCount,
            'classCount' => count($classes),
            'recordCount' => $students->count(),
        ];
    }

    private function teacherData(School $school): array
    {
        $teachers = $school->teachers()
            ->orderBy('grade')->orderBy('last_name')->orderBy('name')
            ->get(['id', 'name', 'last_name', 'email', 'mee_password', 'grade']);
        $classes = [];
        $rows = [];
        $reviewCount = 0;

        foreach ($teachers as $teacher) {
            [$grade, $group] = $this->splitGradeAndGroup((string) $teacher->grade);
            $grade = $grade !== '' ? $grade : 'Sin grado';
            $class = $grade . ($group !== '' ? '°' . $group : '');
            $gender = $this->inferGender((string) $teacher->name);
            $credentialsReview = trim((string) $teacher->email) === ''
                || trim((string) $teacher->mee_password) === '';
            $row = [
                trim((string) $teacher->name),
                ...$this->splitSurnames((string) $teacher->last_name),
                trim((string) $teacher->email),
                trim((string) $teacher->mee_password),
                $grade,
                $group,
                $gender,
            ];

            if ($credentialsReview || $gender === 'Revisar') {
                $reviewCount++;
            }
            $classes[$class][] = $row;
            $rows[] = $row;
        }

        uksort($classes, fn ($a, $b) => strnatcasecmp($a, $b));

        return [
            'classes' => $classes,
            'rows' => $rows,
            'reviewCount' => $reviewCount,
            'classCount' => count($classes),
            'recordCount' => $teachers->count(),
        ];
    }

    public function script(School $school)
    {
        return response(file_get_contents(public_path('js/formato-entrega.js')), 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function drawSheet(
        $sheet,
        School $school,
        string $title,
        string $subtitle,
        array $rows,
        array $headers = self::HEADERS,
        ?int $reviewColumn = 9,
        array $centeredColumns = [6, 7, 8, 9],
    ): void
    {
        $columnCount = count($headers);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $lastRow = max(6, 6 + count($rows));
        $red = 'DA0920';
        $charcoal = '3A3939';

        $sheet->getDefaultColumnDimension()->setWidth(14);
        $sheet->getDefaultRowDimension()->setRowHeight(24);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getFont()->setName('Segoe UI')->setSize(10)->getColor()->setRGB($charcoal);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(8);
        $sheet->getRowDimension(2)->setRowHeight(34);
        $sheet->getRowDimension(3)->setRowHeight(24);
        $sheet->getRowDimension(4)->setRowHeight(4);
        $sheet->getRowDimension(5)->setRowHeight(12);

        $sheet->mergeCells("C2:{$lastColumn}2");
        $sheet->setCellValue("C2", $school->name);
        $sheet->getStyle("C2:{$lastColumn}2")->applyFromArray([
            'font' => ['name' => 'Segoe UI', 'bold' => true, 'size' => $this->schoolNameFontSize($school->name), 'color' => ['rgb' => $charcoal]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $sheet->mergeCells("C3:{$lastColumn}3");
        $sheet->setCellValue("C3", $title . '   |   ' . $subtitle);
        $sheet->getStyle("C3:{$lastColumn}3")->applyFromArray([
            'font' => ['name' => 'Segoe UI', 'size' => 11, 'color' => ['rgb' => $red]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A4:{$lastColumn}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($red);

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$column}6", $header);
        }
        $sheet->getStyle("A6:{$lastColumn}6")->applyFromArray([
            'font' => ['name' => 'Segoe UI', 'bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $charcoal]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension(6)->setRowHeight(28);

        foreach ($rows as $rowIndex => $values) {
            $excelRow = $rowIndex + 7;
            $sheet->getRowDimension($excelRow)->setRowHeight(24);
            foreach ($values as $columnIndex => $value) {
                $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
                $sheet->setCellValueExplicit("{$column}{$excelRow}", (string) $value, DataType::TYPE_STRING);
            }

            if ($rowIndex % 2 === 1) {
                $sheet->getStyle("A{$excelRow}:{$lastColumn}{$excelRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F6F6F7');
            }
            if ($reviewColumn !== null && ($values[$reviewColumn - 1] ?? null) === 'Revisar') {
                $reviewColumnName = Coordinate::stringFromColumnIndex($reviewColumn);
                $sheet->getStyle("{$reviewColumnName}{$excelRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $red]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE9EB']],
                ]);
            }
        }

        if ($rows !== []) {
            $sheet->getStyle("A7:{$lastColumn}{$lastRow}")->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                'borders' => [
                    'insideHorizontal' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E3E3E5']],
                ],
            ]);
            $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($red);
        }

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $horizontal = in_array($columnIndex, $centeredColumns, true)
                ? Alignment::HORIZONTAL_CENTER
                : Alignment::HORIZONTAL_LEFT;
            $sheet->getStyle("{$column}6:{$column}{$lastRow}")->getAlignment()
                ->setHorizontal($horizontal)->setIndent($horizontal === Alignment::HORIZONTAL_CENTER ? 0 : 1);
        }

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $logoPath = public_path('images/logo-formato-entrega.png');
        if (is_file($logoPath)) {
            $logo = new Drawing();
            $logo->setName('Macmillan Castillo SI');
            $logo->setDescription('Macmillan Castillo SI');
            $logo->setPath($logoPath);
            $logo->setCoordinates('A2');
            $logo->setHeight(54);
            $logo->setOffsetX(8);
            $logo->setOffsetY(2);
            $logo->setWorksheet($sheet);
        }

        $sheet->freezePane('A7');
        $sheet->setShowGridlines(false);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd(6, 6);
        $sheet->getPageSetup()->setScale(0);
        $sheet->getPageMargins()->setLeft(0.5)->setRight(0.5)->setTop(0.6)->setBottom(0.7);
        $sheet->getHeaderFooter()->setOddFooter('&LMacmillan Castillo  |  Solución integral&R Página &P de &N');
        $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");
    }

    private function splitGradeAndGroup(string $grade): array
    {
        $grade = trim($grade);
        if (preg_match('/^(.+?)\s*°\s*([[:alpha:]])$/u', $grade, $matches)) {
            return [trim($matches[1]), mb_strtoupper($matches[2])];
        }
        if (preg_match('/^(.+?)\s*°\s*$/u', $grade, $matches)) {
            return [trim($matches[1]), ''];
        }
        if (preg_match('/^(.+?)\s+([[:alpha:]])$/u', $grade, $matches)) {
            return [trim($matches[1]), mb_strtoupper($matches[2])];
        }

        return [$grade, ''];
    }

    private function splitSurnames(string $lastName): array
    {
        $tokens = preg_split('/\s+/u', trim($lastName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $particles = ['de', 'del', 'la', 'las', 'los', 'da', 'das', 'dos', 'di', 'van', 'von', 'san', 'santa'];
        $units = [];
        $pending = [];
        $joinNext = false;

        foreach ($tokens as $token) {
            $lower = mb_strtolower($token);
            if (in_array($lower, $particles, true)) {
                $pending[] = $token;
            } elseif (in_array($lower, ['y', 'e'], true) && $units !== [] && $pending === []) {
                $units[array_key_last($units)] .= ' ' . $token;
                $joinNext = true;
            } else {
                $word = implode(' ', [...$pending, $token]);
                $pending = [];
                if ($joinNext) {
                    $units[array_key_last($units)] .= ' ' . $word;
                    $joinNext = false;
                } else {
                    $units[] = $word;
                }
            }
        }

        if ($pending !== []) {
            if ($units === []) {
                $units[] = implode(' ', $pending);
            } else {
                $units[array_key_last($units)] .= ' ' . implode(' ', $pending);
            }
        }

        return [$units[0] ?? '', implode(' ', array_slice($units, 1))];
    }

    private function inferGender(string $fullName): string
    {
        $firstName = preg_split('/[\s-]+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY)[0] ?? '';
        $firstName = mb_strtolower(Str::ascii(trim($firstName, '.')));
        static $unisex = [
            'alex', 'ariel', 'cruz', 'guadalupe', 'noel', 'sasha', 'yael', 'jordan', 'sam',
            'reyes', 'jean', 'dominique', 'ali', 'jessie', 'taylor', 'cameron', 'morgan',
            'riley', 'avery', 'dakota', 'skyler', 'peyton', 'parker', 'quinn', 'reese',
            'rowan', 'sage', 'sidney', 'sydney', 'jamie', 'robin', 'lee', 'blake', 'noor',
            'kim', 'terry', 'tracy', 'michel', 'nicola', 'claude', 'sacha', 'yuki', 'paris',
            'lupe', 'trinidad', 'natividad', 'lian', 'nikita', 'simone', 'cory', 'jaden',
            'jayden', 'jaiden', 'kaden', 'emerson', 'finley', 'jules', 'tatum', 'toni', 'yuri',
        ];
        static $female = [
            'ma', 'maria', 'ana', 'sofia', 'valentina', 'camila', 'regina', 'ximena', 'valeria',
            'mariana', 'daniela', 'fernanda', 'renata', 'victoria', 'isabella', 'isabel', 'luciana',
            'paula', 'andrea', 'natalia', 'gabriela', 'alejandra', 'ariadna', 'jimena', 'melanie',
            'romina', 'emily', 'ashley', 'dulce', 'abril', 'karla', 'carla', 'paola', 'mia', 'lucia',
            'julieta', 'samantha', 'danna', 'dana', 'sara', 'sarahi', 'nayeli', 'itzel', 'citlali',
            'xochitl', 'frida', 'elena', 'emma', 'alexa', 'alondra', 'aylin', 'ailen', 'amy', 'angela',
            'angelica', 'ariana', 'arely', 'ayleen', 'barbara', 'brenda', 'briana', 'bianca', 'carolina',
            'catalina', 'cecilia', 'claudia', 'cristina', 'dayana', 'dafne', 'estefania', 'evelyn',
            'fatima', 'flor', 'giselle', 'helena', 'irene', 'iris', 'ivanna', 'ivana', 'jazmin',
            'jennifer', 'jessica', 'joana', 'juana', 'julia', 'karen', 'karina', 'kimberly', 'laura',
            'leslie', 'liliana', 'lizbeth', 'lourdes', 'luisa', 'luz', 'magdalena', 'margarita', 'marisol',
            'martha', 'marta', 'mayte', 'mercedes', 'michelle', 'miranda', 'mireya', 'monica', 'montserrat',
            'naomi', 'nancy', 'nicole', 'noemi', 'nora', 'olivia', 'patricia', 'perla', 'rebeca', 'rocio',
            'rosa', 'rosario', 'ruth', 'sabrina', 'sandra', 'selena', 'silvia', 'sonia', 'tania', 'teresa',
            'thalia', 'valery', 'vanessa', 'veronica', 'viviana', 'wendy', 'yaretzi', 'yazmin', 'yesenia',
            'zoe', 'zulema', 'allison', 'samadhi', 'zuri', 'abigail', 'adriana', 'aileen', 'alba', 'alice',
            'alma', 'amalia', 'amanda', 'amelia', 'anahi', 'anna', 'aranza', 'aurora', 'azul', 'beatriz',
            'belen', 'bella', 'brisa', 'celeste', 'chloe', 'cielo', 'coral', 'cristal', 'dalia', 'denisse',
            'edith', 'elisa', 'elizabeth', 'erika', 'esmeralda', 'esther', 'eva', 'fabiola', 'gloria',
            'graciela', 'grecia', 'hannah', 'ingrid', 'ines', 'ivonne', 'jacqueline', 'jocelyn', 'josefina',
            'judith', 'kenia', 'kelly', 'lidia', 'lorena', 'lupita', 'marcela', 'maribel', 'miriam', 'nadia',
            'nelly', 'nidia', 'pamela', 'penelope', 'raquel', 'roxana', 'salma', 'sarai', 'scarlett',
            'shirley', 'soledad', 'susana', 'tatiana', 'ursula', 'yadira', 'yolanda', 'zaira',
            'ainhoa', 'alana', 'alexia', 'alison', 'astrid', 'berenice', 'bertha', 'camelia',
            'candela', 'carmen', 'casandra', 'cassandra', 'cassidy', 'charlotte', 'chelsea',
            'cheryl', 'chiara', 'cindy', 'clara', 'clarissa', 'colette', 'consuelo', 'courtney',
            'cynthia', 'daisy', 'dania', 'danae', 'danielle', 'daphne', 'darlene', 'deborah',
            'debora', 'diane', 'donna', 'dora', 'dorothy', 'eileen', 'elaine', 'eleanor', 'ella',
            'ellie', 'eloisa', 'elsa', 'emilia', 'erin', 'estela', 'estelle', 'estrella', 'eugenia',
            'eve', 'faith', 'felicia', 'fiona', 'flora', 'francesca', 'francisca', 'gabriella',
            'gemma', 'gianna', 'gina', 'giovanna', 'grace', 'greta', 'hailey', 'haley', 'hana',
            'hazel', 'heather', 'heidi', 'helen', 'holly', 'hope', 'ida', 'idalia', 'ileana',
            'imelda', 'irina', 'irma', 'isela', 'isabelle', 'itzia', 'ivette', 'ivy', 'jade',
            'jana', 'jane', 'janice', 'jasmine', 'jeanne', 'jenna', 'jenny', 'jillian', 'joanna',
            'johanna', 'jordyn', 'josefa', 'joselyn', 'joseline', 'josephine', 'joy', 'joyce',
            'juanita', 'julianna', 'julie', 'julissa', 'june', 'kara', 'karime', 'karin', 'karol',
            'kassandra', 'kate', 'katherine', 'kathleen', 'katie', 'kayla', 'keira', 'kelsey',
            'kendra', 'keyla', 'kiara', 'kira', 'kristen', 'kristina', 'kylie', 'lana', 'lara',
            'lauren', 'layla', 'lea', 'leah', 'leila', 'lena', 'leticia', 'lia', 'lila', 'lilia',
            'lilly', 'lily', 'lina', 'linda', 'lindsay', 'lisa', 'lisbeth', 'livia', 'lola', 'lucie',
            'lucy', 'luna', 'lydia', 'mabel', 'mackenzie', 'madeline', 'madelyn', 'madison', 'maeve',
            'maia', 'maite', 'malia', 'manon', 'margaret', 'margot', 'marina', 'marissa', 'martina',
            'mary', 'maya', 'mayra', 'megan', 'melisa', 'melissa', 'micaela', 'michaela', 'milagros',
            'mila', 'mina', 'molly', 'nadine', 'nathalie', 'natalie', 'nelida', 'nereida', 'nina',
            'noelia', 'nuria', 'octavia', 'olga', 'oriana', 'paloma', 'paulina', 'pauline', 'phoebe',
            'pilar', 'priscila', 'priscilla', 'rafaela', 'ramona', 'reina', 'reyna', 'renee', 'rita',
            'roberta', 'rosalia', 'rosemary', 'rose', 'rubi', 'ruby', 'sabina', 'sadie', 'sally',
            'sarah', 'savannah', 'selene', 'serena', 'shakira', 'sharon', 'sheila', 'sheyla',
            'sienna', 'silvana', 'sofie', 'sophie', 'stacy', 'stefania', 'stefanie', 'stella',
            'summer', 'sylvia', 'tabitha', 'talia', 'tamara', 'tatyana', 'tessa', 'tiffany', 'valerie',
            'vera', 'violeta', 'violet', 'virginia', 'vivian', 'willow', 'xiomara', 'yamila', 'yara',
            'yareli', 'yaremi', 'yasmin', 'yasmine', 'yvette', 'zahra', 'zara', 'zeltzin', 'zoey',
        ];
        static $male = [
            'jose', 'juan', 'luis', 'carlos', 'jesus', 'miguel', 'angel', 'francisco', 'antonio', 'pedro',
            'manuel', 'javier', 'jorge', 'alejandro', 'daniel', 'david', 'eduardo', 'fernando', 'ricardo',
            'roberto', 'sergio', 'rafael', 'mario', 'oscar', 'victor', 'hector', 'raul', 'arturo', 'alberto',
            'adrian', 'andres', 'diego', 'emiliano', 'santiago', 'mateo', 'sebastian', 'leonardo', 'matias',
            'nicolas', 'samuel', 'gael', 'thiago', 'tadeo', 'ian', 'iker', 'dylan', 'bruno', 'axel',
            'christian', 'cristian', 'kevin', 'brayan', 'bryan', 'brian', 'jonathan', 'joshua', 'josue',
            'isaac', 'ismael', 'ivan', 'aaron', 'abraham', 'adan', 'agustin', 'aldo', 'alan', 'alexis',
            'alfredo', 'alvaro', 'anthony', 'armando', 'benjamin', 'bernardo', 'camilo', 'cesar', 'damian',
            'dario', 'edgar', 'elias', 'emanuel', 'emmanuel', 'enrique', 'erick', 'erik', 'ernesto', 'esteban',
            'ethan', 'eithan', 'evan', 'fabian', 'felipe', 'gabriel', 'gerardo', 'gilberto', 'gonzalo',
            'gustavo', 'hugo', 'humberto', 'ignacio', 'irving', 'israel', 'jaime', 'jair', 'jared', 'jason',
            'joaquin', 'jonas', 'julian', 'julio', 'kenneth', 'leo', 'lorenzo', 'lucas', 'luca', 'marco',
            'marcos', 'mauricio', 'maximiliano', 'nathan', 'noah', 'noe', 'omar', 'orlando', 'pablo',
            'patricio', 'ramiro', 'ramon', 'rodrigo', 'rogelio', 'ruben', 'salvador', 'saul', 'tomas',
            'ulises', 'uriel', 'valentin', 'vicente', 'xavier', 'yahir', 'yair', 'martin', 'mauro', 'elian',
            'dante', 'liam', 'bastian', 'ivo', 'jafet', 'lionel', 'kaleb', 'caleb', 'alexander', 'abel',
            'adam', 'aiden', 'alfonso', 'andrew', 'angelo', 'arthur', 'austin', 'bautista', 'benito',
            'brandon', 'braulio', 'cain', 'carl', 'carlo', 'carter', 'cedric', 'charles', 'chris', 'ciro',
            'claudio', 'colin', 'connor', 'cristobal', 'damien', 'danny', 'derek', 'edson', 'elijah', 'emilio',
            'emir', 'enzo', 'eric', 'ezequiel', 'fabio', 'felix', 'fidel', 'frank', 'gaston', 'george',
            'guillermo', 'hans', 'henry', 'hiram', 'horacio', 'igor', 'isaiah', 'isidro', 'itzae', 'jack',
            'jacob', 'jairo', 'james', 'johan', 'john', 'joe', 'joel', 'joseph', 'josh', 'kaled', 'khaled',
            'khalid', 'kalet', 'karl', 'keith', 'kian', 'kyle', 'leon', 'leonel', 'logan', 'max', 'maximo',
            'michael', 'moises', 'nathaniel', 'oliver', 'owen', 'patrick', 'paul', 'peter', 'raymundo', 'rene',
            'rick', 'roger', 'ryan', 'simon', 'steven', 'theo', 'thomas', 'timothy', 'tobias', 'tristan',
            'tyler', 'william', 'xander', 'yandel', 'yeshua', 'yoel', 'yoshua', 'ander', 'iktan',
            'abelardo', 'absalon', 'abdiel', 'adalberto', 'adolfo', 'adonai', 'albert', 'albrecht',
            'aldair', 'aldahir', 'alec', 'alfred', 'ambrosio', 'anders', 'andy', 'angelo', 'anton',
            'antoine', 'archer', 'armand', 'arnold', 'arath', 'atlas', 'aurelio', 'azael', 'baltazar',
            'barry', 'bartolo', 'bastien', 'ben', 'bennett', 'bernabe', 'bernard', 'bertrand', 'bill',
            'billy', 'bjorn', 'boris', 'brad', 'bradley', 'brent', 'brett', 'bruce', 'bryce', 'carl',
            'carsten', 'casimiro', 'cayetano', 'charlie', 'chase', 'clark', 'clayton', 'clemens',
            'clifford', 'clinton', 'cody', 'colton', 'conrad', 'constantino', 'corey', 'craig',
            'curtis', 'dagoberto', 'dale', 'dalton', 'damaso', 'darren', 'dave', 'dean', 'dennis',
            'derick', 'devin', 'dexter', 'dieter', 'dillon', 'dimitri', 'dionisio', 'dirk', 'dominic',
            'dominik', 'donald', 'donato', 'doug', 'douglas', 'drew', 'dustin', 'dwayne', 'dwight',
            'eder', 'edgardo', 'eddie', 'edmund', 'edmundo', 'edward', 'edwin', 'efrain', 'eli',
            'elijah', 'eliot', 'elliot', 'elliott', 'emil', 'emile', 'emmett', 'epifanio', 'eros',
            'erwin', 'etienne', 'eusebio', 'evaristo', 'evander', 'francesco', 'francis', 'franklin',
            'frederick', 'gavin', 'gene', 'geoffrey', 'gerald', 'gervasio', 'giovanni', 'giuseppe',
            'glenn', 'gordon', 'graham', 'grant', 'gregory', 'griffin', 'guido', 'gustav', 'harold',
            'harry', 'harvey', 'heinrich', 'henri', 'herbert', 'herman', 'hubert', 'hunter', 'imanol',
            'inaki', 'irvin', 'isaias', 'jacinto', 'jacques', 'jake', 'jakob', 'jan', 'jaret', 'jasiel',
            'jasper', 'jay', 'jeff', 'jefferson', 'jeffrey', 'jehu', 'jens', 'jeremy', 'jeremiah',
            'jerome', 'jerry', 'jerson', 'jhon', 'jhonny', 'jim', 'jimmy', 'johnny', 'johnatan',
            'jonah', 'josafat', 'josias', 'jovani', 'jovanny', 'jurgen', 'kael', 'kai', 'kaleb',
            'kendrick', 'kenny', 'keven', 'klaus', 'kurt', 'lance', 'lars', 'laurent', 'lawrence',
            'leander', 'leandro', 'leobardo', 'leopold', 'levi', 'lewis', 'lincoln', 'lino', 'logan',
            'louis', 'lukas', 'luke', 'lysander', 'macario', 'malcolm', 'manfred', 'marcel', 'marius',
            'marlon', 'marshall', 'marvin', 'mason', 'mathieu', 'matt', 'matthew', 'maurice', 'maxwell',
            'melchor', 'melvin', 'mikhail', 'milan', 'miles', 'milton', 'mitchell', 'nabor', 'nahum',
            'naim', 'natanael', 'neftali', 'neil', 'nelson', 'nestor', 'nicholas', 'nick', 'nikolai',
            'norman', 'octavio', 'olaf', 'olivier', 'osvaldo', 'otoniel', 'otto', 'oziel', 'pascal',
            'pascual', 'philip', 'pierre', 'quentin', 'ralph', 'randy', 'ray', 'raymond', 'reed',
            'reinaldo', 'reinhard', 'remy', 'rex', 'richard', 'ricky', 'rigoberto', 'roland', 'rolando',
            'rolf', 'ronald', 'ronaldo', 'rosendo', 'roy', 'russell', 'sandro', 'santino', 'santos',
            'scott', 'sean', 'seth', 'shane', 'shawn', 'silvestre', 'stanley', 'stefan', 'stefano',
            'stephen', 'stuart', 'sven', 'teddy', 'teo', 'theodore', 'thierry', 'timoteo', 'toby',
            'tony', 'travis', 'trevor', 'troy', 'valente', 'valentino', 'vincent', 'wade', 'walter',
            'wayne', 'wesley', 'wyatt', 'xavi', 'yannick', 'yosef', 'zacarias', 'zachary', 'zane', 'zion',
        ];

        if (mb_strlen($firstName) < 2 || in_array($firstName, $unisex, true)) {
            return 'Revisar';
        }
        if (in_array($firstName, $female, true)) {
            return 'Femenino';
        }
        if (in_array($firstName, $male, true)) {
            return 'Masculino';
        }

        if (str_ends_with($firstName, 'ander') || str_ends_with($firstName, 'rick')) {
            return 'Masculino';
        }
        if (preg_match('/(lyn|lynn|elle|ette)$/', $firstName)) {
            return 'Femenino';
        }

        return match (mb_substr($firstName, -1)) {
            'a' => 'Femenino',
            'o' => 'Masculino',
            default => 'Revisar',
        };
    }

    private function schoolNameFontSize(string $schoolName): int
    {
        return max(12, min(22, (int) floor(22 - max(0, mb_strlen($schoolName) - 24) / 5)));
    }

    private function countLabel(int $count, string $singular, string $plural): string
    {
        return $count . ' ' . ($count === 1 ? $singular : $plural);
    }

    private function uniqueSheetTitle(string $title, array &$usedTitles): string
    {
        $base = trim(preg_replace('/[:\\\\\/?*\[\]]/u', '-', $title) ?? 'Clase');
        $base = mb_substr($base !== '' ? $base : 'Clase', 0, 31);
        $candidate = $base;
        $suffix = 2;

        while (in_array(mb_strtolower($candidate), $usedTitles, true)) {
            $ending = '-' . $suffix++;
            $candidate = mb_substr($base, 0, 31 - mb_strlen($ending)) . $ending;
        }

        $usedTitles[] = mb_strtolower($candidate);
        return $candidate;
    }
}