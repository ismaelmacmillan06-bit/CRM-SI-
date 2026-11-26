<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rol "consul_ged" (Consultor Global Educa). Se crea por migración para que
 * exista en todos los entornos sin depender de correr seeders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Role::firstOrCreate(['name' => 'consul_ged', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Sin borrado: el rol puede tener usuarios asignados.
    }
};
