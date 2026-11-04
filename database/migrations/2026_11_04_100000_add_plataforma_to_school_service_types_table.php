<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// "Gestor de plataformas": clave fija que liga un servicio con su apartado de Seguimiento,
// para no depender del nombre ni del ID del servicio (cambian entre local y producción).
return new class extends Migration
{
    private const PLATAFORMAS = [
        'meta'          => ['nombre' => 'Meta',          'icono' => '🎯', 'color' => '#2563eb'],
        'global-educa'  => ['nombre' => 'Global Educa',  'icono' => '🌐', 'color' => '#6366f1'],
        'frances'       => ['nombre' => 'Francés',       'icono' => '🗼', 'color' => '#db2777'],
        'red-roo-reads' => ['nombre' => 'Red Roo Reads', 'icono' => '📖', 'color' => '#10b981'],
    ];

    public function up(): void
    {
        Schema::table('school_service_types', function (Blueprint $table) {
            $table->string('plataforma', 30)->nullable()->unique();
        });

        foreach (self::PLATAFORMAS as $clave => $datos) {
            $servicio = DB::table('school_service_types')->where('name', $datos['nombre'])->first();

            if ($servicio) {
                DB::table('school_service_types')->where('id', $servicio->id)->update(['plataforma' => $clave]);
                continue;
            }

            // Global Educa y Red Roo Reads vienen de su propia migración: si faltan, es que en
            // este entorno tienen otro nombre y se ligan a mano en Configuración → Servicios.
            if (in_array($clave, ['meta', 'frances'], true)) {
                DB::table('school_service_types')->insert([
                    'name'       => $datos['nombre'],
                    'icon'       => $datos['icono'],
                    'color'      => $datos['color'],
                    'order'      => (DB::table('school_service_types')->max('order') ?? 0) + 1,
                    'active'     => true,
                    'plataforma' => $clave,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('school_service_types', function (Blueprint $table) {
            $table->dropUnique(['plataforma']);
            $table->dropColumn('plataforma');
        });
    }
};
