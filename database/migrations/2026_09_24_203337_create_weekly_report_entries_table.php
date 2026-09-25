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
        Schema::create('weekly_report_entries', function (Blueprint $table) {
            $table->id();
            $table->date('week_start');
            $table->string('category', 40);
            $table->foreignId('consultant_id')->constrained()->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['week_start', 'category', 'consultant_id'], 'weekly_report_entry_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_report_entries');
    }
};
