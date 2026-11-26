<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Responsables de plataforma (Equipo SI): lista de slugs de Seguimiento (meta, global-educa…)
 * de los que el consultor digital es responsable en todos los colegios que la llevan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('consultants', 'responsable_seguimiento')) {
            Schema::table('consultants', function (Blueprint $table) {
                $table->json('responsable_seguimiento')->nullable()->after('zone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('consultants', 'responsable_seguimiento')) {
            Schema::table('consultants', function (Blueprint $table) {
                $table->dropColumn('responsable_seguimiento');
            });
        }
    }
};
