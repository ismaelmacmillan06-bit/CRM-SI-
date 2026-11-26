<?php

namespace App\Http\Middleware;

use App\Helpers\AccesoColegios;
use App\Models\School;
use Closure;
use Illuminate\Http\Request;

class VerificarAccesoRol
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        // Admin: acceso total sin restricciones
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Perfil de usuario: cualquier usuario autenticado puede gestionar su propio perfil
        if ($request->routeIs('profile.*')) {
            return $next($request);
        }

        // Mis Notas SI: bloc de notas personal — acceso completo (lectura y
        // escritura) para todos los roles por igual; el propio controlador
        // ya limita a cada quien a ver/editar solo sus notas.
        if ($request->routeIs('notes.*')) {
            return $next($request);
        }

        // Centro de notificaciones: todos los roles pueden consultar, leer y descartar sus notificaciones
        if ($request->routeIs('notifications.*')) {
            return $next($request);
        }

        // Tablero SI: todos ven lo que va dirigido a su rol; quién publica o edita
        // se decide en ComunicadoController (Comunicado::puedePublicar)
        if ($request->routeIs('tablero.*')) {
            return $next($request);
        }

        // Herramientas SI: admin y consultor_digital; consul_ged las consulta (su rama solo deja GET)
        if ($request->routeIs('herramientas.*') && !$user->hasAnyRole(['consultor_digital', 'consul_ged'])) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
        }

        // Seguimiento SIC: admin y consultor_digital; consul_ged solo consulta
        if ($request->routeIs('seguimiento-sic.*') && !$user->hasAnyRole(['consultor_digital', 'consul_ged'])) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
        }

        // Producción: admin y consultor_digital cargan/borran; coordinador, ECA, ELT y ventas solo
        // consultan (sus ramas de abajo bloquean POST/DELETE y la vista oculta cargar/borrar)
        if ($request->routeIs('produccion.*') && !$user->hasAnyRole(['consultor_digital', 'coordinador', 'consultor_eca', 'consultor_elt', 'representante_ventas', 'consul_ged'])) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
        }

        // Seguimiento (Meta, Global Educa, Francés, Red Roo Reads, Equipamiento): admin y consultor_digital
        // capturan avances; personalizar el catálogo de acciones es solo del admin. ECA, ELT, ventas y
        // coordinador solo consultan (ver y descargar Excel, nada de guardar).
        if ($request->routeIs('seguimiento.*')) {
            if (!$user->hasAnyRole(['consultor_digital', 'consultor_eca', 'consultor_elt', 'representante_ventas', 'coordinador', 'consul_ged'])) {
                return redirect()->route('dashboard')
                    ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
            }
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                if ($request->routeIs('seguimiento.acciones.*')) {
                    abort(403, 'Solo un administrador puede personalizar las acciones de arranque.');
                }
                // consul_ged: libertad total solo en Global Educa (capturar y gestionar sus archivos)
                $esGlobalEducaDeGed = $user->hasRole('consul_ged')
                    && in_array($this->plataformaDeSeguimiento($request), \App\Http\Controllers\SeguimientoController::PLATAFORMAS_CONSUL_GED, true);
                if (!$user->hasRole('consultor_digital') && !$esGlobalEducaDeGed) {
                    return back()->with('error_acceso', 'Seguimiento es solo de consulta para tu rol.');
                }
            }
        }

        // Reporte Semanal: admin, consultor_digital y coordinador (este último solo
        // puede consultar/exportar, no guardar — ver rama de coordinador más abajo)
        if ($request->routeIs('reporte-semanal.*') && !$user->hasAnyRole(['consultor_digital', 'coordinador', 'consul_ged'])) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
        }

        // ECA y ELT: lectura en Colegios/Dashboard + acceso completo a SSA
        if ($user->hasAnyRole(['consultor_eca', 'consultor_elt'])) {
            // SSA: escritura permitida
            if ($request->routeIs('ssa.*')) {
                return $next($request);
            }
            // Tareas SI: marcan el estado de las tareas dirigidas a su área en sus
            // colegios (TareaController valida tarea y colegio)
            if ($request->routeIs('tareas.update-status')) {
                return $next($request);
            }
            // Descarga de alumnos por clase (POST para soportar 20+ clases)
            if ($request->routeIs('schools.students.export-by-class')) {
                return $next($request);
            }
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()->with('error_acceso', 'No tienes permisos para realizar esta acción.');
            }
            $rutasPermitidas = ['dashboard', 'dashboard.acciones-arranque.excel', 'reportes.general', 'reportes.zonas',
                'schools.*', 'tickets.show', 'tareas.index', 'alumnos-docentes.*', 'avance-colegios.*', 'produccion.index',
                'seguimiento.show', 'seguimiento.colegio', 'seguimiento.excel', 'seguimiento.recursos.preview', 'seguimiento.recursos.download'];
            if (!$request->routeIs($rutasPermitidas)) {
                return redirect()->route('ssa.index')
                    ->with('error_acceso', 'No tienes acceso a esta sección.');
            }
            // No pueden generar el Report Master
            if ($request->routeIs('schools.reporte-master')) {
                abort(403, 'No tienes permisos para generar este reporte.');
            }
            return $next($request);
        }

        // Coordinador: lectura amplia (Dashboard incl. Excel de arranque, Avance
        // Colegios, Colegios y todo dentro de un colegio, Alumnos Docentes, Equipo
        // SI, Bundles SI, Producción, Reporte Semanal incl. exportar, Tablero SI)
        // + escritura solo en SSA, Mis Notas SI (ya permitido arriba) y Tareas SI
        // (publica tareas para ECA/ELT; TareaController limita qué puede tocar).
        // Sin acceso a Seguimiento SIC, Herramientas SI, Configuración ni
        // Bitácora (Seguimiento SIC y Herramientas exigen consultor_digital/admin
        // más arriba; el resto no está en la lista blanca de abajo).
        if ($user->hasRole('coordinador')) {
            if ($request->routeIs('ssa.*') || $request->routeIs('tareas.*') || $request->routeIs('schools.students.export-by-class')) {
                return $next($request);
            }
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()->with('error_acceso', 'No tienes permisos para realizar esta acción.');
            }
            $rutasPermitidas = [
                'dashboard', 'dashboard.acciones-arranque.excel', 'reportes.general', 'reportes.zonas',
                'avance-colegios.*',
                'alumnos-docentes.*',
                'reporte-semanal.index', 'reporte-semanal.exportar',
                'schools.index', 'schools.show', 'schools.reporte-master',
                'schools.processes.index',
                'schools.teachers.index', 'schools.tickets.index',
                'schools.tickets.report.*', 'tickets.show',
                'schools.visits.index', 'schools.students.index',
                'schools.students.export-by-class',
                'schools.formato-entrega', 'schools.formato-entrega.script', 'schools.formato-entrega.accesos',
                'schools.bundles.index',
                'schools.repositorio.index', 'schools.repositorio.download',
                'consultants.index', 'consultants.show',
                'bundles.index',
                'produccion.index',
                'tablero.index',
                'seguimiento.show', 'seguimiento.colegio', 'seguimiento.excel',
                'seguimiento.recursos.preview', 'seguimiento.recursos.download',
            ];
            if (!$request->routeIs($rutasPermitidas)) {
                return redirect()->route('dashboard')
                    ->with('error_acceso', 'No tienes acceso a esta sección.');
            }
            return $next($request);
        }

        // Consul GED (Consultor Global Educa): consulta Dashboard, Avance Colegios, Reporte
        // Semanal, Colegios (incl. formatos y reportes), Bundles, Herramientas SI, Producción
        // y Seguimiento SIC; en Seguimiento solo escribe en Global Educa (validado arriba).
        // Escritura además en Mis Notas SI (permitido arriba). Sin Configuración, Equipo SI,
        // Alumnos Docentes, Calendario SSA, Tareas, Tablero ni Bitácora.
        if ($user->hasRole('consul_ged')) {
            // Seguimiento ya pasó su filtro de arriba (solo deja escribir en Global Educa)
            if ($request->routeIs('seguimiento.*') || $request->routeIs('schools.students.export-by-class')) {
                return $next($request);
            }
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()->with('error_acceso', 'No tienes permisos para realizar esta acción.');
            }
            $rutasPermitidas = [
                'dashboard', 'dashboard.acciones-arranque.excel', 'reportes.general', 'reportes.zonas',
                'avance-colegios.*',
                'reporte-semanal.index', 'reporte-semanal.exportar',
                'schools.index', 'schools.show', 'schools.reporte-master',
                'schools.processes.index',
                'schools.teachers.index', 'schools.tickets.index',
                'schools.tickets.report.*', 'tickets.show',
                'schools.visits.index', 'schools.students.index',
                'schools.formato-entrega', 'schools.formato-entrega.script', 'schools.formato-entrega.accesos',
                'schools.bundles.index',
                'schools.repositorio.index', 'schools.repositorio.download',
                'bundles.index',
                'herramientas.*',
                'produccion.index',
                'seguimiento-sic.*',
            ];
            if (!$request->routeIs($rutasPermitidas)) {
                return redirect()->route('dashboard')
                    ->with('error_acceso', 'No tienes acceso a esta sección.');
            }
            return $next($request);
        }

        // Ventas, Supervisor y Vendedor: lectura en todo el sistema (+ escritura SSA y descarga alumnos para ventas)
        if ($user->hasAnyRole(['representante_ventas', 'supervisor', 'vendedor'])) {
            if ($user->hasRole('representante_ventas') && ($request->routeIs('ssa.*') || $request->routeIs('schools.students.export-by-class'))) {
                return $next($request);
            }
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()->with('error_acceso', 'No tienes permisos para realizar esta acción.');
            }
            return $next($request);
        }

        // Consultor Digital: solo sus colegios asignados
        if ($user->hasRole('consultor_digital')) {
            if ($request->routeIs('processes.catalog.*')) {
                abort(403, 'Solo un administrador puede personalizar las acciones de arranque.');
            }

            $schoolParam = $request->route('school');

            // Rutas shallow (tickets, alumnos, docentes, visitas: ver/editar/borrar sin
            // {school} en la URL): el colegio sale del propio registro
            if (!$schoolParam) {
                $schoolParam = $this->colegioDeRutaShallow($request);
            }

            if ($schoolParam) {
                if (!AccesoColegios::consultor($user)) {
                    abort(403, 'No se encontró tu perfil de consultor.');
                }

                $schoolId = $schoolParam instanceof School
                    ? $schoolParam->id
                    : (int) $schoolParam;
                $asignado = AccesoColegios::esAsignado($user, $schoolId);

                // Seguimiento: el permiso depende de la plataforma de la URL. Meta lo consultan
                // todos los digitales; capturar es del asignado o del responsable de esa plataforma.
                if ($request->routeIs('seguimiento.*')) {
                    $plataforma = $this->plataformaDeSeguimiento($request);
                    $soloConsulta = $request->isMethod('GET') || $request->isMethod('HEAD');
                    $permitido = $asignado
                        || AccesoColegios::esResponsable($user, $schoolId, $plataforma)
                        || ($soloConsulta && $plataforma === 'meta');
                    abort_unless($permitido, 403, 'No tienes acceso a este colegio.');

                    return $next($request);
                }

                if (!$asignado) {
                    abort_unless(AccesoColegios::esResponsable($user, $schoolId), 403, 'No tienes acceso a este colegio.');

                    // Responsable de plataforma: todo menos borrar y cambiar los datos generales del colegio
                    if ($request->isMethod('DELETE')) {
                        abort(403, 'Solo el consultor asignado al colegio puede eliminar información.');
                    }
                    if ($request->routeIs(['schools.edit', 'schools.update'])) {
                        abort(403, 'Solo el consultor asignado puede editar los datos generales del colegio.');
                    }
                }
            }

            return $next($request);
        }

        // Rol desconocido o sin rol asignado
        abort(403, 'Tu cuenta no tiene un rol asignado. Contacta al administrador.');
    }

    /** Plataforma de una ruta de Seguimiento: el {plataforma} de la URL o la del archivo. */
    private function plataformaDeSeguimiento(Request $request): ?string
    {
        $plataforma = $request->route('plataforma');
        if (is_string($plataforma)) {
            return $plataforma;
        }
        $recurso = $request->route('recurso');
        if ($recurso instanceof \App\Models\SeguimientoRecurso) {
            return $recurso->plataforma;
        }

        return $recurso ? \App\Models\SeguimientoRecurso::whereKey($recurso)->value('plataforma') : null;
    }

    /** Modelos que se editan por ruta shallow y de los que se toma el colegio. */
    private const MODELOS_SHALLOW = [
        'ticket'  => \App\Models\Ticket::class,
        'student' => \App\Models\Student::class,
        'teacher' => \App\Models\Teacher::class,
        'visit'   => \App\Models\Visit::class,
    ];

    private function colegioDeRutaShallow(Request $request): ?int
    {
        foreach (self::MODELOS_SHALLOW as $param => $modelo) {
            $valor = $request->route($param);
            if ($valor === null) {
                continue;
            }
            $schoolId = $valor instanceof \Illuminate\Database\Eloquent\Model
                ? $valor->school_id
                : $modelo::whereKey($valor)->value('school_id');
            abort_unless($schoolId, 404);

            return (int) $schoolId;
        }

        return null;
    }
}
