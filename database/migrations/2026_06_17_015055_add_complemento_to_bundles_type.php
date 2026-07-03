<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY COLUMN es sintaxis MySQL; SQLite no la soporta y no necesita ENUMs
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bundles MODIFY type ENUM('ELT','Plan Lector','Imagina','Wikids','Pienso Contigo','Complemento') DEFAULT 'ELT'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bundles MODIFY type ENUM('ELT','Plan Lector','Imagina','Wikids','Pienso Contigo') DEFAULT 'ELT'");
        }
    }
};
