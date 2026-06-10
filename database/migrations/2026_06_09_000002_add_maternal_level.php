<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('levels')->where('name', 'Maternal')->exists()) {
            DB::table('levels')->insert([
                'name'       => 'Maternal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('levels')->where('name', 'Maternal')->delete();
    }
};
