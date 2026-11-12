<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Agenda de exámenes Meta: un renglón por colegio con los datos de la plataforma,
// la capacitación y la fecha de aplicación del examen.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimiento_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('administrador')->nullable();
            $table->string('usuario')->nullable();
            $table->string('contrasena')->nullable();
            $table->string('capacitacion')->nullable();
            $table->date('fecha_capacitacion')->nullable();
            $table->date('fecha_aplicacion')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_meta');
    }
};
