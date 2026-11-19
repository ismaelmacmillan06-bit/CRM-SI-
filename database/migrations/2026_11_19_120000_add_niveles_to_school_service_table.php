<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_service', function (Blueprint $table) {
            $table->json('niveles')->nullable()->after('school_service_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('school_service', function (Blueprint $table) {
            $table->dropColumn('niveles');
        });
    }
};
