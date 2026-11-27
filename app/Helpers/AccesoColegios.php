<?php

namespace App\Helpers;

use App\Http\Controllers\SeguimientoController;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\User;

/**
 * Acceso de un consultor digital a los colegios:
 *  - Asignado: el colegio es suyo (school_consultants con rol digital). Puede todo, incluso borrar.
 *  - Responsable de plataforma: en Equipo SI tiene marcada esa plataforma de Seguimiento; en los
 *    colegios que la llevan puede todo MENOS borrar y cambiar los datos generales del colegio.
 *  - consul_ged (Consultor Global Educa): no tiene colegios asignados; es responsable automático
 *    de las plataformas de SeguimientoController::PLATAFORMAS_CONSUL_GED (Global Educa).
 * Admin siempre puede todo. Los demás roles se rigen por VerificarAccesoRol.
 */
class AccesoColegios
{
    public static function consultor(User $user): ?Consultant
    {
        return self::recordar("consultor.{$user->id}", fn () => Consultant::where('user_id', $user->id)->first());
    }

    /** Colegios asignados como consultor digital. */
    public static function idsAsignados(User $user): array
    {
        return self::recordar("asignados.{$user->id}", function () use ($user) {
            $consultor = self::consultor($user);

            return $consultor
                ? SchoolConsultant::where('consultant_id', $consultor->id)->where('role', 'digital')
                    ->pluck('school_id')->map(fn ($id) => (int) $id)->unique()->values()->all()
                : [];
        });
    }

    /** Plataformas de Seguimiento de las que es responsable (consultores digitales por Equipo SI; consul_ged automático). */
    public static function plataformasResponsable(User $user): array
    {
        if ($user->hasRole('consul_ged')) {
            return SeguimientoController::PLATAFORMAS_CONSUL_GED;
        }
        if (!$user->hasRole('consultor_digital')) {
            return [];
        }

        return array_values(array_intersect(
            (array) (self::consultor($user)?->responsable_seguimiento ?? []),
            array_keys(SeguimientoController::PLATAFORMAS)
        ));
    }

    /** Colegios que llevan alguna de sus plataformas (o la indicada) de las que es responsable. */
    public static function idsResponsable(User $user, ?string $plataforma = null): array
    {
        $plataformas = self::plataformasResponsable($user);
        if ($plataforma !== null) {
            $plataformas = in_array($plataforma, $plataformas, true) ? [$plataforma] : [];
        }
        if (!$plataformas) {
            return [];
        }

        return self::recordar('responsable.' . $user->id . '.' . implode(',', $plataformas), fn () => School::whereHas(
            'services', fn ($q) => $q->whereIn('plataforma', $plataformas)
        )->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    /**
     * Colegios que cuentan en sus tableros (Dashboard, Avance, Bitácora, reportes): null = todos.
     * Digital: asignados + los de su responsabilidad (colección, aunque venga vacía: vacía = ninguno).
     * Los demás roles ven todo.
     */
    public static function idsTablero(User $user): ?\Illuminate\Support\Collection
    {
        return $user->hasRole('consultor_digital') ? collect(self::idsGestionables($user)) : null;
    }

    /** Asignados + los que gestiona como responsable. */
    public static function idsGestionables(User $user): array
    {
        return array_values(array_unique(array_merge(self::idsAsignados($user), self::idsResponsable($user))));
    }

    public static function esAsignado(User $user, School|int $school): bool
    {
        return in_array(self::id($school), self::idsAsignados($user), true);
    }

    public static function esResponsable(User $user, School|int $school, ?string $plataforma = null): bool
    {
        return in_array(self::id($school), self::idsResponsable($user, $plataforma), true);
    }

    /** Puede trabajar en el colegio (capturar, crear, editar, exportar). */
    public static function puedeGestionar(?User $user, School|int $school): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('consul_ged')) {
            return self::esResponsable($user, $school);
        }

        return $user->hasRole('consultor_digital')
            && (self::esAsignado($user, $school) || self::esResponsable($user, $school));
    }

    /** Puede borrar datos del colegio: solo admin y el consultor digital asignado. */
    public static function puedeEliminar(?User $user, School|int $school): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin') || ($user->hasRole('consultor_digital') && self::esAsignado($user, $school));
    }

    private static function id(School|int $school): int
    {
        return (int) ($school instanceof School ? $school->id : $school);
    }

    /** Memoriza por petición (no entre peticiones) para no repetir consultas. */
    private static function recordar(string $clave, \Closure $calcular): mixed
    {
        $atributos = request()->attributes;
        $clave = 'acceso_colegios.' . $clave;
        if (!$atributos->has($clave)) {
            $atributos->set($clave, $calcular());
        }

        return $atributos->get($clave);
    }
}
