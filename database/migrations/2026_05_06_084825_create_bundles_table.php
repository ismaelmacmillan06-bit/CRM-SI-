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
    Schema::create('bundles', function (Blueprint $table) {
        $table->id();
        $table->string('serie');
        $table->string('name');
        $table->string('grade')->nullable();
        $table->string('level')->nullable();
        $table->enum('role', ['student', 'teacher'])->default('student');
        $table->enum('type', ['ELT', 'Plan Lector', 'Imagina', 'Wikids', 'Pienso Contigo'])->default('ELT');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundles');
    }
};
