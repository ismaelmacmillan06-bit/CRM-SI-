<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A quién va dirigida la tarea: digital (las de siempre), eca, elt o
     * academico (ECA y ELT). Las existentes quedan como digital.
     */
    public function up(): void
    {
        Schema::table('tareas_si', function (Blueprint $table) {
            $table->string('audiencia', 20)->default('digital')->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('tareas_si', function (Blueprint $table) {
            $table->dropColumn('audiencia');
        });
    }
};
