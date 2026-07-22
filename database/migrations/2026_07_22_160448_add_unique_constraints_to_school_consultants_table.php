<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar duplicados antes de agregar constraints
        \DB::statement('
            DELETE sc1 FROM school_consultants sc1
            INNER JOIN school_consultants sc2
            WHERE sc1.id > sc2.id
              AND sc1.school_id = sc2.school_id
              AND sc1.consultant_id = sc2.consultant_id
        ');

        Schema::table('school_consultants', function (Blueprint $table) {
            // Un consultor solo puede tener UN rol por colegio
            $table->unique(['school_id', 'consultant_id'], 'uq_school_consultant');
            // Cada slot de rol solo puede estar ocupado por un consultor por colegio
            $table->unique(['school_id', 'role'], 'uq_school_role');
        });
    }

    public function down(): void
    {
        Schema::table('school_consultants', function (Blueprint $table) {
            $table->dropUnique('uq_school_consultant');
            $table->dropUnique('uq_school_role');
        });
    }
};
