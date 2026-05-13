<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = ['Preescolar', 'Primaria', 'Secundaria', 'Preparatoria', 'Licenciatura'];

        foreach ($levels as $level) {
            DB::table('levels')->insert(['name' => $level]);
            }
    }
}
