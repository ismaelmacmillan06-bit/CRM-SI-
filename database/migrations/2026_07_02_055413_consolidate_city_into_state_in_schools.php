<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Elimina la doble fuente de verdad: mueve city → state en registros históricos
// donde state aún es null (colegios creados antes de agregar la columna state).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE schools SET state = city WHERE state IS NULL AND city IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE schools SET city = state WHERE city IS NULL AND state IS NOT NULL');
    }
};
