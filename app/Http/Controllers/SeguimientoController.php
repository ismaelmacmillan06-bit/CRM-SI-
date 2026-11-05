<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoAccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SeguimientoController extends Controller
{
    // slug => datos de la plataforma. El slug es el de la URL (/seguimiento/{slug}) y el que se
    // guarda en school_service_types.plataforma. Sin 'acciones' la página queda "en construcción".
    public const PLATAFORMAS = [
        'meta'          => ['nombre' => 'Meta',          'icono' => '🎯'],
        'global-educa'  => ['nombre' => 'Global Educa',  'icono' => '🌐', 'acciones' => [
            'kickoff'                   => 'Kickoff (contacto con el colegio)',
            'entrega-informacion'       => 'Entrega de información por parte del colegio',
            'configuracion'             => 'Configuración de Global Educa (Arranque)',
            'pruebas'                   => 'Pruebas de configuración',
            'presentacion-capacitacion' => 'Presentación a colegio y capacitación',
            'liberacion'                => 'Liberación',
            'seguimiento'               => 'Seguimiento',
        ]],
        'frances'       => ['nombre' => 'Francés',       'icono' => '🗼'],
        'red-roo-reads' => ['nombre' => 'Red Roo Reads', 'icono' => '📖'],
    ];

    public function show(string $plataforma)
    {
        $info = self::PLATAFORMAS[$plataforma];
        $datos = ['slug' => $plataforma, 'nombre' => $info['nombre'], 'icono' => $info['icono']];

        if (empty($info['acciones'])) {
            return view('seguimiento.show', $datos);
        }

        $totalAcciones = count($info['acciones']);
        $servicioLigado = SchoolServiceType::where('plataforma', $plataforma)->exists();

        $colegios = $this->colegiosDe($plataforma)
            ->with(['levels', 'schoolConsultants' => fn ($q) => $q->where('role', 'digital')->with('consultant.user')])
            ->orderBy('name')
            ->get();

        $avance = SeguimientoAccion::where('plataforma', $plataforma)
            ->whereIn('school_id', $colegios->pluck('id'))
            ->whereIn('accion', array_keys($info['acciones']))
            ->get()
            ->groupBy('school_id');

        $filas = $colegios->map(function (School $colegio) use ($avance, $info, $totalAcciones) {
            $acciones = ($avance[$colegio->id] ?? collect())->keyBy('accion');
            $hechas = $acciones->where('status', 'done')->count();
            $siguiente = collect($info['acciones'])
                ->first(fn ($etiqueta, $clave) => ($acciones[$clave]->status ?? 'pending') !== 'done');

            return [
                'colegio'   => $colegio,
                'consultor' => $colegio->schoolConsultants->first()?->consultant?->user?->name,
                'hechas'    => $hechas,
                'pct'       => round($hechas / $totalAcciones * 100),
                'completo'  => $hechas === $totalAcciones,
                'siguiente' => $siguiente,
            ];
        });

        return view('seguimiento.dashboard', $datos + [
            'filas'          => $filas,
            'totalAcciones'  => $totalAcciones,
            'completos'      => $filas->where('completo', true)->count(),
            'servicioLigado' => $servicioLigado,
        ]);
    }

    public function colegio(string $plataforma, School $school)
    {
        $info = $this->plataformaDelColegio($plataforma, $school);

        $guardadas = SeguimientoAccion::with('completedBy')
            ->where('school_id', $school->id)
            ->where('plataforma', $plataforma)
            ->get()
            ->keyBy('accion');

        return view('seguimiento.colegio', [
            'slug'     => $plataforma,
            'nombre'   => $info['nombre'],
            'icono'    => $info['icono'],
            'school'   => $school,
            'acciones' => $info['acciones'],
            'guardadas' => $guardadas,
            'hechas'   => $guardadas->only(array_keys($info['acciones']))->where('status', 'done')->count(),
        ]);
    }

    public function guardar(Request $request, string $plataforma, School $school)
    {
        $info = $this->plataformaDelColegio($plataforma, $school);

        $request->validate([
            'acciones'            => 'required|array',
            'acciones.*.status'   => ['required', Rule::in(array_keys(SeguimientoAccion::STATUS))],
            'acciones.*.notes'    => 'nullable|string|max:2000',
        ]);

        $cambios = 0;

        DB::transaction(function () use ($request, $plataforma, $school, $info, &$cambios) {
            foreach ($info['acciones'] as $clave => $etiqueta) {
                $enviado = $request->input("acciones.{$clave}");
                if (!$enviado) {
                    continue;
                }

                $accion = SeguimientoAccion::firstOrNew([
                    'school_id'  => $school->id,
                    'plataforma' => $plataforma,
                    'accion'     => $clave,
                ]);

                $statusAnterior = $accion->status ?? 'pending';
                $accion->status = $enviado['status'];
                $accion->notes = $enviado['notes'] ?? null;

                if ($accion->status === 'done' && $statusAnterior !== 'done') {
                    $accion->completed_at = now();
                    $accion->completed_by = auth()->id();
                } elseif ($accion->status !== 'done') {
                    $accion->completed_at = null;
                    $accion->completed_by = null;
                }

                if (!$accion->exists && $accion->status === 'pending' && !$accion->notes) {
                    continue; // nada que guardar: sigue pendiente y sin notas
                }

                $accion->save();

                if ($statusAnterior !== $accion->status) {
                    $cambios++;
                    ActivityLog::log(
                        'seguimiento',
                        "{$info['nombre']}: \"{$etiqueta}\" → " . SeguimientoAccion::STATUS[$accion->status],
                        $school->id,
                        '🧭'
                    );
                }
            }
        });

        return back()->with('success', $cambios > 0 ? 'Seguimiento actualizado.' : 'Cambios guardados.');
    }

    // Colegios que llevan la plataforma: sin inactivos y, para consultor digital, solo los suyos
    private function colegiosDe(string $plataforma)
    {
        $query = School::noInactivos()
            ->whereHas('services', fn ($q) => $q->where('plataforma', $plataforma));

        if (auth()->user()->hasRole('consultor_digital')) {
            $consultant = Consultant::where('user_id', auth()->id())->first();
            $query->whereIn('id', SchoolConsultant::where('consultant_id', $consultant?->id)
                ->where('role', 'digital')
                ->pluck('school_id'));
        }

        return $query;
    }

    private function plataformaDelColegio(string $plataforma, School $school): array
    {
        $info = self::PLATAFORMAS[$plataforma];

        abort_if(empty($info['acciones']), 404);
        abort_unless($school->status !== 'inactivo' && $school->tienePlataforma($plataforma), 404);

        return $info;
    }
}
