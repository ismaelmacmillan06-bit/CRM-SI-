<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Horario de la capacitación Meta (se muestra en la tabla y en el calendario)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seguimiento_meta', function (Blueprint $table) {
            $table->time('hora_capacitacion')->nullable()->after('fecha_capacitacion');
        });
    }

    public function down(): void
    {
        Schema::table('seguimiento_meta', function (Blueprint $table) {
            $table->dropColumn('hora_capacitacion');
        });
    }
};
