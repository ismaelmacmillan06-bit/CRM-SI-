<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\Process;
use App\Models\School;
use App\Models\WeeklyReportEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReporteSemanalController extends Controller
{
    private const CATEGORIAS = [
        'red_roo_reads'     => ['label' => 'Red Roo Reads',      'icon' => '📚'],
        'equipamiento_lego' => ['label' => 'Equipamiento y LEGO', 'icon' => '🧩'],
        'meta'              => ['label' => 'Meta',                'icon' => '📈'],
        'exams_lab'         => ['label' => 'Exams Lab',           'icon' => '🧪'],
        'frances'           => ['label' => 'Francés',             'icon' => '🇫🇷'],
        'extras'            => ['label' => 'Extras',              'icon' => '✨'],
    ];

    public function index()
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->addDays(4);

        $consultores = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))
            ->with('user')
            ->get()
            ->sortBy(fn($c) => $c->user->name)
            ->values();

        $entradas = WeeklyReportEntry::where('week_start', $weekStart->toDateString())->get();

        $grid = [];
        foreach (self::CATEGORIAS as $slug => $meta) {
            foreach ($consultores as $consultor) {
                $grid[$slug][$consultor->id] = '';
            }
        }
        foreach ($entradas as $entrada) {
            $grid[$entrada->category][$entrada->consultant_id] = $entrada->content ?? '';
        }

        $ultimoGuardado = $entradas->max('updated_at');

        [$colegiosSinAlumno, $colegiosSinDocente] = $this->colegiosPendientes();

        $totalCampos = count(self::CATEGORIAS) * max($consultores->count(), 1);
        $novedadesCapturadas = $entradas->filter(fn($e) => trim((string) $e->content) !== '')->count();
        $avance = $totalCampos > 0 ? (int) round($novedadesCapturadas / $totalCampos * 100) : 0;

        return view('reporte-semanal.index', [
            'categorias'          => self::CATEGORIAS,
            'consultores'         => $consultores,
            'grid'                => $grid,
            'weekStart'           => $weekStart,
            'weekEnd'             => $weekEnd,
            'weekLabel'           => $this->formatearRangoSemana($weekStart, $weekEnd),
            'ultimoGuardado'      => $ultimoGuardado,
            'colegiosSinAlumno'   => $colegiosSinAlumno,
            'colegiosSinDocente'  => $colegiosSinDocente,
            'novedadesCapturadas' => $novedadesCapturadas,
            'totalCampos'         => $totalCampos,
            'avance'              => $avance,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'consultor_digital']), 403);

        $request->validate([
            'payload' => 'required|string',
        ]);

        $entradas = json_decode($request->input('payload'), true);
        if (!is_array($entradas)) {
            return back()->with('error', 'No se pudo leer la información enviada.');
        }

        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $categoriasValidas = array_keys(self::CATEGORIAS);
        $consultorIdsValidos = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))
            ->pluck('id')
            ->toArray();

        $guardados = 0;
        foreach ($entradas as $entrada) {
            $categoria = $entrada['category'] ?? null;
            $consultantId = $entrada['consultant_id'] ?? null;

            if (!in_array($categoria, $categoriasValidas, true) || !in_array((int) $consultantId, $consultorIdsValidos, true)) {
                continue;
            }

            WeeklyReportEntry::updateOrCreate(
                [
                    'week_start'    => $weekStart,
                    'category'      => $categoria,
                    'consultant_id' => $consultantId,
                ],
                [
                    'content'    => $entrada['content'] ?? null,
                    'updated_by' => auth()->id(),
                ]
            );
            $guardados++;
        }

        return back()->with('success', "Reporte guardado ({$guardados} celda(s) actualizadas).");
    }

    public function exportar()
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->addDays(4);

        $consultores = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))
            ->with('user')
            ->get()
            ->sortBy(fn($c) => $c->user->name)
            ->values();

        $entradas = WeeklyReportEntry::where('week_start', $weekStart->toDateString())->get()
            ->keyBy(fn($e) => $e->category . '_' . $e->consultant_id);

        [$colegiosSinAlumno, $colegiosSinDocente] = $this->colegiosPendientes();

        $filename = 'reporte_semanal_' . $weekStart->format('Y-m-d') . '.csv';

        $callback = function () use ($consultores, $entradas, $weekStart, $weekEnd, $colegiosSinAlumno, $colegiosSinDocente) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para acentos en Excel
            $csv = fn(array $row) => fputcsv($out, $row, ',', '"', '\\');

            $csv(['Reporte semanal — ' . $weekStart->format('d/m/Y') . ' al ' . $weekEnd->format('d/m/Y')]);
            $csv([]);
            $csv(['Colegios que aún no envían su formato de alumno', $colegiosSinAlumno->count()]);
            $csv(['Colegios que aún no envían su formato de docente', $colegiosSinDocente->count()]);
            $csv([]);

            $header = ['Seguimiento'];
            foreach ($consultores as $consultor) {
                $header[] = $consultor->user->name;
            }
            $csv($header);

            foreach (self::CATEGORIAS as $slug => $meta) {
                $fila = [$meta['label']];
                foreach ($consultores as $consultor) {
                    $entrada = $entradas->get($slug . '_' . $consultor->id);
                    $fila[] = $entrada->content ?? '';
                }
                $csv($fila);
            }

            $csv([]);
            $csv(['Listado de colegios que faltan de entregar formato docente']);
            foreach ($colegiosSinDocente as $colegio) {
                $csv([$colegio->name]);
            }

            $csv([]);
            $csv(['Listado de colegios que faltan de entregar formato alumno']);
            foreach ($colegiosSinAlumno as $colegio) {
                $csv([$colegio->name]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function formatearRangoSemana(Carbon $inicio, Carbon $fin): string
    {
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                  'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        if ($inicio->month === $fin->month) {
            return $inicio->day . ' al ' . $fin->day . ' de ' . $meses[$fin->month - 1] . ' de ' . $fin->year;
        }

        return $inicio->day . ' de ' . $meses[$inicio->month - 1]
             . ' al ' . $fin->day . ' de ' . $meses[$fin->month - 1] . ' de ' . $fin->year;
    }

    /**
     * Colegios que aún no tienen marcado como "done" el proceso de alta de
     * alumnos / registro de profesores en ninguno de sus niveles.
     */
    private function colegiosPendientes(): array
    {
        $procesoAlumnoId  = Process::where('slug', 'alta_alumnos')->value('id');
        $procesoDocenteId = Process::where('slug', 'registrar_profesores')->value('id');

        $colegiosSinAlumno = School::whereDoesntHave('schoolLevels.processes', function ($q) use ($procesoAlumnoId) {
                $q->where('process_id', $procesoAlumnoId)->where('status', 'done');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $colegiosSinDocente = School::whereDoesntHave('schoolLevels.processes', function ($q) use ($procesoDocenteId) {
                $q->where('process_id', $procesoDocenteId)->where('status', 'done');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return [$colegiosSinAlumno, $colegiosSinDocente];
    }
}
