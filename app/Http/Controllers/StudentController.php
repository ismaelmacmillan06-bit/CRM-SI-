<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\School;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentController extends Controller
{
    public function index(School $school)
    {
        $students = $school->students()->orderBy('level')->orderBy('grade')->get();
        // Solo los niveles registrados en el colegio para el dropdown de alta masiva
        $nivelesDelColegio = $school->schoolLevels()->with('level')->get()
            ->pluck('level.name')->filter()->sort()->values();
        return view('students.index', compact('school', 'students', 'nivelesDelColegio'));
    }

    public function create(School $school)
    {
        $nivelesDelColegio = $school->schoolLevels()->with('level')->get()
            ->pluck('level.name')->filter()->sort()->values();
        return view('students.create', compact('school', 'nivelesDelColegio'));
    }

    public function store(Request $request, School $school)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            // mee_username es único globalmente: un usuario MEE pertenece a un solo alumno
            'mee_username' => 'required|string|max:255|unique:students,mee_username',
            'mee_password' => 'required|string|max:255',
            'grade'        => 'nullable|string|max:100',
            'level'        => 'nullable|string|max:100',
        ], [
            'mee_username.unique' => 'Este usuario MEE ya está registrado en el sistema.',
        ]);

        $school->students()->create($request->only(['name', 'last_name', 'mee_username', 'mee_password', 'grade', 'level']));

        return redirect()->route('schools.students.index', $school)
                         ->with('success', 'Alumno registrado correctamente.');
    }

    public function uploadPdf(Request $request, School $school)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240',
            'grade'    => 'nullable|string|max:100',
            'level'    => 'nullable|string|max:100',
        ]);

        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($request->file('pdf_file')->getPathname());
            $text   = $pdf->getText();

            // Extraer datos del PDF
            $students = $this->extractStudentsFromPdf($text);

            if (empty($students)) {
                return back()->with('error', 'No se pudieron extraer alumnos del PDF. Verifica el formato.');
            }
            

            $grade = $request->grade;
            $level = $request->level;
            $count = 0;

            foreach ($students as $student) {
                // Evitar duplicados por username
                $exists = $school->students()->where('mee_username', $student['mee_username'])->exists();
                if (!$exists) {
                    $school->students()->create([
                        'name'         => $student['name'],
                        'last_name'    => $student['last_name'],
                        'mee_username' => $student['mee_username'],
                        'mee_password' => $student['mee_password'],
                        'grade'        => $grade,
                        'level'        => $level,
                    ]);
                    $count++;
                }
            }

            return redirect()->route('schools.students.index', $school)
                             ->with('success', "✅ Se registraron {$count} alumnos correctamente.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el PDF: ' . $e->getMessage());
        }
    }

