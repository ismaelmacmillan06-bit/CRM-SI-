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
        Schema::create('school_level_process', function (Blueprint $table) {
        $table->id();
        $table->foreignId('school_level_id')->constrained('school_level')->onDelete('cascade');
        $table->foreignId('process_id')->constrained('processes')->onDelete('cascade');
        $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
        $table->timestamp('completed_at')->nullable();
        $table->foreignId('completed_by')->nullable()->constrained('consultants')->onDelete('set null');
        $table->text('notes')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_level_process');
    }
};
