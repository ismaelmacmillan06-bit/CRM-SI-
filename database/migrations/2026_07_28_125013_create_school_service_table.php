<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_service_type_id')->constrained('school_service_types')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['school_id', 'school_service_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_service');
    }
};
