<?php

namespace App\Http\Controllers;

use App\Helpers\Niveles;
use App\Models\ActivityLog;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoAccion;
use App\Models\SeguimientoDefinicionAccion;
use App\Models\SeguimientoMeta;
use App\Models\SeguimientoRecurso;
use App\Models\Ticket;
use App\Models\Visit;
use App\Services\SectionNovedadesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SeguimientoController extends Controller
{
    // slug => datos de la plataforma. El slug es el de la URL (/seguimiento/{slug}) y el que se
    // guarda en school_service_types.plataforma. Sin 'acciones' la página queda "en construcción".
    public const PLATAFORMAS = [
        // Meta: agenda de exámenes (tabla seguimiento_meta) + sus propias acciones de arranque.
        // Se gestiona por colegio en seguimiento/meta-colegio (datos y acciones en un solo formulario).
        'meta'          => ['nombre' => 'Meta',          'icono' => '🎯', 'agenda' => true,
            'acciones' => [
                'compartir-edgar'    => 'Compartir información a Edgar',
                'capacitacion'       => 'Capacitación',
                'fechas-aplicacion'  => 'Fechas de aplicación',
                'info-administrador' => 'Información del administrador',
                'carga-alumnos'      => 'Carga de alumnos en META',
            ],
            // Responsable de Meta: botón "Contacto Responsable" con correo y WhatsApp
            'contacto' => [
                'nombre'   => 'Celeste May',
                'correo'   => 'celeste.may.contractor@macmillaneducation.com',
                'telefono' => '9994456141',
                'saludo'   => 'Celeste',
            ],
        ],
        'global-educa'  => ['nombre' => 'Global Educa',  'icono' => '🌐', 'acciones' => [
            'kickoff'                   => 'Kickoff (contacto con el colegio)',
            'entrega-informacion'       => 'Entrega de información por parte del colegio',
            'configuracion'             => 'Configuración de Global Educa (Arranque)',
            'pruebas'                   => 'Pruebas de configuración',
            'presentacion-capacitacion' => 'Presentación a colegio y capacitación',
            'liberacion'                => 'Liberación',
            'seguimiento'               => 'Seguimiento',
        ]],
        'frances'       => ['nombre' => 'Francés',       'icono' => '🗼', 'por_nivel' => true, 'acciones' => [
            'alta-docentes'     => 'Alta de docentes en Espace Virtuel',
            'alta-alumnos'      => 'Alta de alumnos en Espace Virtuel',
            'creacion-clases'   => 'Creación de clases en Espace Virtuel',
            'asignar-docentes'  => 'Asignar docentes a clases en Espace Virtuel',
            'asignar-alumnos'   => 'Asignar alumnos a clases en Espace Virtuel',
            'correo-activacion' => 'Envío de correo para activación de materiales',
            'entregar-colegio'  => 'Entregar colegio de Francés (entregar cuentas)',
        ]],
        'red-roo-reads' => ['nombre' => 'Red Roo Reads', 'icono' => '📖', 'acciones' => []],
        'equipamiento'  => ['nombre' => 'Equipamiento',  'icono' => '🖥️', 'acciones' => []],
    ];

    // Quién puede capturar en Seguimiento; el resto de los roles con acceso (ECA, ELT, ventas,
    // coordinador) solo consulta. El middleware VerificarAccesoRol también bloquea sus POST.
    public const ROLES_EDICION = ['admin'];

    public static function puedeEditar(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(self::ROLES_EDICION);
    }

    private function catalogoAcciones(string $plataforma)
    {
        return SeguimientoDefinicionAccion::where('plataforma', $plataforma)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    private function infoPlataforma(string $plataforma): array
    {
        abort_unless(array_key_exists($plataforma, self::PLATAFORMAS), 404);
        $info = self::PLATAFORMAS[$plataforma];
        $catalogo = $this->catalogoAcciones($plataforma);

        if (array_key_exists('acciones', $info)) {
            $info['acciones'] = $catalogo->pluck('etiqueta', 'clave')->all();
        }

        return $info;
    }

    public function show(string $plataforma)
    {
        $info = $this->infoPlataforma($plataforma);
        if ($user = auth()->user()) {
            app(SectionNovedadesService::class)->consumeSeguimiento($user, $plataforma);
        }
        $datos = [
            'slug'             => $plataforma,
            'nombre'           => $info['nombre'],
            'icono'            => $info['icono'],
            'puedeEditar'      => self::puedeEditar(),
            'catalogoAcciones' => $this->catalogoAcciones($plataforma),
        ];

        if (!empty($info['agenda'])) {
            return $this->agendaMeta($datos);
        }

        if (!array_key_exists('acciones', $info)) {
            return view('seguimiento.show', $datos);
        }

        $tieneAcciones = !empty($info['acciones']);
        $filas = $this->filasPlataforma($plataforma);
        $schoolIds = $filas->pluck('colegio.id');

        $resumenFormato = [];
        if (in_array($plataforma, ['global-educa', 'frances'], true)) {
            $formatoController = app(FormatoEntregaController::class);
            foreach ($filas as $fila) {
                $colegio = $fila['colegio'];
                $resumenFormato[$colegio->id] = $formatoController->summaryForSchool($colegio, $plataforma);
            }
        }

        $categoriasRecursos = SeguimientoRecursoController::CATEGORIAS_POR_PLATAFORMA[$plataforma] ?? [];
        $cargarRecursos = fn (string $categoria) => in_array($categoria, $categoriasRecursos, true)
            ? SeguimientoRecurso::where('plataforma', $plataforma)
                ->where('categoria', $categoria)
                ->visiblePara(auth()->user())
                ->with(['user', 'audiencias'])
                ->latest()
                ->get()
            : collect();
        $formatos = $cargarRecursos('formatos');
        $manuales = $cargarRecursos('manuales');

        $ticketsLista = $this->ticketsDelProducto($info['nombre'], $schoolIds)
            ->with(['school', 'consultant.user'])
            ->latest()
            ->get();
        $sesionesLista = $this->sesionesDelProducto($info['nombre'], $schoolIds)
            ->with(['school', 'consultant.user'])
            ->orderByDesc('scheduled_date')
            ->orderByDesc('visit_date')
            ->get();

        return view('seguimiento.dashboard', $datos + [
            'filas'              => $filas,
            'tieneAcciones'      => $tieneAcciones,
            'totalAcciones'      => count($info['acciones']),
            'completos'          => $tieneAcciones ? $filas->where('completo', true)->count() : 0,
            'servicioLigado'     => SchoolServiceType::where('plataforma', $plataforma)->exists(),
            'resumenFormato'     => $resumenFormato,
            'categoriasRecursos' => $categoriasRecursos,
            'formatos'           => $formatos,
            'manuales'           => $manuales,
            'ticketsLista'       => $ticketsLista,
            'sesionesLista'      => $sesionesLista,
            'tickets'            => [
                'total'    => $ticketsLista->count(),
                'abiertos' => $ticketsLista->where('status', '!=', 'closed')->count(),
                'cerrados' => $ticketsLista->where('status', 'closed')->count(),
            ],
            'sesiones'           => [
                'total'      => $sesionesLista->count(),
                'realizadas' => $sesionesLista->where('status', 'terminada')->count(),
                'pendientes' => $sesionesLista->where('status', '!=', 'terminada')->count(),
            ],
        ]);
    }

    // Agenda de exámenes Meta: datos de acceso, capacitación y fecha de aplicación por colegio
    private function agendaMeta(array $datos)
    {
        $info = $this->infoPlataforma($datos['slug']);
        $filas = $this->filasPlataforma($datos['slug']);
        $manuales = SeguimientoRecurso::where('plataforma', $datos['slug'])
            ->where('categoria', 'manuales')
            ->visiblePara(auth()->user())
            ->with(['user', 'audiencias'])
            ->latest()
            ->get();

        return view('seguimiento.meta', $datos + [
            'filas'          => $filas,
            'tieneAcciones'  => count($info['acciones']) > 0,
            'totalAcciones'  => count($info['acciones']),
            'servicioLigado' => SchoolServiceType::where('plataforma', $datos['slug'])->exists(),
            'contacto'       => $info['contacto'] ?? null,
            'manuales'       => $manuales,
        ] + $this->actividad($datos['nombre'], $filas->pluck('colegio.id')));
    }

    // Cards de Tickets y Sesiones: los que tienen marcado el producto de la plataforma
    private function actividad(string $producto, $schoolIds): array
    {
        $tickets = $this->ticketsDelProducto($producto, $schoolIds)->get(['id', 'status']);
        $sesiones = $this->sesionesDelProducto($producto, $schoolIds)->get(['id', 'status']);

        return [
            'tickets'  => [
                'total'    => $tickets->count(),
                'abiertos' => $tickets->where('status', '!=', 'closed')->count(),
                'cerrados' => $tickets->where('status', 'closed')->count(),
            ],
            'sesiones' => [
                'total'      => $sesiones->count(),
                'realizadas' => $sesiones->where('status', 'terminada')->count(),
                'pendientes' => $sesiones->where('status', '!=', 'terminada')->count(),
            ],
        ];
    }

    // Descarga en Excel: colegios, acciones de arranque, tickets y sesiones del producto
    public function excel(string $plataforma)
    {
        $info = $this->infoPlataforma($plataforma);
        abort_if(!array_key_exists('acciones', $info), 404);

        $filas = $this->filasPlataforma($plataforma);
        $ids = $filas->pluck('colegio.id');

        $tickets = $this->ticketsDelProducto($info['nombre'], $ids)
            ->with(['school', 'consultant.user'])->latest()->get();
        $sesiones = $this->sesionesDelProducto($info['nombre'], $ids)
            ->with(['school', 'consultant.user'])->orderByDesc('scheduled_date')->get();

        $archivo = (new \App\Exports\SeguimientoExcel($plataforma, $info, $filas, $tickets, $sesiones))->guardar();
        $nombre = 'Seguimiento_' . str_replace(' ', '_', $info['nombre']) . '_' . now()->format('Y-m-d') . '.xlsx';

        return response()->download($archivo, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // Tickets y sesiones marcados con el producto (Tickets/Sesiones → Producto) de esos colegios.
    private function ticketsDelProducto(string $producto, $schoolIds)
    {
        return Ticket::whereIn('school_id', $schoolIds)
            ->whereNotNull('productos')
            ->whereJsonContains('productos', $producto);
    }

    private function sesionesDelProducto(string $producto, $schoolIds)
    {
        return Visit::whereIn('school_id', $schoolIds)
            ->whereNotNull('productos')
            ->whereJsonContains('productos', $producto);
    }

    // Un renglón por colegio con su avance de arranque (y, en plataformas con agenda, sus datos de Meta)
    private function filasPlataforma(string $slug)
    {
        $info = $this->infoPlataforma($slug);
        $agenda = !empty($info['agenda']);
        $porNivel = !empty($info['por_nivel']);
        $acciones = $info['acciones'];
        $totalAcciones = count($acciones);

        $colegios = $this->colegiosDe($slug)
            ->with(array_merge(
                ['levels', 'services', 'schoolConsultants' => fn ($q) => $q->where('role', 'digital')->with('consultant.user')],
                $agenda ? ['seguimientoMeta'] : []
            ))
            ->orderBy('name')
            ->get();

        $avance = $totalAcciones > 0
            ? SeguimientoAccion::where('plataforma', $slug)
                ->whereIn('school_id', $colegios->pluck('id'))
                ->whereIn('accion', array_keys($acciones))
                ->get()
                ->groupBy('school_id')
            : collect();

        $hoy = today();
        $limite = today()->addDays(30);
        $proxima = fn ($fecha) => $fecha && $fecha->between($hoy, $limite);

        return $colegios->map(function (School $colegio) use ($slug, $agenda, $porNivel, $proxima, $avance, $acciones, $totalAcciones) {
            $registrosColegio = $avance[$colegio->id] ?? collect();
            $consultor = $colegio->schoolConsultants->first()?->consultant?->user?->name;

            if ($totalAcciones === 0) {
                $filaZero = [
                    'colegio'   => $colegio,
                    'hechas'    => 0,
                    'total'     => 0,
                    'pct'       => 0,
                    'completo'  => false,
                    'siguiente' => null,
                    'acciones'  => collect(),
                    'consultor' => $consultor,
                ];
                if ($agenda) {
                    $meta = $colegio->seguimientoMeta ?? new SeguimientoMeta();
                    $filaZero += [
                        'meta'                 => $meta,
                        'capacitacion_proxima' => $proxima($meta->fecha_capacitacion),
                        'aplicacion_proxima'   => $proxima($meta->fecha_aplicacion),
                        'pendiente'            => $meta->fechasPendientes(),
                    ];
                }
                return $filaZero;
            }

            if ($porNivel) {
                $niveles = $this->ordenarNiveles($colegio->nivelesDePlataforma($slug));
                $nivelIds = $niveles->pluck('id')->all();
                $porNivelMap = $registrosColegio
                    ->whereIn('level_id', $nivelIds)
                    ->groupBy('level_id');

                $resumenNiveles = $niveles->map(function ($nivel) use ($porNivelMap, $acciones, $totalAcciones) {
                    $accionesNivel = ($porNivelMap[$nivel->id] ?? collect())->keyBy('accion');
                    $hechasNivel = $accionesNivel->where('status', 'done')->count();

                    return [
                        'nivel'    => $nivel,
                        'hechas'   => $hechasNivel,
                        'total'    => $totalAcciones,
                        'pct'      => $totalAcciones > 0 ? round($hechasNivel / $totalAcciones * 100) : 0,
                        'completo' => $totalAcciones > 0 && $hechasNivel === $totalAcciones,
                        'acciones' => $accionesNivel,
                    ];
                });

                $totalColegio = $niveles->count() * $totalAcciones;
                $hechas = $resumenNiveles->sum('hechas');
                $pct = $totalColegio > 0 ? round($hechas / $totalColegio * 100) : 0;
                $completo = $totalColegio > 0 && $hechas === $totalColegio;

                $siguiente = null;
                if ($niveles->isEmpty()) {
                    $siguiente = $colegio->levels->isEmpty()
                        ? 'Sin niveles registrados'
                        : 'Sin niveles con ' . self::PLATAFORMAS[$slug]['nombre'];
                } elseif (!$completo) {
                    foreach ($resumenNiveles as $nr) {
                        $pendiente = collect($acciones)
                            ->first(fn ($etiqueta, $clave) => ($nr['acciones'][$clave]->status ?? 'pending') !== 'done');
                        if ($pendiente !== null) {
                            $siguiente = $nr['nivel']->name . ': ' . $pendiente;
                            break;
                        }
                    }
                }

                return [
                    'colegio'   => $colegio,
                    'hechas'    => $hechas,
                    'total'     => $totalColegio,
                    'pct'       => $pct,
                    'completo'  => $completo,
                    'siguiente' => $siguiente,
                    'acciones'  => collect(),
                    'niveles'   => $resumenNiveles,
                    'consultor' => $consultor,
                ];
            }

            $hechasPorClave = $registrosColegio->whereNull('level_id')->keyBy('accion');
            $hechas = $hechasPorClave->where('status', 'done')->count();

            $fila = [
                'colegio'              => $colegio,
                'hechas'               => $hechas,
                'total'                => $totalAcciones,
                'pct'                  => round($hechas / $totalAcciones * 100),
                'completo'             => $hechas === $totalAcciones,
                'siguiente'            => collect($acciones)
                    ->first(fn ($etiqueta, $clave) => ($hechasPorClave[$clave]->status ?? 'pending') !== 'done'),
                'acciones'             => $hechasPorClave,
                'consultor'            => $consultor,
            ];

            if ($agenda) {
                $meta = $colegio->seguimientoMeta ?? new SeguimientoMeta();
                $fila += [
                    'meta'                 => $meta,
                    'capacitacion_proxima' => $proxima($meta->fecha_capacitacion),
                    'aplicacion_proxima'   => $proxima($meta->fecha_aplicacion),
                    'pendiente'            => $meta->fechasPendientes(),
                ];
            }

            return $fila;
        });
    }

    private function ordenarNiveles($niveles)
    {
        $orden = array_flip(Niveles::nombres());

        return $niveles->sortBy(function ($nivel) use ($orden) {
            $canonico = Niveles::canonico($nivel->name);
            $pos = $canonico !== null && isset($orden[$canonico]) ? $orden[$canonico] : 999;

            return sprintf('%04d-%06d', $pos, $nivel->id);
        })->values();
    }

    private function guardarMeta(Request $request, School $school, array $info)
    {
        $datos = $request->validate([
            'administrador'      => 'nullable|string|max:255',
            'usuario'            => 'nullable|string|max:255',
            'contrasena'         => 'nullable|string|max:255',
            'capacitacion'       => 'nullable|string|max:255',
            'fecha_capacitacion' => 'nullable|date',
            'hora_capacitacion'  => 'nullable|date_format:H:i,H:i:s',
            'fecha_aplicacion'   => 'nullable|date',
            'notas'              => 'nullable|string|max:2000',
        ] + $this->reglasAcciones(false));
        unset($datos['acciones']);

        // Se guarda siempre como HH:MM:SS para que reabrir y guardar sin tocar no cuente como cambio
        if (!empty($datos['hora_capacitacion'])) {
            $datos['hora_capacitacion'] = substr($datos['hora_capacitacion'], 0, 5) . ':00';
        }

        $meta = SeguimientoMeta::firstOrNew(['school_id' => $school->id]);
        $meta->fill($datos);
        $meta->updated_by = auth()->id();

        $cambiados = collect(array_keys($meta->getDirty()))
            ->intersect(array_keys(SeguimientoMeta::CAMPOS))
            ->map(fn ($campo) => SeguimientoMeta::CAMPOS[$campo]);

        $cambiosAcciones = 0;

        DB::transaction(function () use ($request, $school, $info, $meta, $cambiados, &$cambiosAcciones) {
            if ($cambiados->isNotEmpty()) {
                $meta->save();
                ActivityLog::log('seguimiento', 'Meta: actualizó ' . $cambiados->implode(', '), $school->id, '🎯');
            }
            $cambiosAcciones = $this->guardarAcciones($request, 'meta', $school, $info);
        });

        $mensaje = $cambiados->isEmpty() && $cambiosAcciones === 0
            ? 'Cambios guardados.'
            : 'Meta actualizado para ' . $school->name . '.';

        return back()->with('success', $mensaje);
    }

    private function reglasAcciones(bool $obligatorias): array
    {
        return [
            'acciones'          => ($obligatorias ? 'required' : 'nullable') . '|array',
            'acciones.*.status' => ['required', Rule::in(array_keys(SeguimientoAccion::STATUS))],
            'acciones.*.notes'  => 'nullable|string|max:2000',
        ];
    }

    public function colegio(string $plataforma, School $school)
    {
        $info = $this->plataformaDelColegio($plataforma, $school);
        $school->load('levels', 'services');

        if (!empty($info['agenda'])) {
            $guardadas = SeguimientoAccion::with('completedBy')
                ->where('school_id', $school->id)
                ->where('plataforma', $plataforma)
                ->whereNull('level_id')
                ->get()
                ->keyBy('accion');

            return view('seguimiento.meta-colegio', [
                'slug'             => $plataforma,
                'nombre'           => $info['nombre'],
                'icono'            => $info['icono'],
                'school'           => $school,
                'meta'             => $school->seguimientoMeta()->with('updatedBy')->first() ?? new SeguimientoMeta(),
                'acciones'         => $info['acciones'],
                'catalogoAcciones' => $this->catalogoAcciones($plataforma),
                'guardadas'        => $guardadas,
                'hechas'           => $guardadas->toBase()->only(array_keys($info['acciones']))->where('status', 'done')->count(),
                'contacto'         => $info['contacto'] ?? null,
                'puedeEditar'      => self::puedeEditar(),
            ]);
        }

        if (!empty($info['por_nivel'])) {
            $nivelesColegio = $this->ordenarNiveles($school->levels);
            $niveles = $this->ordenarNiveles($school->nivelesDePlataforma($plataforma));
            $accionKeys = array_keys($info['acciones']);
            $guardadasPorNivel = SeguimientoAccion::with('completedBy')
                ->where('school_id', $school->id)
                ->where('plataforma', $plataforma)
                ->whereIn('level_id', $niveles->pluck('id'))
                ->get()
                ->groupBy('level_id')
                ->map->keyBy('accion');

            $hechas = $niveles->sum(
                fn ($nivel) => ($guardadasPorNivel[$nivel->id] ?? collect())
                    ->toBase()
                    ->only($accionKeys)
                    ->where('status', 'done')
                    ->count()
            );

            return view('seguimiento.colegio', [
                'slug'              => $plataforma,
                'nombre'            => $info['nombre'],
                'icono'             => $info['icono'],
                'school'            => $school,
                'porNivel'          => true,
                'nivelesColegio'    => $nivelesColegio,
                'niveles'           => $niveles,
                'acciones'          => $info['acciones'],
                'catalogoAcciones'  => $this->catalogoAcciones($plataforma),
                'guardadasPorNivel' => $guardadasPorNivel,
                'guardadas'         => collect(),
                'hechas'            => $hechas,
                'totalAcciones'     => $niveles->count() * count($info['acciones']),
                'puedeEditar'       => self::puedeEditar(),
            ]);
        }

        $guardadas = SeguimientoAccion::with('completedBy')
            ->where('school_id', $school->id)
            ->where('plataforma', $plataforma)
            ->whereNull('level_id')
            ->get()
            ->keyBy('accion');

        return view('seguimiento.colegio', [
            'slug'             => $plataforma,
            'nombre'           => $info['nombre'],
            'icono'            => $info['icono'],
            'school'           => $school,
            'porNivel'         => false,
            'acciones'         => $info['acciones'],
            'catalogoAcciones' => $this->catalogoAcciones($plataforma),
            'guardadas'        => $guardadas,
            'hechas'           => $guardadas->toBase()->only(array_keys($info['acciones']))->where('status', 'done')->count(),
            'totalAcciones'    => count($info['acciones']),
            'puedeEditar'      => self::puedeEditar(),
        ]);
    }

    public function guardar(Request $request, string $plataforma, School $school)
    {
        abort_unless(self::puedeEditar(), 403, 'Seguimiento es solo de consulta para tu rol.');

        $info = $this->plataformaDelColegio($plataforma, $school);

        if (!empty($info['agenda'])) {
            return $this->guardarMeta($request, $school, $info);
        }

        if (!empty($info['por_nivel'])) {
            $request->validate([
                'acciones'            => 'required|array',
                'acciones.*'          => 'array',
                'acciones.*.*.status' => ['required', Rule::in(array_keys(SeguimientoAccion::STATUS))],
                'acciones.*.*.notes'  => 'nullable|string|max:2000',
            ]);

            $cambios = 0;
            DB::transaction(function () use ($request, $plataforma, $school, $info, &$cambios) {
                $cambios = $this->guardarAccionesPorNivel($request, $plataforma, $school, $info);
            });

            return back()->with('success', $cambios > 0 ? 'Seguimiento actualizado.' : 'Cambios guardados.');
        }

        $request->validate($this->reglasAcciones(true));

        $cambios = 0;
        DB::transaction(function () use ($request, $plataforma, $school, $info, &$cambios) {
            $cambios = $this->guardarAcciones($request, $plataforma, $school, $info);
        });

        return back()->with('success', $cambios > 0 ? 'Seguimiento actualizado.' : 'Cambios guardados.');
    }

    // Guarda el estatus/notas de cada acción enviada; regresa cuántas cambiaron de estatus
    private function guardarAcciones(Request $request, string $plataforma, School $school, array $info): int
    {
        $cambios = 0;

        foreach ($info['acciones'] as $clave => $etiqueta) {
            $enviado = $request->input("acciones.{$clave}");
            if (!is_array($enviado)) {
                continue;
            }

            $accion = SeguimientoAccion::firstOrNew([
                'school_id'  => $school->id,
                'plataforma' => $plataforma,
                'level_id'   => null,
                'accion'     => $clave,
            ]);

            if ($this->actualizarAccion($accion, $enviado, $info['nombre'], $etiqueta, $school->id)) {
                $cambios++;
            }
        }

        return $cambios;
    }

    public function guardarNiveles(Request $request, string $plataforma, School $school)
    {
        abort_unless(self::puedeEditar(), 403, 'Seguimiento es solo de consulta para tu rol.');

        $info = $this->plataformaDelColegio($plataforma, $school);
        abort_if(empty($info['por_nivel']), 404);

        $request->validate([
            'niveles'   => 'nullable|array',
            'niveles.*' => 'integer',
        ]);

        $school->load('levels', 'services');
        $validos = $school->levels->pluck('id')->map(fn ($id) => (int) $id)->all();
        $seleccionados = array_values(array_unique(array_intersect(
            array_map('intval', $request->input('niveles', [])),
            $validos
        )));

        $service = $school->services->firstWhere('plataforma', $plataforma);
        if ($service) {
            $school->services()->updateExistingPivot($service->id, [
                'niveles' => json_encode($seleccionados),
            ]);
        }

        $nombres = $this->ordenarNiveles($school->levels->whereIn('id', $seleccionados))
            ->pluck('name')
            ->implode(', ') ?: 'ninguno';
        ActivityLog::log('seguimiento', "{$info['nombre']}: actualizó niveles activos ({$nombres})", $school->id, '🧭');

        return back()->with('success', 'Niveles de ' . $info['nombre'] . ' actualizados.');
    }

    // Guarda acciones independientes por cada nivel activo para esta plataforma en el colegio
    private function guardarAccionesPorNivel(Request $request, string $plataforma, School $school, array $info): int
    {
        $cambios = 0;
        $school->load('levels', 'services');
        $niveles = $this->ordenarNiveles($school->nivelesDePlataforma($plataforma));

        foreach ($niveles as $nivel) {
            foreach ($info['acciones'] as $clave => $etiqueta) {
                $enviado = $request->input("acciones.{$nivel->id}.{$clave}");
                if (!is_array($enviado)) {
                    continue;
                }

                $accion = SeguimientoAccion::firstOrNew([
                    'school_id'  => $school->id,
                    'plataforma' => $plataforma,
                    'level_id'   => $nivel->id,
                    'accion'     => $clave,
                ]);

                if ($this->actualizarAccion($accion, $enviado, "{$info['nombre']} ({$nivel->name})", $etiqueta, $school->id)) {
                    $cambios++;
                }
            }
        }

        return $cambios;
    }

    private function actualizarAccion(SeguimientoAccion $accion, array $enviado, string $logPrefijo, string $etiqueta, int $schoolId): bool
    {
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
            return false; // nada que guardar: sigue pendiente y sin notas
        }

        $accion->save();

        if ($statusAnterior !== $accion->status) {
            ActivityLog::log(
                'seguimiento',
                "{$logPrefijo}: \"{$etiqueta}\" → " . SeguimientoAccion::STATUS[$accion->status],
                $schoolId,
                '🧭'
            );

            return true;
        }

        return false;
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
        $info = $this->infoPlataforma($plataforma);

        abort_if(empty($info['acciones']) && empty($info['agenda']), 404);
        abort_unless($school->status !== 'inactivo' && $school->tienePlataforma($plataforma), 404);

        return $info;
    }

    public function storeCatalogAccion(Request $request, string $plataforma)
    {
        abort_unless(self::puedeEditar(), 403);
        abort_unless(array_key_exists($plataforma, self::PLATAFORMAS), 404);

        $request->validate([
            'etiqueta' => 'required|string|max:200',
            'orden'    => 'nullable|integer|min:0|max:999',
        ]);

        $baseClave = Str::slug($request->etiqueta, '-') ?: 'accion';
        $clave = Str::limit($baseClave, 70, '');
        $suffix = 2;
        while (SeguimientoDefinicionAccion::where('plataforma', $plataforma)->where('clave', $clave)->exists()) {
            $clave = Str::limit($baseClave, 60, '') . '-' . $suffix++;
        }

        $orden = $request->filled('orden')
            ? (int) $request->orden
            : ((int) SeguimientoDefinicionAccion::where('plataforma', $plataforma)->max('orden') + 1);

        $accionDef = SeguimientoDefinicionAccion::create([
            'plataforma' => $plataforma,
            'clave'      => $clave,
            'etiqueta'   => trim($request->etiqueta),
            'orden'      => $orden,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'    => true,
                'item'  => [
                    'id'         => $accionDef->id,
                    'plataforma' => $plataforma,
                    'clave'      => $accionDef->clave,
                    'etiqueta'   => $accionDef->etiqueta,
                    'orden'      => (int) $accionDef->orden,
                ],
                'total' => SeguimientoDefinicionAccion::where('plataforma', $plataforma)->count(),
            ]);
        }

        return back()->with('success', 'Acción de arranque agregada a ' . self::PLATAFORMAS[$plataforma]['nombre'] . '.');
    }

    public function updateCatalogAccion(Request $request, string $plataforma, SeguimientoDefinicionAccion $accionDef)
    {
        abort_unless(self::puedeEditar(), 403);
        abort_unless($accionDef->plataforma === $plataforma, 404);

        $request->validate([
            'etiqueta' => 'required|string|max:200',
            'orden'    => 'nullable|integer|min:0|max:999',
        ]);

        $accionDef->update([
            'etiqueta' => trim($request->etiqueta),
            'orden'    => $request->filled('orden') ? (int) $request->orden : $accionDef->orden,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'   => true,
                'item' => [
                    'id'         => $accionDef->id,
                    'plataforma' => $plataforma,
                    'clave'      => $accionDef->clave,
                    'etiqueta'   => $accionDef->etiqueta,
                    'orden'      => (int) $accionDef->orden,
                ],
            ]);
        }

        return back()->with('success', 'Acción de arranque actualizada.');
    }

    public function reorderCatalogAcciones(Request $request, string $plataforma)
    {
        abort_unless(self::puedeEditar(), 403);
        abort_unless(array_key_exists($plataforma, self::PLATAFORMAS), 404);

        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));

        DB::transaction(function () use ($plataforma, $ids) {
            $validIds = SeguimientoDefinicionAccion::where('plataforma', $plataforma)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $pos = 1;
            foreach ($ids as $id) {
                if (in_array($id, $validIds, true)) {
                    SeguimientoDefinicionAccion::where('plataforma', $plataforma)
                        ->where('id', $id)
                        ->update(['orden' => $pos++]);
                }
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Orden de acciones actualizado.');
    }

    public function destroyCatalogAccion(string $plataforma, SeguimientoDefinicionAccion $accionDef)
    {
        abort_unless(self::puedeEditar(), 403);
        abort_unless($accionDef->plataforma === $plataforma, 404);

        $deletedId = $accionDef->id;
        DB::transaction(function () use ($plataforma, $accionDef) {
            SeguimientoAccion::where('plataforma', $plataforma)
                ->where('accion', $accionDef->clave)
                ->delete();
            $accionDef->delete();
        });

        if (request()->expectsJson()) {
            return response()->json([
                'ok'         => true,
                'deleted_id' => $deletedId,
                'total'      => SeguimientoDefinicionAccion::where('plataforma', $plataforma)->count(),
            ]);
        }

        return back()->with('success', 'Acción de arranque eliminada.');
    }
}
