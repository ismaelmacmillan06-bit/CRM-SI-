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
        Schema::create('visit_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->foreignId('consultant_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['visit_id', 'consultant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_attendees');
    }
};
