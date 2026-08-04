<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ssa_capacitaciones MODIFY tipo ENUM('eca','elt') NOT NULL DEFAULT 'eca'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ssa_capacitaciones MODIFY tipo ENUM('capacitacion','seguimiento','demo','presentacion','otro') NOT NULL DEFAULT 'capacitacion'");
    }
};
