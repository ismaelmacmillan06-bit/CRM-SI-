<?php

namespace App\Console\Commands;

use App\Models\Bundle;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarNuevasSeries extends Command
{
    protected $signature   = 'bundles:importar-nuevas-series';
    protected $description = 'Importa las nuevas series desde "Bundles SI Nuevas Series.xlsx" en la raíz del proyecto';

    // Series que son Plan Lector en vez de ELT
    private array $planLector = [
        'Reading Plan Preescolar',
        'Reading Plan Primaria',
        'Reading Plan Secundaria',
    ];

    public function handle(): int
    {
        $path = base_path('Bundles SI Nuevas Series.xlsx');

        if (!file_exists($path)) {
            $this->error('No se encontró "Bundles SI Nuevas Series.xlsx" en la raíz del proyecto.');
            return 1;
        }

        $this->info('Leyendo archivo...');
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        $serie     = '';
        $role      = 'student';
        $agregados = 0;
        $omitidos  = 0;
        $errores   = [];

        foreach ($rows as $rowNum => $row) {
            $colA = trim((string)($row['A'] ?? ''));
            $colB = trim((string)($row['B'] ?? ''));
            $colC = trim((string)($row['C'] ?? ''));
            $colD = trim((string)($row['D'] ?? ''));

            // ── Encabezado de serie ──
            if (str_starts_with($colA, 'Serie:')) {
                $serie = $colB;
                $role  = 'student';
                continue;
            }

            // ── Separador Student / Teacher ──
            $colALower = strtolower($colA);
            if ($colALower === 'student') { $role = 'student'; continue; }
            if ($colALower === 'teacher') { $role = 'teacher'; continue; }

            // ── Fila de bundle ──
            if ($colA !== 'Bundle Name' || $colB === '') continue;
            if ($serie === '') continue;

            // Normalizar nombres en MAYÚSCULAS (ej. "GLOBAL STAGE LEVEL 1 LANGUAGE WORKBOOK...")
            $name = ($colB === mb_strtoupper($colB) && mb_strlen($colB) > 5)
                ? mb_convert_case($colB, MB_CASE_TITLE, 'UTF-8')
                : $colB;

            $grade = $colC ?: null;
            $level = $colD ?: null;
            $type  = \in_array($serie, $this->planLector) ? 'Plan Lector' : 'ELT';

            // Saltar duplicados por nombre exacto
            if (Bundle::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                $omitidos++;
                continue;
            }

            try {
                Bundle::create([
                    'serie' => $serie,
                    'name'  => $name,
                    'grade' => $grade,
                    'level' => $level,
                    'role'  => $role,
                    'type'  => $type,
                ]);
                $agregados++;
            } catch (\Exception $e) {
                $errores[] = "Fila {$rowNum}: {$name} — " . $e->getMessage();
            }
        }

        $this->info("✅ Se importaron {$agregados} bundle(s).");

        if ($omitidos) {
            $this->warn("Se omitieron {$omitidos} bundle(s) que ya existían en el catálogo.");
        }
        if (\count($errores)) {
            $this->error('Errores:');
            foreach ($errores as $e) { $this->line("  {$e}"); }
        }

        return 0;
    }
}
