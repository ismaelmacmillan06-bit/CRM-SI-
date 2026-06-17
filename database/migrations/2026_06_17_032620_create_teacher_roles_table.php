<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->string('role');
            $table->timestamps();
            $table->unique(['teacher_id', 'role']);
        });

        // Migrate existing single-role data
        DB::table('teachers')->whereNotNull('role')->get()
            ->each(function ($t) {
                DB::table('teacher_roles')->insertOrIgnore([
                    'teacher_id' => $t->id,
                    'role'       => $t->role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        // Drop old column
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('role')->nullable()->after('grade');
        });
        Schema::dropIfExists('teacher_roles');
    }
};
