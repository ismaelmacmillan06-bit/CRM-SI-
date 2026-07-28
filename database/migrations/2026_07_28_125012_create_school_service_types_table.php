<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon', 10)->default('📦');
            $table->string('color', 20)->default('#6366f1');
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Servicios iniciales
        DB::table('school_service_types')->insert([
            ['name' => 'Global Educa',  'icon' => '🌐', 'color' => '#6366f1', 'order' => 1, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lego',          'icon' => '🧱', 'color' => '#f59e0b', 'order' => 2, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Equipamiento',  'icon' => '🖥️',  'color' => '#64748b', 'order' => 3, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ree Roo Reads', 'icon' => '📖', 'color' => '#10b981', 'order' => 4, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('school_service_types');
    }
};
