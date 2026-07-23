<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bundles MODIFY type ENUM('ELT','Plan Lector','Imagina','Wikids','Pienso Contigo','Complemento','Entrelineas','Palabrario','Frances','Ecosistemas','Praxis','Ekos','Carrousel') DEFAULT 'ELT'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bundles MODIFY type ENUM('ELT','Plan Lector','Imagina','Wikids','Pienso Contigo','Complemento','Entrelineas','Palabrario','Frances') DEFAULT 'ELT'");
        }
    }
};
