<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CoordinadorRoleSeeder extends Seeder
{
    /**
     * Nuevo rol "coordinador" — nombrado en español para seguir la
     * convención ya usada (consultor_digital, consultor_eca, consultor_elt,
     * representante_ventas). Se crea sin permisos/acciones todavía;
     * se define qué puede hacer en un paso posterior.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'coordinador']);
    }
}
