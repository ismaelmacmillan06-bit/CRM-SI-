<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Consultant;
use App\Models\SchoolConsultant;
use App\Models\Level;
use App\Models\Process;
use App\Models\SchoolLevel;
use App\Models\SchoolLevelProcess;
use Illuminate\Http\Request;
use App\Models\MeeAdmin;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SchoolController extends Controller
{
    public function index()
    {
        $query = School::with('levels', 'schoolConsultants.consultant.user');

        if (auth()->user()->hasRole('consultor_digital')) {
            $consultant = Consultant::where('user_id', auth()->id())->first();
            $schoolIds  = SchoolConsultant::where('consultant_id', $consultant?->id)
                ->where('role', 'digital')
                ->pluck('school_id');
            $query->whereIn('id', $schoolIds);
        }

        $schools = $query->get();
        return view('schools.index', compact('schools'));
    }

    public function create()
    {
        $consultants = Consultant::with('user')->get();
        $levels = Level::all();
        $digitales    = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))->with('user')->get();
    $ecas         = Consultant::whereHas('user', fn($q) => $q->role('consultor_eca'))->with('user')->get();
    $elts         = Consultant::whereHas('user', fn($q) => $q->role('consultor_elt'))->with('user')->get();
    $representantes = Consultant::whereHas('user', fn($q) => $q->role('representante_ventas'))->with('user')->get();

    return view('schools.create', compact('consultants', 'levels', 'digitales', 'ecas', 'elts', 'representantes'));
}
    

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'consultant_id'=> 'nullable|exists:consultants,id',
            'levels'       => 'required|array',
            'status'       => 'required|in:prospecto,activo,inactivo',
            // Nexus ID: MEXMP seguido de exactamente 6 dígitos, único en la tabla
            'nexus_id'     => ['nullable', 'regex:/^MEXMP\d{6}$/i', 'unique:schools,nexus_id'],
        ], [
            'nexus_id.regex'  => 'El Nexus ID debe tener el formato MEXMP######  (ej. MEXMP123456).',
            'nexus_id.unique' => 'Este Nexus ID ya está registrado en otro colegio.',
        ]);

        $school = School::create($request->except('levels'));

        $roles = [
    'digital' => $request->consultor_digital,
    'eca'     => $request->consultor_eca,
    'elt'     => $request->consultor_elt,
    'ventas'  => $request->representante_ventas,
];

foreach ($roles as $role => $consultantId) {
    if (!empty($consultantId)) {
        \App\Models\SchoolConsultant::create([
            'school_id'     => $school->id,
            'consultant_id' => $consultantId,
            'role'          => $role,
        ]);
    }
}

        // Asignar niveles y crear procesos automáticamente
        $processes = Process::orderBy('order')->get();

        foreach ($request->levels as $levelId) {
            $schoolLevel = SchoolLevel::create([
                'school_id' => $school->id,
                'level_id' => $levelId,
            ]);

            foreach ($processes as $process) {
                SchoolLevelProcess::create([
                    'school_level_id' => $schoolLevel->id,
                    'process_id' => $process->id,
                    'status' => 'pending',
                ]);
            }
        }

        // Guardar administradores MEE
        if ($request->mee_usernames) {
            foreach ($request->mee_usernames as $i => $username) {
                if (!empty($username)) {
                    MeeAdmin::create([
                        'school_id' => $school->id,
                        'username' => $username,
                        'password_plain' => $request->mee_passwords[$i] ?? '',
                    ]);
                }
            }
        }

        return redirect()->route('schools.index')
            ->with('success', 'Colegio registrado correctamente.');
    }

    public function show(School $school)
    {
        $school->load('consultant', 'levels', 'schoolLevels.level', 'schoolLevels.processes.process', 'meeAdmins');
        return view('schools.show', compact('school'));
    }

    public function edit(School $school)
{
    $consultants = Consultant::with('user')->get();
    $levels = Level::all();
    $selectedLevels = $school->levels->pluck('id')->toArray();

    $digitales      = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))->with('user')->get();
    $ecas           = Consultant::whereHas('user', fn($q) => $q->role('consultor_eca'))->with('user')->get();
    $elts           = Consultant::whereHas('user', fn($q) => $q->role('consultor_elt'))->with('user')->get();
    $representantes = Consultant::whereHas('user', fn($q) => $q->role('representante_ventas'))->with('user')->get();

    $responsables = $school->schoolConsultants->keyBy('role');

    return view('schools.edit', compact(
        'school', 'consultants', 'levels', 'selectedLevels',
        'digitales', 'ecas', 'elts', 'representantes', 'responsables'
    ));
}

    public function update(Request $request, School $school)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'status'  => 'required|in:prospecto,activo,inactivo',
            // Excluir el propio colegio del chequeo de unicidad
            'nexus_id'=> ['nullable', 'regex:/^MEXMP\d{6}$/i', "unique:schools,nexus_id,{$school->id}"],
        ], [
            'nexus_id.regex'  => 'El Nexus ID debe tener el formato MEXMP######  (ej. MEXMP123456).',
            'nexus_id.unique' => 'Este Nexus ID ya está registrado en otro colegio.',
        ]);

        // state reemplaza a city como campo canónico de ubicación geográfica
        $school->update($request->only([
            'name', 'nexus_id', 'address', 'city', 'state',
            'phone', 'email', 'status', 'notes',
        ]));

        // Actualizar niveles
        $newLevelIds = array_map('intval', $request->levels ?? []);
        $currentLevelIds = $school->schoolLevels()->pluck('level_id')->map(fn($id) => (int) $id)->toArray();

        $processes = Process::orderBy('order')->get();

        // Actualizar responsables
