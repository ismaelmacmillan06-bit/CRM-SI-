<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BundleSeeder extends Seeder
{
    // Sheet name → type value in DB
    private array $sheetTypeMap = [
        'Macmillan Castillo SI ELT'       => 'ELT',
        'Macmillan Castillo SI Plan Lect' => 'Plan Lector',
        'Macmillan Castillo SI Imagina'   => 'Imagina',
        'Complementos'                    => 'Complemento',
        'Macmillan Castillo SI Wikids'    => 'Wikids',
        'Macmillan Castillo SI P.C'       => 'Pienso Contigo',
    ];

    public function run(): void
    {
        DB::table('bundles')->delete();

        $filePath   = base_path('Bundles SI (1).xlsx');
        $spreadsheet = IOFactory::load($filePath);

        $bundles = [];

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $type  = $this->sheetTypeMap[$sheetName] ?? 'ELT';
            $sheet = $spreadsheet->getSheetByName($sheetName);

            $currentSerie = '';
            $currentRole  = 'student';

            foreach ($sheet->getRowIterator() as $row) {
                $ri = $row->getRowIndex();
                $a  = trim((string) $sheet->getCell('A' . $ri)->getValue());
                $b  = trim((string) $sheet->getCell('B' . $ri)->getValue());
                $c  = trim((string) $sheet->getCell('C' . $ri)->getValue());
                $d  = trim((string) $sheet->getCell('D' . $ri)->getValue());

                if ($a === 'Serie:') {
                    $currentSerie = $b;
                    $currentRole  = 'student';
                    continue;
                }

                if ($a === 'Student') {
                    $currentRole = 'student';
                    continue;
                }

                if ($a === 'Teacher') {
                    $currentRole = 'teacher';
                    continue;
                }

                if ($a === 'Bundle Name' && $b !== '') {
                    $bundles[] = [
                        'serie'      => $currentSerie,
                        'name'       => $b,
                        'grade'      => $c !== '' ? $c : null,
                        'level'      => $d !== '' ? rtrim($d) : null,
                        'role'       => $currentRole,
                        'type'       => $type,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($bundles, 200) as $chunk) {
            DB::table('bundles')->insert($chunk);
        }

        $this->command->info('Bundles importados: ' . count($bundles));
    }
}
