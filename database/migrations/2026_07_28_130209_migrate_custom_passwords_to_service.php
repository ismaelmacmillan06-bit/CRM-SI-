<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar "Usuarios y contraseñas personalizadas" como servicio (order=0 para aparecer primero)
        DB::table('school_service_types')->insertOrIgnore([
            'name'       => 'Usuarios y contraseñas personalizadas',
            'icon'       => '🔐',
            'color'      => '#0891b2',
            'order'      => 0,
            'active'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $typeId = DB::table('school_service_types')
            ->where('name', 'Usuarios y contraseñas personalizadas')
            ->value('id');

        // Migrar colegios que ya tenían custom_passwords=true
        $schoolIds = DB::table('schools')->where('custom_passwords', true)->pluck('id');
        foreach ($schoolIds as $schoolId) {
            DB::table('school_service')->insertOrIgnore([
                'school_id'              => $schoolId,
                'school_service_type_id' => $typeId,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }
    }

    public function down(): void
    {
        $typeId = DB::table('school_service_types')
            ->where('name', 'Usuarios y contraseñas personalizadas')
            ->value('id');

        if ($typeId) {
            DB::table('school_service')->where('school_service_type_id', $typeId)->delete();
            DB::table('school_service_types')->where('id', $typeId)->delete();
        }
    }
};
