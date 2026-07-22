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

        // Herramientas SI: solo admin y consultor_digital
        if ($request->routeIs('herramientas.*') && !$user->hasRole('consultor_digital')) {
            return redirect()->route('dashboard')
                ->with('error_acceso', 'No tienes permisos de acceso para esta sección.');
        }

        // ECA y ELT: solo lectura y solo sección Colegios
        if ($user->hasAnyRole(['consultor_eca', 'consultor_elt'])) {
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()->with('error_acceso', 'No tienes permisos para realizar esta acción.');
            }
            $rutasPermitidas = ['dashboard', 'schools.*'];
            if (!$request->routeIs($rutasPermitidas)) {
                return redirect()->route('schools.index')
                    ->with('error_acceso', 'Solo tienes acceso a la sección de Colegios.');
            }
            // No pueden generar el Report Master
            if ($request->routeIs('schools.reporte-master')) {
                abort(403, 'No tienes permisos para generar este reporte.');
            }
            return $next($request);
        }

        // Ventas: solo lectura en todo el sistema
        if ($user->hasRole('representante_ventas')) {
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
