<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('teachers', function (Blueprint $table) {
        $table->enum('role', ['docente', 'director_general', 'director_nivel', 'coordinador'])
              ->default('docente')->after('grade');
        $table->enum('subject', ['ECA', 'ELT', 'ambos', 'ninguno'])
              ->default('ninguno')->after('role');
    });
}

public function down(): void
{
    Schema::table('teachers', function (Blueprint $table) {
        $table->dropColumn(['role', 'subject']);
    });
}
};
