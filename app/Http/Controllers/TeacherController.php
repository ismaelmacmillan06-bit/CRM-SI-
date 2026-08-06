<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Teacher;
use App\Models\School;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TeacherController extends Controller
{
    public function index(School $school)
    {
        $teachers = $school->teachers()->with('roles')->get();
        return view('teachers.index', compact('school', 'teachers'));
    }

    public function create(School $school)
    {
        return view('teachers.create', compact('school'));
    }

    public function store(Request $request, School $school)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email'     => 'nullable|email',
            'grade'     => 'nullable|string|max:100',
            'roles'     => 'nullable|array',
            'roles.*'   => 'string',
            'subject'   => 'nullable|string|in:ECA,ELT,ambos,ninguno',
        ]);

        $teacher = $school->teachers()->create(
            $request->only(['name', 'last_name', 'email', 'grade', 'subject', 'mee_username', 'mee_password'])
        );

        foreach ($request->roles ?? ['docente'] as $role) {
            if (array_key_exists($role, Teacher::ROLES)) {
                $teacher->roles()->firstOrCreate(['role' => $role]);
            }
        }

        ActivityLog::log('docente', "Docente \"{$request->name} {$request->last_name}\" registrado en {$school->name}", $school->id, '👨‍🏫');

        return redirect()->route('schools.teachers.index', $school)
                         ->with('success', 'Docente registrado correctamente.');
    }

    public function edit(Teacher $teacher)
    {
        $teacher->load('roles');
        return view('teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email'     => 'nullable|email',
            'grade'     => 'nullable|string|max:100',
            'roles'     => 'nullable|array',
            'roles.*'   => 'string',
            'subject'   => 'nullable|string|in:ECA,ELT,ambos,ninguno',
        ]);

        $teacher->update(
            $request->only(['name', 'last_name', 'email', 'grade', 'subject', 'mee_username', 'mee_password'])
        );

        // Sync roles
        $teacher->roles()->delete();
        foreach ($request->roles ?? ['docente'] as $role) {
            if (array_key_exists($role, Teacher::ROLES)) {
                $teacher->roles()->firstOrCreate(['role' => $role]);
            }
        }

        return redirect()->route('schools.teachers.index', $teacher->school)
                         ->with('success', 'Docente actualizado correctamente.');
    }

    public function destroy(Teacher $teacher)
    {
        $school = $teacher->school;
        $nombre = "{$teacher->name} {$teacher->last_name}";
        $teacher->delete();
        ActivityLog::log('docente', "Docente \"{$nombre}\" eliminado de {$school->name}", $school->id, '🗑️');
        return redirect()->route('schools.teachers.index', $school)
                         ->with('success', 'Docente eliminado correctamente.');
    }

    // ── Importación masiva ──────────────────────────────────────────────

    public function importarMasivo(Request $request, School $school)
    {
        $request->validate(['archivo' => 'required|file|mimes:xlsx,xls,csv|max:20480']);

        $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
        $sheet       = $spreadsheet->getActiveSheet();

        $importados = 0;
        $errores    = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $ri = $row->getRowIndex();
            $nombre    = trim((string) $sheet->getCell('A' . $ri)->getValue());
            $apellidos = trim((string) $sheet->getCell('B' . $ri)->getValue());
            $email     = trim((string) $sheet->getCell('C' . $ri)->getValue());
            $grado     = trim((string) $sheet->getCell('D' . $ri)->getValue());
            $rolesRaw  = trim((string) $sheet->getCell('E' . $ri)->getValue());
            $materiaRaw = trim((string) $sheet->getCell('F' . $ri)->getValue());
            $password  = trim((string) $sheet->getCell('G' . $ri)->getValue());

            if ($nombre === '' && $apellidos === '') continue;
            if ($nombre === '') { $errores[] = "Fila {$ri}: Nombre vacío."; continue; }

            $subject = $this->parseSubject($materiaRaw);
            $roles   = $this->parseRoles($rolesRaw);

            $teacher = $school->teachers()->create([
                'name'         => $nombre,
                'last_name'    => $apellidos,
                'email'        => $email ?: null,
                'grade'        => $grado ?: null,
                'subject'      => $subject,
                'mee_username' => $email ?: null,
                'mee_password' => $password ?: null,
            ]);

            foreach ($roles as $role) {
                $teacher->roles()->firstOrCreate(['role' => $role]);
            }

            $importados++;
        }

        $msg = "{$importados} docente(s) importados correctamente.";
        if (!empty($errores)) {
            $msg .= ' Errores: ' . implode('; ', $errores);
        }

        if ($importados > 0) {
            ActivityLog::log('docente', "Importación masiva: $importados docente(s) registrados", $school->id, '👨‍🏫');
        }

        return redirect()->route('schools.teachers.index', $school)->with('success', $msg);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Docentes');

        $headers = ['Nombre', 'Apellidos', 'Email', 'Grado', 'Rol', 'Materia', 'Contraseña MEE'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
        }

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Example rows
        $examples = [
            ['María', 'González López', 'maria@colegio.com', '1°', 'Docente', 'ELT', 'pass123'],
            ['Carlos', 'Ramírez Torres', 'carlos@colegio.com', '2°', 'Coordinador Inglés,Docente', 'ELT', 'pass456'],
            ['Ana', 'Flores Ruiz', 'ana@colegio.com', '', 'Director General', 'ninguno', ''],
        ];
        foreach ($examples as $ri => $row) {
            foreach ($row as $ci => $val) {
                $col = chr(65 + $ci);
                $sheet->setCellValue("{$col}" . ($ri + 2), $val);
            }
        }

        // Roles note
        $roles = implode(', ', array_values(Teacher::ROLES));
        $sheet->setCellValue('A6', 'Roles disponibles: ' . $roles);
        $sheet->mergeCells('A6:G6');
        $sheet->getStyle('A6')->getFont()->setItalic(true)->getColor()->setRGB('888888');

        // Subjects note
        $sheet->setCellValue('A7', 'Materias disponibles: ECA (Español), ELT (Inglés), ambos, ninguno');
        $sheet->mergeCells('A7:G7');
        $sheet->getStyle('A7')->getFont()->setItalic(true)->getColor()->setRGB('888888');

        // Column widths
        foreach (['A' => 20, 'B' => 22, 'C' => 28, 'D' => 12, 'E' => 35, 'F' => 14, 'G' => 18] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, 'plantilla-docentes.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Helpers privados ────────────────────────────────────────────────

    private function parseRoles(string $raw): array
    {
        if ($raw === '') return ['docente'];

        $aliases = [
            'docente'              => 'docente',
            'director general'     => 'director_general',
            'director de nivel'    => 'director_nivel',
            'director nivel'       => 'director_nivel',
            'coordinador'          => 'coordinador',
            'dueño'                => 'dueno',
            'dueno'                => 'dueno',
            'administrador mee'    => 'admin_mee',
            'admin mee'            => 'admin_mee',
            'coordinador inglés'   => 'coord_ingles',
            'coordinador ingles'   => 'coord_ingles',
            'coordinador español'  => 'coord_espanol',
            'coordinador espanol'  => 'coord_espanol',
        ];

        $roles = [];
        foreach (explode(',', $raw) as $part) {
            $key = mb_strtolower(trim($part));
            $key = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $key);
            // Try normalized alias first, then direct DB key
            $resolved = $aliases[$key] ?? (array_key_exists($key, Teacher::ROLES) ? $key : null);
            if ($resolved) $roles[] = $resolved;
        }

        return $roles ?: ['docente'];
    }

    private function parseSubject(string $raw): string
    {
        $aliases = [
            'eca' => 'ECA', 'español' => 'ECA', 'espanol' => 'ECA',
            'elt' => 'ELT', 'inglés' => 'ELT', 'ingles' => 'ELT',
            'ambos' => 'ambos', 'both' => 'ambos',
        ];
        $key = mb_strtolower(trim($raw));
        $key = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $key);
        return $aliases[$key] ?? 'ninguno';
    }
}