$school->schoolConsultants()->delete();
$roles = [
    'digital' => $request->consultor_digital,
    'eca'     => $request->consultor_eca,
    'elt'     => $request->consultor_elt,
    'ventas'  => $request->representante_ventas,
];

foreach ($roles as $role => $consultantId) {
    if (!empty($consultantId)) {
        \App\Models\SchoolConsultant::create([
            'school_id'     => $school->id,
            'consultant_id' => $consultantId,
            'role'          => $role,
        ]);
    }
}

        // Agregar niveles nuevos
        foreach ($newLevelIds as $levelId) {
            if (!in_array($levelId, $currentLevelIds)) {
                $schoolLevel = SchoolLevel::create([
                    'school_id' => $school->id,
                    'level_id' => $levelId,
                ]);
                foreach ($processes as $process) {
                    SchoolLevelProcess::create([
                        'school_level_id' => $schoolLevel->id,
                        'process_id' => $process->id,
                        'status' => 'pending',
                    ]);
                }
            }
        }

        // Eliminar niveles removidos
        foreach ($currentLevelIds as $levelId) {
            if (!in_array($levelId, $newLevelIds)) {
                $school->schoolLevels()->where('level_id', $levelId)->delete();
            }
        }

        // Actualizar administradores MEE
        $school->meeAdmins()->delete();
        if ($request->mee_usernames) {
            foreach ($request->mee_usernames as $i => $username) {
                if (!empty($username)) {
                    MeeAdmin::create([
                        'school_id' => $school->id,
                        'username' => $username,
                        'password_plain' => $request->mee_passwords[$i] ?? '',
                    ]);
                }
            }
        }

        return redirect()->route('schools.index')
            ->with('success', 'Colegio actualizado correctamente.');

    }
    public function destroy(School $school)
    {
        $school->delete();
        return redirect()->route('schools.index')
            ->with('success', 'Colegio eliminado correctamente.');
    }

    // ── Importación masiva ────────────────────────────────────────────────

    public function importarMasivo(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $request->validate(
            ['archivo' => 'required|file|mimes:xlsx,xls|max:10240'],
            [
                'archivo.required' => 'Selecciona un archivo Excel.',
                'archivo.mimes'    => 'Solo se permiten archivos .xlsx o .xls.',
                'archivo.max'      => 'El archivo no puede superar 10 MB.',
            ]
        );

        // try-catch separado: un archivo corrupto lanzaría excepción sin esto
        try {
            $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
        } catch (\Exception $e) {
            return back()->withErrors(['archivo' => 'No se pudo leer el archivo. Asegúrate de que sea un Excel válido (.xlsx).']);
        }

        $sheet            = $spreadsheet->getActiveSheet();
        $filasValidas     = [];  // solo filas que pasaron todas las validaciones
        $omitidos         = [];
        $nexusEnArchivo   = [];
        $nombresEnArchivo = [];

        // ── Fase 1: validar todas las filas sin tocar la BD ──────────────
        foreach ($sheet->getRowIterator(2) as $row) {
            $ri        = $row->getRowIndex();
            $nombre    = trim((string) $sheet->getCell("A{$ri}")->getValue());
            $nexusId   = trim((string) $sheet->getCell("B{$ri}")->getValue());
            $statusRaw = trim((string) $sheet->getCell("C{$ri}")->getValue());
            $estadoRaw = trim((string) $sheet->getCell("D{$ri}")->getValue());

            // Saltar filas completamente vacías
            if ($nombre === '' && $nexusId === '' && $statusRaw === '' && $estadoRaw === '') continue;

            // Nombre es el único campo obligatorio
            if ($nombre === '') {
                $omitidos[] = "Fila {$ri}: nombre vacío.";
                continue;
            }

            // Nexus ID: formato MEXMP + exactamente 6 dígitos
            if ($nexusId !== '' && !preg_match('/^MEXMP\d{6}$/i', $nexusId)) {
                $omitidos[] = "Fila {$ri}: Nexus ID '{$nexusId}' inválido (formato: MEXMP######).";
                continue;
            }

            // Status: normalizar acentos antes de comparar
            $statusNorm = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], mb_strtolower($statusRaw));
            if ($statusNorm === '' || $statusNorm === 'activo') {
                $status = 'activo';
            } elseif ($statusNorm === 'inactivo') {
                $status = 'inactivo';
            } else {
                $omitidos[] = "Fila {$ri}: status '{$statusRaw}' no válido (usa Activo o Inactivo).";
                continue;
            }

            // Nexus ID: duplicado dentro del archivo
            if ($nexusId !== '') {
                if (in_array($nexusId, $nexusEnArchivo)) {
                    $omitidos[] = "Fila {$ri}: Nexus ID '{$nexusId}' duplicado en el archivo.";
                    continue;
                }
                // Nexus ID: ya existe en la BD
                if (School::where('nexus_id', $nexusId)->exists()) {
                    $omitidos[] = "Fila {$ri}: Nexus ID '{$nexusId}' ya existe en el sistema.";
                    continue;
                }
                $nexusEnArchivo[] = $nexusId;
            }

            // Nombre: duplicado dentro del archivo
            $nombreNorm = mb_strtolower($nombre);
            if (in_array($nombreNorm, $nombresEnArchivo)) {
                $omitidos[] = "Fila {$ri}: '{$nombre}' aparece duplicado en el archivo.";
                continue;
            }
            // Nombre: ya existe en la BD (case-insensitive)
            if (School::whereRaw('LOWER(name) = ?', [$nombreNorm])->exists()) {
                $omitidos[] = "Fila {$ri}: '{$nombre}' ya existe en el sistema.";
                continue;
            }
            $nombresEnArchivo[] = $nombreNorm;

            // Estado: no crítico — si no se reconoce, se importa sin estado
            $state = $this->normalizarEstado($estadoRaw);
            if ($estadoRaw !== '' && $state === null) {
                $omitidos[] = "Fila {$ri}: estado '{$estadoRaw}' no reconocido (importado sin estado).";
            }

            $filasValidas[] = [
                'name'     => $nombre,
                'nexus_id' => $nexusId ?: null,
                'status'   => $status,
                'state'    => $state,
            ];
        }

        // ── Fase 2: insertar todo en una sola transacción ─────────────────
        // Si falla cualquier INSERT, se revierten todos los de esta importación
        $importados = 0;
        DB::transaction(function () use ($filasValidas, &$importados) {
            foreach ($filasValidas as $fila) {
                School::create($fila);
                $importados++;
            }
        });

        $msg = "{$importados} colegio(s) importados correctamente.";
        if (!empty($omitidos)) {
            $msg .= '  Avisos: ' . implode(' | ', $omitidos);
        }

        return redirect()->route('schools.index')->with('success', $msg);
    }

    private function normalizarEstado(string $raw): ?string
    {
        if ($raw === '') return null;

        $estados = [
            'aguascalientes'     => 'Aguascalientes',
            'baja california'    => 'Baja California',
            'baja california sur'=> 'Baja California Sur',
            'campeche'           => 'Campeche',
            'chiapas'            => 'Chiapas',
            'chihuahua'          => 'Chihuahua',
            'ciudad de mexico'   => 'Ciudad de México',
            'cdmx'               => 'Ciudad de México',
            'df'                 => 'Ciudad de México',
            'distrito federal'   => 'Ciudad de México',
            'coahuila'           => 'Coahuila',
            'colima'             => 'Colima',
            'durango'            => 'Durango',
            'guanajuato'         => 'Guanajuato',
            'guerrero'           => 'Guerrero',
            'hidalgo'            => 'Hidalgo',
            'jalisco'            => 'Jalisco',
            'estado de mexico'   => 'Estado de México',
            'mexico'             => 'Estado de México',
            'edomex'             => 'Estado de México',
            'michoacan'          => 'Michoacán',
            'michoacán'          => 'Michoacán',
            'morelos'            => 'Morelos',
            'nayarit'            => 'Nayarit',
            'nuevo leon'         => 'Nuevo León',
            'nuevo león'         => 'Nuevo León',
            'oaxaca'             => 'Oaxaca',
            'puebla'             => 'Puebla',
            'queretaro'          => 'Querétaro',
            'querétaro'          => 'Querétaro',
            'quintana roo'       => 'Quintana Roo',
            'san luis potosi'    => 'San Luis Potosí',
            'san luis potosí'    => 'San Luis Potosí',
            'sinaloa'            => 'Sinaloa',
            'sonora'             => 'Sonora',
            'tabasco'            => 'Tabasco',
            'tamaulipas'         => 'Tamaulipas',
            'tlaxcala'           => 'Tlaxcala',
            'veracruz'           => 'Veracruz',
            'yucatan'            => 'Yucatán',
            'yucatán'            => 'Yucatán',
            'zacatecas'          => 'Zacatecas',
        ];

        $key = mb_strtolower(trim($raw));
        $key = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $key);

        return $estados[$key] ?? $estados[mb_strtolower(trim($raw))] ?? null;
    }

    public function descargarPlantilla()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Colegios');

        foreach (['A' => 'Nombre del Colegio', 'B' => 'Nexus ID', 'C' => 'Status', 'D' => 'Estado'] as $col => $header) {
            $sheet->setCellValue("{$col}1", $header);
        }

        $sheet->getStyle('A1:D1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $ejemplos = [
            ['Colegio Lomas Verdes',          'NX-001', 'Activo',   'Jalisco'],
            ['Instituto Cultural del Sur',     'NX-002', 'Activo',   'Ciudad de México'],
            ['Colegio San Felipe Neri',        'NX-003', 'Inactivo', 'Nuevo León'],
            ['Centro Educativo Benito Juárez', '',       'Activo',   ''],
        ];

        foreach ($ejemplos as $ri => $fila) {
            $sheet->setCellValue('A' . ($ri + 2), $fila[0]);
            $sheet->setCellValue('B' . ($ri + 2), $fila[1]);
            $sheet->setCellValue('C' . ($ri + 2), $fila[2]);
            $sheet->setCellValue('D' . ($ri + 2), $fila[3]);
        }

        $notas = [
            '* Status válidos: Activo, Inactivo  |  Nexus ID y Estado son opcionales  |  No modifiques la fila de encabezados',
            '* Estados válidos: Aguascalientes, Baja California, Baja California Sur, Campeche, Chiapas, Chihuahua,',
            '  Ciudad de México (o CDMX), Coahuila, Colima, Durango, Guanajuato, Guerrero, Hidalgo, Jalisco,',
            '  Estado de México (o Edomex), Michoacán, Morelos, Nayarit, Nuevo León, Oaxaca, Puebla, Querétaro,',
            '  Quintana Roo, San Luis Potosí, Sinaloa, Sonora, Tabasco, Tamaulipas, Tlaxcala, Veracruz, Yucatán, Zacatecas',
        ];

        foreach ($notas as $ni => $nota) {
            $fila = 7 + $ni;
            $sheet->setCellValue("A{$fila}", $nota);
            $sheet->mergeCells("A{$fila}:D{$fila}");
            $sheet->getStyle("A{$fila}")->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
            ]);
        }

        $sheet->getColumnDimension('A')->setWidth(38);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(22);

        $tempFile = tempnam(sys_get_temp_dir(), 'plantilla_colegios_');

        // try-finally garantiza que el archivo temporal se elimine si la descarga falla
        try {
            (new Xlsx($spreadsheet))->save($tempFile);
            return response()->download($tempFile, 'plantilla-colegios.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            @unlink($tempFile);
            return back()->withErrors(['error' => 'No se pudo generar la plantilla. Intenta de nuevo.']);
        }
    }
}