<?php

namespace Database\Seeders;

use App\Models\ExternalPlatform;
use App\Models\ExternalPlatformStep;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExternalPlatformsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'Meta', 'slug' => 'meta', 'icon' => '📘', 'order' => 1,
                'steps' => [
                    'Alta de usuarios en META',
                    '1er Trimestre',
                    '2do Trimestre',
                    '3er Trimestre',
                ],
            ],
            [
                'name' => 'Read Roo Reads', 'slug' => 'read-roo-reads', 'icon' => '📖', 'order' => 2,
                'steps' => [
                    'Formato Docente',
                    'Formato Alumnos',
                    'Capacitación RRR',
                    'Entrega de usuarios RRR',
                ],
            ],
            [
                'name' => 'Espacevirtuel', 'slug' => 'espacevirtuel', 'icon' => '🇫🇷', 'order' => 3,
                'steps' => [
                    'Creación de clases',
                    'Alta de usuarios',
                    'Entrega de plataforma',
                ],
            ],
        ];

        foreach ($platforms as $p) {
            $platform = ExternalPlatform::updateOrCreate(
                ['slug' => $p['slug']],
                ['name' => $p['name'], 'icon' => $p['icon'], 'order' => $p['order']]
            );

            foreach ($p['steps'] as $i => $stepName) {
                ExternalPlatformStep::updateOrCreate(
                    ['external_platform_id' => $platform->id, 'slug' => Str::slug($stepName)],
                    ['name' => $stepName, 'order' => $i + 1]
                );
            }
        }
    }
}
