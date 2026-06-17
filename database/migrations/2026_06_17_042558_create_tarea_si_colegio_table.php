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
        Schema::create('tarea_si_colegio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_si_id')->constrained('tareas_si')->onDelete('cascade');
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pendiente', 'en_proceso', 'realizada'])->default('pendiente');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tarea_si_id', 'school_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarea_si_colegio');
    }
};
