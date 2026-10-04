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

        // Mis Notas SI: bloc de notas personal — acceso completo (lectura y
        // escritura) para todos los roles por igual; el propio controlador
        // ya limita a cada quien a ver/editar solo sus notas.
        if ($request->routeIs('notes.*')) {
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

        // Producción: admin, consultor_digital y coordinador (este último solo lectura,
        // ver más abajo la rama de coordinador que bloquea los métodos de escritura)
        if ($request->routeIs('produccion.*') && !$user->hasAnyRole(['consultor_digital', 'coordinador'])) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
        }

        // Seguimiento Externo (beta): solo admin y consultor_digital
        if ($request->routeIs('seguimiento-externo.*') && !$user->hasRole('consultor_digital')) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
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
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()->with('error_acceso', 'No tienes permisos para realizar esta acción.');
            }
            $rutasPermitidas = ['dashboard', 'schools.*', 'tareas.index', 'alumnos-docentes.*', 'avance-colegios.*'];
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
        // + escritura solo en SSA y Mis Notas SI (ya permitido arriba). Sin acceso
        // a Seguimiento SIC/Externo, Herramientas SI, Configuración, Tareas SI ni
        // Bitácora (quedan bloqueados: las dos primeras y Herramientas exigen
        // consultor_digital/admin más arriba; Tareas SI y Bitácora simplemente no
        // están en la lista blanca de abajo).
        if ($user->hasRole('coordinador')) {
            if ($request->routeIs('ssa.*')) {
                return $next($request);
            }
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()->with('error_acceso', 'No tienes permisos para realizar esta acción.');
            }
            $rutasPermitidas = [
                'dashboard', 'dashboard.acciones-arranque.excel',
                'avance-colegios.*',
                'alumnos-docentes.*',
                'reporte-semanal.index', 'reporte-semanal.exportar',
                'schools.index', 'schools.show', 'schools.reporte-master',
                'schools.processes.index',
                'schools.teachers.index', 'schools.tickets.index',
                'schools.visits.index', 'schools.students.index',
                'schools.bundles.index',
                'schools.repositorio.index', 'schools.repositorio.download',
                'consultants.index', 'consultants.show',
                'bundles.index',
                'produccion.index',
                'tablero.index',
            ];
            if (!$request->routeIs($rutasPermitidas)) {
                return redirect()->route('dashboard')
                    ->with('error_acceso', 'No tienes acceso a esta sección.');
            }
            return $next($request);
        }

        // Ventas: lectura en todo el sistema + escritura en SSA
        if ($user->hasRole('representante_ventas')) {
            if ($request->routeIs('ssa.*')) {
                return $next($request);
            }
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()->with('error_acceso', 'No tienes permisos para realizar esta acción.');
            }
            return $next($request);
        }

        // Consultor Digital: solo sus colegios asignados
        if ($user->hasRole('consultor_digital')) {
            $schoolParam = $request->route('school');

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
