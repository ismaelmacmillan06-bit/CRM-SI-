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
    Schema::table('visits', function (Blueprint $table) {
        $table->enum('status', ['pendiente', 'en_curso', 'terminada'])->default('pendiente')->after('visit_date');
        $table->date('scheduled_date')->nullable()->after('status');
        $table->text('summary')->nullable()->after('scheduled_date');
        $table->string('evidence')->nullable()->after('summary');
    });
}

public function down(): void
{
    Schema::table('visits', function (Blueprint $table) {
        $table->dropColumn(['status', 'scheduled_date', 'summary', 'evidence']);
    });
}
};