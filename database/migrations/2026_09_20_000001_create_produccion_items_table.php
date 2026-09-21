<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion_items', function (Blueprint $table) {
            $table->id();
            $table->string('nivel')->nullable();
            $table->string('grado')->nullable();
            $table->string('colegio_id')->nullable()->index();
            $table->string('colegio')->nullable();
            $table->string('tipo_pago')->nullable();
            $table->string('serie')->nullable()->index();
            $table->string('empresa')->nullable();
            $table->string('titulo')->nullable();
            $table->string('codigo_isbn')->nullable();
            $table->string('codigo_gs1')->nullable();
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion_items');
    }
};
