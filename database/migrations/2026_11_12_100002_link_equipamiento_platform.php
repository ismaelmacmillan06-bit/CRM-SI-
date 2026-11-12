<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Seguimiento → Equipamiento: liga el servicio contable existente con su apartado.
// Solo actualiza un renglón; si el servicio no existe o ya hay uno ligado, no hace nada
// (se puede ligar a mano en Configuración → Servicios → Gestor de plataformas).
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('school_service_types')->where('plataforma', 'equipamiento')->exists()) {
            return;
        }

        $servicio = DB::table('school_service_types')
            ->where('name', 'Equipamiento')
            ->whereNull('plataforma')
            ->orderBy('id')
            ->first();

        if ($servicio) {
            DB::table('school_service_types')->where('id', $servicio->id)->update(['plataforma' => 'equipamiento']);
        }
    }

    public function down(): void
    {
        DB::table('school_service_types')->where('plataforma', 'equipamiento')->update(['plataforma' => null]);
    }
};
