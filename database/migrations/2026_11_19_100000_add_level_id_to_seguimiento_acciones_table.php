<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seguimiento_acciones', function (Blueprint $table) {
            $table->index('school_id', 'seguimiento_acciones_school_id_idx');
            $table->foreignId('level_id')->nullable()->after('plataforma')->constrained('levels')->cascadeOnDelete();
        });

        Schema::table('seguimiento_acciones', function (Blueprint $table) {
            $table->dropUnique('seguimiento_acciones_unica');
            $table->unique(['school_id', 'plataforma', 'level_id', 'accion'], 'seguimiento_acciones_unica');
        });
    }

    public function down(): void
    {
        Schema::table('seguimiento_acciones', function (Blueprint $table) {
            $table->dropUnique('seguimiento_acciones_unica');
            $table->unique(['school_id', 'plataforma', 'accion'], 'seguimiento_acciones_unica');
        });

        Schema::table('seguimiento_acciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('level_id');
            $table->dropIndex('seguimiento_acciones_school_id_idx');
        });
    }
};
