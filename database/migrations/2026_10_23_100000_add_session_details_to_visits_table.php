<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('modalidad', 20)->default('presencial');
            $table->json('productos')->nullable();
            $table->string('tipo', 20)->default('programada');
            $table->string('prioridad', 20)->default('media');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['modalidad', 'productos', 'tipo', 'prioridad']);
        });
    }
};
