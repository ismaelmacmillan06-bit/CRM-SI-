<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\School;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentController extends Controller
{
    public function index(Request $request, School $school)
    {
        $perPage = (int) $request->query('per_page', 50);
        if (!in_array($perPage, [50, 100, 200], true)) {
            $perPage = 50;
        }

        $students = $school->students()
            ->orderBy('level')->orderBy('grade')->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        // Niveles configurados en el colegio (para dropdown de alta masiva)
        $nivelesDelColegio = $school->schoolLevels()->with('level')->get()
            ->pluck('level.name')->filter()->sort()->values();

        // Conteo de alumnos por nivel (sobre el total del colegio, no solo la página actual)
        $porNivel = $school->students()
            ->whereNotNull('level')->where('level', '!=', '')
            ->selectRaw('level, count(*) as total')
            ->groupBy('level')
            ->pluck('total', 'level')
            ->sortKeys();

        return view('students.index', compact('school', 'students', 'nivelesDelColegio', 'porNivel', 'perPage'));
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

        ActivityLog::log('alumno', "Alumno \"{$request->name} {$request->last_name}\" registrado en {$school->name}", $school->id, '👨‍🎓');

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

        $grade = $request->grade;
        $level = $request->level;

        $faltantes = $this->nivelesFaltantes($school, [$level]);
        if ($faltantes) {
            return back()->with('error', $this->mensajeNivelesFaltantes($faltantes));
        }

        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($request->file('pdf_file')->getPathname());
            $text   = $pdf->getText();

            // Extraer datos del PDF
            $students = $this->extractStudentsFromPdf($text);

            if (empty($students)) {
                return back()->with('error', 'No se pudieron extraer alumnos del PDF. Verifica el formato.');
            }

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

            if ($count > 0) {
                ActivityLog::log('alumno', "Importación PDF: {$count} alumno(s) registrados en {$school->name}", $school->id, '📄');
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
        $nombre = "{$student->name} {$student->last_name}";
        $student->delete();
        ActivityLog::log('alumno', "Alumno \"{$nombre}\" eliminado de {$school->name}", $school->id, '🗑️');
        return redirect()->route('schools.students.index', $school)
                         ->with('success', 'Alumno eliminado correctamente.');
    }

    /**
     * Normaliza un encabezado de columna para compararlo sin acentos,
     * mayúsculas ni espacios/paréntesis (ej. "Nombre (s)" -> "nombres").
     */
    private function normalizarEncabezado($valor): string
    {
        $s = (string) $valor;
        $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
        $s = preg_replace('/\p{Mn}/u', '', $s);
        $s = mb_strtolower(trim($s));
        return preg_replace('/[^a-z0-9]/', '', $s);
    }

    /**
     * De una lista de niveles usados en una carga (uno por alumno, o repetido
     * si es el mismo para todos), devuelve los nombres canónicos (Maternal,
     * Preescolar, Primaria, Secundaria, Preparatoria, Licenciatura) que el
     * colegio NO tiene seleccionados. Ignora valores que no correspondan a
     * ningún nivel del catálogo estándar (no se pueden validar).
     */
    private function nivelesFaltantes(School $school, array $nivelesUsados): array
    {
        $catalogMap = [];
        foreach (\App\Models\Level::pluck('name') as $nombreCatalogo) {
            $catalogMap[$this->normalizarEncabezado($nombreCatalogo)] = $nombreCatalogo;
        }

        $configurados = $school->schoolLevels()->with('level')->get()
            ->pluck('level.name')->filter()
            ->map(fn($n) => $this->normalizarEncabezado($n))->all();

        $faltantes = [];
        foreach ($nivelesUsados as $nivel) {
            $nivel = trim((string) $nivel);
            if ($nivel === '') continue;
            $norm = $this->normalizarEncabezado($nivel);
            if (!isset($catalogMap[$norm])) continue; // no es un nivel estándar, no se valida
            if (!in_array($norm, $configurados, true)) {
                $faltantes[$norm] = $catalogMap[$norm];
            }
        }

        return array_values($faltantes);
    }

    private function mensajeNivelesFaltantes(array $faltantes): string
    {
        $lista = implode(', ', $faltantes);
        return count($faltantes) === 1
            ? "Tu colegio no tiene {$lista} seleccionado. Selecciónalo antes de cargar tus alumnos."
            : "Tu colegio no tiene estos niveles seleccionados: {$lista}. Selecciónalos antes de cargar tus alumnos.";
    }

    public function importarExcel(Request $request, School $school)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'level'      => 'nullable|string|max:100',
            'grade'      => 'nullable|string|max:100',
        ], [
            'excel_file.mimes' => 'Solo se aceptan archivos Excel (.xlsx, .xls) o CSV (.csv).',
            'excel_file.max'   => 'El archivo no puede superar 10 MB.',
        ]);

        try {
            $file = $request->file('excel_file');
            $ext  = strtolower($file->getClientOriginalExtension());

            if (in_array($ext, ['csv', 'txt'], true)) {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                $reader->setDelimiter(',');
                $spreadsheet = $reader->load($file->getPathname());
            } else {
                $spreadsheet = IOFactory::load($file->getPathname());
            }
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

            // Detectar columnas por nombre de encabezado (fila 0), tolerante a
            // acentos/mayúsculas. Soporta tanto el CSV que se usa para dar de
            // alta alumnos en Macmillan (Nombre(s), Apellidos, Usuario,
            // Password, ..., Nivel, Grado, Grupo) como la plantilla clásica
            // (Nombre Completo, Usuario, Contraseña, Clase).
            $NAME_KEYS     = ['nombre', 'nombres', 'nombrecompleto', 'name', 'fullname', 'alumno', 'estudiante'];
            $LASTNAME_KEYS = ['apellidos', 'apellido', 'lastname', 'surname'];
            $USER_KEYS     = ['usuario', 'username', 'user', 'login'];
            $PASS_KEYS     = ['password', 'contrasena', 'contrasenia', 'clave', 'pass'];
            $NIVEL_KEYS    = ['nivel', 'level'];
            $GRADO_KEYS    = ['grado', 'gardo', 'grade'];
            $GRUPO_KEYS    = ['grupo', 'group', 'seccion'];
            $CLASE_KEYS    = ['clase', 'class', 'aula', 'salon'];

            $cols   = [];
            $header = $rows[0] ?? [];
            foreach ($header as $ci => $cell) {
                $h = $this->normalizarEncabezado($cell);
                if ($h === '') continue;
                if (!isset($cols['name'])     && in_array($h, $NAME_KEYS, true))     $cols['name'] = $ci;
                if (!isset($cols['lastname']) && in_array($h, $LASTNAME_KEYS, true)) $cols['lastname'] = $ci;
                if (!isset($cols['user'])     && in_array($h, $USER_KEYS, true))     $cols['user'] = $ci;
                if (!isset($cols['pass'])     && in_array($h, $PASS_KEYS, true))     $cols['pass'] = $ci;
                if (!isset($cols['nivel'])    && in_array($h, $NIVEL_KEYS, true))    $cols['nivel'] = $ci;
                if (!isset($cols['grado'])    && in_array($h, $GRADO_KEYS, true))    $cols['grado'] = $ci;
                if (!isset($cols['grupo'])    && in_array($h, $GRUPO_KEYS, true))    $cols['grupo'] = $ci;
                if (!isset($cols['clase'])    && in_array($h, $CLASE_KEYS, true))    $cols['clase'] = $ci;
            }
            $usaEncabezados = isset($cols['name']) && isset($cols['user']);

            // Saltar la fila de encabezado
            array_shift($rows);

            $level  = $request->level;
            $grade  = $request->grade;

            // --- Primera pasada: parsear todas las filas sin tocar la base de datos ---
            $filas = [];
            foreach ($rows as $i => $row) {
                if ($usaEncabezados) {
                    $nombre    = trim((string) ($row[$cols['name']] ?? ''));
                    $apellido  = isset($cols['lastname']) ? trim((string) ($row[$cols['lastname']] ?? '')) : '';
                    $usuario   = trim((string) ($row[$cols['user']] ?? ''));
                    $contrasena = isset($cols['pass'])  ? trim((string) ($row[$cols['pass']] ?? ''))  : '';
                    $nivelFila = isset($cols['nivel'])  ? trim((string) ($row[$cols['nivel']] ?? '')) : '';
                    $gradoFila = isset($cols['grado'])  ? trim((string) ($row[$cols['grado']] ?? '')) : '';
                    $grupoFila = isset($cols['grupo'])  ? trim((string) ($row[$cols['grupo']] ?? '')) : '';
                    $claseFila = isset($cols['clase'])  ? trim((string) ($row[$cols['clase']] ?? '')) : '';

                    // Sin columna de Apellidos: asumir que "Nombre" trae el nombre completo
                    if (!isset($cols['lastname']) && $apellido === '' && str_contains($nombre, ' ')) {
                        $partes   = preg_split('/\s+/', $nombre, 2);
                        $nombre   = $partes[0];
                        $apellido = $partes[1] ?? '';
                    }

                    // Combinar Grado + Grupo (ej. "1" + "A" = "1°A"); si no hay,
                    // usar la columna Clase directa (formato clásico)
                    $gradoFinal = $claseFila;
                    if ($gradoFila !== '' || $grupoFila !== '') {
                        $gradoFinal = trim($gradoFila . ($grupoFila !== '' ? '°' . $grupoFila : ''));
                    }
                } else {
                    // Formato clásico posicional: Nombre Completo | Usuario | Contraseña | Clase
                    $nombreCompleto = trim((string) ($row[0] ?? ''));
                    $usuario        = trim((string) ($row[1] ?? ''));
                    $contrasena     = trim((string) ($row[2] ?? ''));
                    $claseFila      = isset($row[3]) ? trim((string) $row[3]) : '';

                    $partes   = preg_split('/\s+/', $nombreCompleto, 2);
                    $nombre   = $partes[0] ?? '';
                    $apellido = $partes[1] ?? '';
                    $nivelFila  = '';
                    $gradoFinal = $claseFila;
                }

                if ($nombre === '' || $usuario === '' || $contrasena === '') {
                    continue; // Fila vacía o incompleta — saltar silenciosamente
                }

                $filas[] = [
                    'fila'       => $i + 2,
                    'nombre'     => $nombre,
                    'apellido'   => $apellido,
                    'usuario'    => $usuario,
                    'contrasena' => $contrasena,
                    'nivel'      => $nivelFila !== '' ? $nivelFila : $level,
                    'grado'      => $gradoFinal !== '' ? $gradoFinal : $grade,
                ];
            }

            // --- Validar que el colegio tenga seleccionados los niveles que se van a cargar ---
            $faltantes = $this->nivelesFaltantes($school, array_column($filas, 'nivel'));
            if ($faltantes) {
                return back()->with('error', $this->mensajeNivelesFaltantes($faltantes));
            }

            // --- Segunda pasada: crear los alumnos ---
            $count    = 0;
            $omitidos = [];
            foreach ($filas as $f) {
                // Evitar duplicados por username MEE
                if ($school->students()->where('mee_username', $f['usuario'])->exists()) {
                    $omitidos[] = "Fila {$f['fila']}: usuario «{$f['usuario']}» ya existe — omitido.";
                    continue;
                }

                $school->students()->create([
                    'name'         => $f['nombre'],
                    'last_name'    => $f['apellido'],
                    'mee_username' => $f['usuario'],
                    'mee_password' => $f['contrasena'],
                    'level'        => $f['nivel'],
                    'grade'        => $f['grado'],
                ]);
                $count++;
            }

            $msg = "✅ Se registraron {$count} alumno(s) correctamente.";
            if ($omitidos) {
                $msg .= ' ' . count($omitidos) . ' omitido(s) por duplicado.';
            }

            if ($count > 0) {
                ActivityLog::log('alumno', "Importación Excel: {$count} alumno(s) registrados en {$school->name}", $school->id, '📊');
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
        ActivityLog::log('alumno', "Todos los alumnos de {$school->name} fueron eliminados", $school->id, '🗑️');
        return redirect()->route('schools.students.index', $school)
                         ->with('success', 'Todos los alumnos fueron eliminados.');
    }
}