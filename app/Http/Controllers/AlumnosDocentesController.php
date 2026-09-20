<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;

class AlumnosDocentesController extends Controller
{
    const NIVELES = ['Maternal', 'Preescolar', 'Primaria', 'Secundaria', 'Preparatoria', 'Licenciatura'];

    public function index()
    {
        $schools = School::withCount('students')->orderBy('name')->get();

        $porColegio = Student::select('school_id', 'level')->get()->groupBy('school_id');

        $catalogNorm = collect(self::NIVELES)->mapWithKeys(fn($n) => [$this->normalizar($n) => $n]);

        $filas = $schools->map(function ($school) use ($porColegio, $catalogNorm) {
            $conteos = array_fill_keys(self::NIVELES, 0);
            $otros   = 0;

            foreach ($porColegio->get($school->id, collect()) as $student) {
                $norm = $this->normalizar($student->level);
                if (isset($catalogNorm[$norm])) {
                    $conteos[$catalogNorm[$norm]]++;
                } else {
                    $otros++;
                }
            }

            return [
                'school'  => $school,
                'niveles' => $conteos,
                'otros'   => $otros,
                'total'   => $school->students_count,
            ];
        });

        return view('alumnos-docentes.index', [
            'filas'   => $filas,
            'niveles' => self::NIVELES,
        ]);
    }

    public function buscar(Request $request)
    {
        $q = trim((string) $request->query('usuario'));
        if ($q === '') {
            return response()->json([]);
        }

        $alumnos = Student::with('school')
            ->where('mee_username', 'like', "%{$q}%")
            ->limit(20)->get()
            ->map(fn($s) => [
                'tipo'        => 'Alumno',
                'nombre'      => trim($s->name . ' ' . $s->last_name),
                'usuario'     => $s->mee_username,
                'school_id'   => $s->school_id,
                'school_name' => $s->school?->name ?? '—',
            ]);

        $docentes = Teacher::with('school')
            ->where('mee_username', 'like', "%{$q}%")
            ->limit(20)->get()
            ->map(fn($t) => [
                'tipo'        => 'Docente',
                'nombre'      => trim($t->name . ' ' . $t->last_name),
                'usuario'     => $t->mee_username,
                'school_id'   => $t->school_id,
                'school_name' => $t->school?->name ?? '—',
            ]);

        $resultados = $alumnos->concat($docentes)->sortBy('usuario')->values();

        return response()->json($resultados);
    }

    /**
     * Normaliza un valor de nivel para compararlo sin acentos ni mayúsculas
     * (ej. "primaria" o "PRIMARIA " -> "primaria"), tolerando variaciones
     * de captura en cargas masivas antiguas.
     */
    private function normalizar($valor): string
    {
        $s = (string) $valor;
        $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
        $s = preg_replace('/\p{Mn}/u', '', $s);
        $s = mb_strtolower(trim($s));
        return preg_replace('/[^a-z0-9]/', '', $s);
    }
}
