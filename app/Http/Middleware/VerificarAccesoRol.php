<?php

namespace App\Http\Middleware;

use App\Models\Consultant;
use App\Models\SchoolConsultant;
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

        // Herramientas SI: solo admin y consultor_digital
        if ($request->routeIs('herramientas.*') && !$user->hasRole('consultor_digital')) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
        }

        // Seguimiento SIC: solo admin y consultor_digital
        if ($request->routeIs('seguimiento-sic.*') && !$user->hasRole('consultor_digital')) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
        }

        // Producción: admin y consultor_digital cargan/borran; coordinador, ECA, ELT y ventas solo
        // consultan (sus ramas de abajo bloquean POST/DELETE y la vista oculta cargar/borrar)
        if ($request->routeIs('produccion.*') && !$user->hasAnyRole(['consultor_digital', 'coordinador', 'consultor_eca', 'consultor_elt', 'representante_ventas'])) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
        }

        // Seguimiento (Meta, Global Educa, Francés, Red Roo Reads, Equipamiento): solo admin edita o
        // personaliza acciones de arranque; el resto de roles con acceso solo consulta y exporta Excel.
        if ($request->routeIs('seguimiento.*')) {
            if (!$user->hasAnyRole(['consultor_digital', 'consultor_eca', 'consultor_elt', 'representante_ventas', 'coordinador'])) {
                return redirect()->route('dashboard')
                    ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
            }
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()->with('error_acceso', 'Solo un administrador puede modificar o configurar las acciones de arranque.');
            }
        }

        // Reporte Semanal: admin, consultor_digital y coordinador (este último solo
        // puede consultar/exportar, no guardar — ver rama de coordinador más abajo)
        if ($request->routeIs('reporte-semanal.*') && !$user->hasAnyRole(['consultor_digital', 'coordinador'])) {
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
            if ($request->routeIs('schools.processes.*') && !$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                abort(403, 'Solo un administrador puede modificar las acciones de arranque del colegio.');
            }

            $schoolParam = $request->route('school');

            // Rutas shallow de tickets (ver/editar/borrar): el colegio sale del ticket
            if (!$schoolParam && $request->route('ticket')) {
                $ticket = $request->route('ticket');
                $schoolParam = $ticket instanceof \App\Models\Ticket
                    ? $ticket->school_id
                    : \App\Models\Ticket::whereKey($ticket)->value('school_id');
                abort_unless($schoolParam, 404);
            }

            if ($schoolParam) {
                $consultant = Consultant::where('user_id', $user->id)->first();

                if (!$consultant) {
                    abort(403, 'No se encontró tu perfil de consultor.');
                }

                $assignedIds = SchoolConsultant::where('consultant_id', $consultant->id)
                    ->where('role', 'digital')
                    ->pluck('school_id')
                    ->toArray();

                $schoolId = $schoolParam instanceof School
                    ? $schoolParam->id
                    : (int) $schoolParam;

                if (!in_array($schoolId, $assignedIds)) {
                    abort(403, 'No tienes acceso a este colegio.');
                }
            }

            return $next($request);
        }

        // Rol desconocido o sin rol asignado
        abort(403, 'Tu cuenta no tiene un rol asignado. Contacta al administrador.');
    }
}
