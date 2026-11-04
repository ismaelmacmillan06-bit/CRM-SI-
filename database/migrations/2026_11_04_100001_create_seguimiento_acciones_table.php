<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimiento_acciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('plataforma', 30);   // clave de SeguimientoController::PLATAFORMAS
            $table->string('accion', 50);       // clave de la acción dentro de la plataforma
            $table->string('status', 20)->default('pending'); // pending | in_progress | done
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'plataforma', 'accion'], 'seguimiento_acciones_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_acciones');
    }
};
