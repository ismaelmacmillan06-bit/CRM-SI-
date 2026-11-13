<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimiento_recursos', function (Blueprint $table) {
            $table->id();
            $table->string('plataforma', 40);
            $table->string('categoria', 40);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('archivo', 500);
            $table->string('archivo_nombre');
            $table->string('extension', 20);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamano')->default(0);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['plataforma', 'categoria']);
        });

        Schema::create('seguimiento_recurso_audiencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seguimiento_recurso_id')
                ->constrained('seguimiento_recursos')
                ->cascadeOnDelete();
            $table->string('rol', 40);
            $table->timestamps();

            $table->unique(['seguimiento_recurso_id', 'rol'], 'seg_rec_aud_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_recurso_audiencias');
        Schema::dropIfExists('seguimiento_recursos');
    }
};
