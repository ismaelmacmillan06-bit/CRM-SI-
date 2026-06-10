<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolLevel;
use App\Models\SchoolLevelProcess;

class ProcessesSeeder extends Seeder
{
    public function run(): void
    {
        $processes = [
            ['name' => 'Alta de Bundles',                  'slug' => 'alta_bundles',            'order' => 1],
            ['name' => 'Capacitación del administrador',   'slug' => 'capacitacion_admin',      'order' => 2],
            ['name' => 'Registrar profesores',             'slug' => 'registrar_profesores',    'order' => 3],
            ['name' => 'Creación de clases',               'slug' => 'creacion_clases',         'order' => 4],
            ['name' => 'Libro del profesor',               'slug' => 'libro_profesor',          'order' => 5],
            ['name' => 'Alta de alumnos',                  'slug' => 'alta_alumnos',            'order' => 6],
            ['name' => 'Asignación del libro del alumno',  'slug' => 'asignacion_libro_alumno', 'order' => 7],
            ['name' => 'Generar contraseñas',              'slug' => 'generar_contrasenas',     'order' => 8],
            ['name' => 'Alta en servicios alumno',         'slug' => 'alta_servicios_alumno',   'order' => 9],
            ['name' => 'Entrega del colegio',              'slug' => 'entrega_colegio',         'order' => 10],
        ];

        // 1) Crea o ACTUALIZA el catálogo (identifica por 'order', así renombra sin duplicar
        //    y sin romper los procesos que ya tienen los colegios)
        foreach ($processes as $process) {
            DB::table('processes')->updateOrInsert(
                ['order' => $process['order']],
                ['name' => $process['name'], 'slug' => $process['slug']]
            );
        }

        // 2) Rellena los procesos que falten en los colegios YA creados,
        //    sin tocar el avance que ya tengan guardado
        $allProcesses = DB::table('processes')->get();

        foreach (SchoolLevel::all() as $schoolLevel) {
            foreach ($allProcesses as $process) {
                SchoolLevelProcess::firstOrCreate(
                    [
                        'school_level_id' => $schoolLevel->id,
                        'process_id'      => $process->id,
                    ],
                    ['status' => 'pending']
                );
            }
        }
    }
}