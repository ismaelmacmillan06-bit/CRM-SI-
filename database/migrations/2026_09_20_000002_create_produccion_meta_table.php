<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fila única con datos del último Excel cargado (nombre, fecha de corte, quién lo subió).
        Schema::create('produccion_meta', function (Blueprint $table) {
            $table->id();
            $table->string('archivo_nombre')->nullable();
            $table->string('corte')->nullable();
            $table->unsignedInteger('total_filas')->default(0);
            $table->foreignId('cargado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cargado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion_meta');
    }
};
