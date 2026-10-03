<?php

namespace App\Traits;

use App\Models\School;
use Illuminate\Http\RedirectResponse;

trait BloqueaColegioInactivo
{
    /**
     * Si el colegio está inactivo, no se puede crear ni cargar nada nuevo
     * en sus apartados (alumnos, docentes, tickets, visitas, bundles,
     * acciones de arranque, seguimiento externo) hasta reactivarlo en
     * "Editar colegio". Editar/eliminar registros ya existentes sí se
     * permite.
     */
    protected function bloqueadoPorInactivo(School $school): ?RedirectResponse
    {
        if ($school->status === 'inactivo') {
            return back()->with('error', 'Este colegio está inactivo. Actívalo en "Editar colegio" para poder agregar registros.');
        }

        return null;
    }
}
