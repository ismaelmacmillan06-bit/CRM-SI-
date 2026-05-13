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
        Schema::create('schools', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('nexus_id')->nullable();
        $table->string('address')->nullable();
        $table->string('city')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->enum('status', ['prospecto', 'activo', 'inactivo'])->default('prospecto');
        $table->text('notes')->nullable();
        $table->foreignId('consultant_id')->constrained()->onDelete('cascade');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
