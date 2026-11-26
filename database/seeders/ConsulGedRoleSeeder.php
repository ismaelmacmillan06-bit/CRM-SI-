<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ConsulGedRoleSeeder extends Seeder
{
    /**
     * Rol "consul_ged" (Consultor Global Educa): consulta casi todo y tiene
     * libertad total solo en Seguimiento → Global Educa. Los permisos viven
     * en VerificarAccesoRol y SeguimientoController::PLATAFORMAS_CONSUL_GED.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'consul_ged', 'guard_name' => 'web']);
    }
}
