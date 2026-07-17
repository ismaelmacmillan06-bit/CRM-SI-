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
        Schema::table('comunicados', function (Blueprint $table) {
            $table->string('enlace')->nullable()->after('archivo_tipo');
            $table->string('enlace_texto')->nullable()->after('enlace');
        });
    }

    public function down(): void
    {
        Schema::table('comunicados', function (Blueprint $table) {
            $table->dropColumn(['enlace', 'enlace_texto']);
        });
    }
};