private function extractStudentsFromPdf(string $text): array
{
    $students = [];

    $text = preg_replace('/\s+/', ' ', $text);

    // UN SOLO regex captura usuario + nombre + contraseña juntos.
    // Elimina por completo el problema de alineación entre dos arrays separados.
    //
    // Estructura del PDF de MEE (2 columnas, extracción fila por fila):
    //   Col. izq: [Nombre] Your username: [user] Go to site [URL]
    //   Col. der: [Nombre] Your password: [pass]
    //
    // El nombre se toma de la columna derecha (justo antes de "Your password:"),
    // donde nunca hay contaminación de la contraseña del alumno anterior.
    // El .*? (no-codicioso + DOTALL) salta la URL y cualquier texto intermedio.
    //
    // Cada palabra del nombre usa (?!\S) = fin de token completo, para no
    // hacer match parcial dentro de contraseñas (ej: "Tmdh" en "TmdhGQGXaW").
    //
    // Soporta inglés ("Your username:" / "Your password:") y
    // español ("Tu nombre de usuario:" / "Tu contraseña:").
    $titleWord = '[A-ZÁÉÍÓÚÜÑ][a-záéíóúüñ]+(?!\S)';
    $capsWord  = '[A-ZÁÉÍÓÚÜÑ]{2,}(?!\S)';
    $lowerWord = '[a-záéíóúüñ]{2,}(?!\S)';
    $firstWord = "(?:{$titleWord}|{$capsWord})";
    $nextWord  = "(?:{$titleWord}|{$capsWord}|{$lowerWord})";

    $pattern = '/(?:Your username:|Tu nombre de usuario:)\s*(\S+)'
             . '\s+(?:Go to site|Ir al sitio).*?'
             . '(' . $firstWord . '(?:\s+' . $nextWord . '){1,5})'
             . '\s+(?:Your password:|Tu contraseña:)\s*(\S+)/su';

    preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $username  = trim($match[1]);
        $fullName  = trim($match[2]);
        $password  = trim($match[3]);

        $nameParts = explode(' ', $fullName);
        $name      = array_shift($nameParts);
        $lastName  = implode(' ', $nameParts);

        $students[] = [
            'name'         => $name,
            'last_name'    => $lastName,
            'mee_username' => $username,
            'mee_password' => $password,
        ];
    }

    return $students;
}

    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            // Excluir al propio alumno para que pueda guardarse sin cambiar su username
            'mee_username' => "required|string|max:255|unique:students,mee_username,{$student->id}",
            'mee_password' => 'required|string|max:255',
            'grade'        => 'nullable|string|max:100',
            'level'        => 'nullable|string|max:100',
        ], [
            'mee_username.unique' => 'Este usuario MEE ya está registrado en otro alumno.',
        ]);

        $student->update($request->only(['name', 'last_name', 'mee_username', 'mee_password', 'grade', 'level']));

        return redirect()->route('schools.students.index', $student->school)
                         ->with('success', 'Alumno actualizado correctamente.');
    }

    public function destroy(Student $student)
    {
        $school = $student->school;
        $student->delete();
        return redirect()->route('schools.students.index', $school)
                         ->with('success', 'Alumno eliminado correctamente.');
    }

    public function importarExcel(Request $request, School $school)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
            'level'      => 'nullable|string|max:100',
            'grade'      => 'nullable|string|max:100',
        ], [
            'excel_file.mimes' => 'Solo se aceptan archivos Excel (.xlsx o .xls).',
            'excel_file.max'   => 'El archivo no puede superar 10 MB.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('excel_file')->getPathname());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

            // Saltar la fila de encabezado (fila 0 del array)
            array_shift($rows);

            $level  = $request->level;
            $grade  = $request->grade;
            $count  = 0;
            $omitidos = [];

            foreach ($rows as $i => $row) {
                $nombreCompleto = trim((string) ($row[0] ?? ''));
                $usuario        = trim((string) ($row[1] ?? ''));
                $contrasena     = trim((string) ($row[2] ?? ''));
                // Columna D opcional: clase/grado por fila
                $gradoFila      = isset($row[3]) ? trim((string) $row[3]) : null;

                if ($nombreCompleto === '' || $usuario === '' || $contrasena === '') {
                    continue; // Fila vacía o incompleta — saltar silenciosamente
                }

                // Dividir nombre completo: primera palabra = nombre, resto = apellidos
                $partes   = preg_split('/\s+/', $nombreCompleto, 2);
                $nombre   = $partes[0];
                $apellido = $partes[1] ?? '';

                // Evitar duplicados por username MEE
                if ($school->students()->where('mee_username', $usuario)->exists()) {
                    $omitidos[] = "Fila " . ($i + 2) . ": usuario «{$usuario}» ya existe — omitido.";
                    continue;
                }

                $school->students()->create([
                    'name'         => $nombre,
                    'last_name'    => $apellido,
                    'mee_username' => $usuario,
                    'mee_password' => $contrasena,
                    'level'        => $level,
                    'grade'        => $gradoFila ?: $grade,
                ]);
                $count++;
            }

            $msg = "✅ Se registraron {$count} alumno(s) correctamente.";
            if ($omitidos) {
                $msg .= ' ' . count($omitidos) . ' omitido(s) por duplicado.';
            }

            return redirect()->route('schools.students.index', $school)
                             ->with('success', $msg)
                             ->with('excel_omitidos', $omitidos);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el Excel: ' . $e->getMessage());
        }
    }

    public function destroyAll(School $school)
    {
        $school->students()->delete();
        return redirect()->route('schools.students.index', $school)
                         ->with('success', 'Todos los alumnos fueron eliminados.');
    }
}