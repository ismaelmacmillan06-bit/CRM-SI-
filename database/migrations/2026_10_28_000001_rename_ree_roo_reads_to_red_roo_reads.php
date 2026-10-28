<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// El nombre correcto del producto es "Red Roo Reads" (antes se capturó como "Ree Roo Reads").
// Solo renombra datos: no borra nada.
return new class extends Migration
{
    public function up(): void
    {
        // Servicio contable (solo si aún no existe uno con el nombre correcto)
        if (! DB::table('school_service_types')->where('name', 'Red Roo Reads')->exists()) {
            DB::table('school_service_types')
                ->where('name', 'Ree Roo Reads')
                ->update(['name' => 'Red Roo Reads', 'updated_at' => now()]);
        }

        // Productos guardados en las sesiones (visits.productos es JSON con los nombres)
        DB::table('visits')
            ->where('productos', 'like', '%Ree Roo Reads%')
            ->orderBy('id')
            ->each(function ($visit) {
                $productos = json_decode($visit->productos, true) ?: [];
                $productos = array_values(array_unique(array_map(
                    fn ($p) => $p === 'Ree Roo Reads' ? 'Red Roo Reads' : $p,
                    $productos
                )));
                DB::table('visits')->where('id', $visit->id)->update(['productos' => json_encode($productos)]);
            });
    }

    public function down(): void
    {
        // Sin reversa: el nombre anterior tenía un error de captura.
    }
};
