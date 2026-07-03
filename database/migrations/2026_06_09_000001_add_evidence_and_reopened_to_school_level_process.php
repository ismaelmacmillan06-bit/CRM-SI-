<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_level_process', function (Blueprint $table) {
            $table->string('evidence')->nullable()->after('notes');
        });

        // MODIFY COLUMN es sintaxis MySQL; SQLite no la soporta y no necesita ENUMs (valida en app)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE school_level_process MODIFY COLUMN status ENUM('pending','in_progress','done','reopened') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE school_level_process MODIFY COLUMN status ENUM('pending','in_progress','done') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('school_level_process', function (Blueprint $table) {
            $table->dropColumn('evidence');
        });
    }
};
