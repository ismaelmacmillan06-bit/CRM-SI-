<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\School;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

class StudentController extends Controller
{
    public function index(School $school)
    {
        $students = $school->students()->orderBy('level')->orderBy('grade')->get();
        return view('students.index', compact('school', 'students'));
    }

    public function create(School $school)
    {
        return view('students.create', compact('school'));
    }

    public function store(Request $request, School $school)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'mee_username' => 'required|string|max:255',
            'mee_password' => 'required|string|max:255',
            'grade'        => 'nullable|string|max:100',
            'level'        => 'nullable|string|max:100',
        ]);

        $school->students()->create($request->all());

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

    // Limpiar texto
    $text = preg_replace('/\s+/', ' ', $text);

    // Extraer bloques de username con su nombre
    preg_match_all('/Your username:\s*(\S+)\s+Go to site[^Y]+Your password:\s*(\S+)/u', $text, $matches, PREG_SET_ORDER);

    // Extraer nombres por separado - están justo ANTES de "Your username:"
    preg_match_all('/([A-ZÁÉÍÓÚÜÑ]{2,}(?:\s+[A-ZÁÉÍÓÚÜÑ]{2,})+)\s+Your username:/u', $text, $nameMatches);
    $names = $nameMatches[1] ?? [];

    foreach ($matches as $index => $match) {
        $username = trim($match[1]);
        $password = trim($match[2]);
        $fullName = isset($names[$index]) ? trim($names[$index]) : 'Sin nombre';

        // Separar nombre y apellidos
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
            'mee_username' => 'required|string|max:255',
            'mee_password' => 'required|string|max:255',
            'grade'        => 'nullable|string|max:100',
            'level'        => 'nullable|string|max:100',
        ]);

        $student->update($request->all());

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

    public function destroyAll(School $school)
{
    $school->students()->delete();
    return redirect()->route('schools.students.index', $school)
                     ->with('success', 'Todos los alumnos fueron eliminados.');
}
}