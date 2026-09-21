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
        Schema::create('school_external_platform_step', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_platform_step_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'done'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('consultants')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'external_platform_step_id'], 'school_ext_step_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_external_platform_step');
    }
};
