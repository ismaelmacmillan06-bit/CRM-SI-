<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProcessesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $processes = [
            ['name' => 'High Bondules',             'slug' => 'high_bondules',          'order' => 1],
            ['name' => 'Admin Training',             'slug' => 'admin_training',          'order' => 2],
            ['name' => 'Register Teachers',          'slug' => 'register_teachers',       'order' => 3],
            ['name' => 'Class Creation',             'slug' => 'class_creation',          'order' => 4],
            ['name' => 'Teacher Book Assignment',    'slug' => 'teacher_book_assignment', 'order' => 5],
            ['name' => 'Student Registration',       'slug' => 'student_registration',    'order' => 6],
            ['name' => 'Student Book Assignment',    'slug' => 'student_book_assignment', 'order' => 7],
            ['name' => 'Generate Passwords',         'slug' => 'generate_passwords',      'order' => 8],
        ];

        foreach ($processes as $process) {
            DB::table('processes')->insert($process);
        }
    }
}
