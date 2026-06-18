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
        Schema::create('bundle_resurtidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('bundle_id')->constrained()->onDelete('cascade');
            $table->integer('cantidad_anterior');
            $table->integer('cantidad_resurtido');
            $table->integer('cantidad_nueva');
            $table->string('autorizado_por')->nullable();
            $table->date('fecha');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_resurtidos');
    }
};
