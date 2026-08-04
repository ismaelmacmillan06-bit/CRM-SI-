<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssa_capacitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora')->nullable();
            $table->enum('tipo', ['capacitacion', 'seguimiento', 'demo', 'presentacion', 'otro'])
                  ->default('capacitacion');
            $table->enum('estatus', ['pendiente', 'confirmado', 'realizado', 'cancelado'])
                  ->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssa_capacitaciones');
    }
};
