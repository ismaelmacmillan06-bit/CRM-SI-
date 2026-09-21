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
        Schema::create('external_platform_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_platform_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedTinyInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_platform_steps');
    }
};
