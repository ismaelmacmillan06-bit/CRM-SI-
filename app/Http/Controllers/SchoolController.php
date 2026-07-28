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
use App\Models\ActivityLog;
use App\Models\MeeAdmin;
use App\Models\SchoolServiceType;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        $query = School::with('levels', 'schoolConsultants.consultant.user');

        if (auth()->user()->hasRole('consultor_digital')) {
            $consultant = Consultant::where('user_id', auth()->id())->first();
            $schoolIds  = SchoolConsultant::where('consultant_id', $consultant?->id)
                ->where('role', 'digital')
                ->pluck('school_id');
            $query->whereIn('id', $schoolIds);
        }

        // Búsqueda por nombre, Nexus ID o estado
        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('nexus_id', 'like', "%{$buscar}%")
                  ->orWhere('state', 'like', "%{$buscar}%")
                  ->orWhere('city', 'like', "%{$buscar}%");
            });
        }

        $schools = $query->orderBy('name')->paginate(20)->withQueryString();
        return view('schools.index', compact('schools', 'buscar'));
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

        ActivityLog::log('colegio', "Colegio \"{$school->name}\" registrado", $school->id, '🏫');

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

    $serviceTypes    = SchoolServiceType::active()->get();
    $selectedServices = $school->services->pluck('id')->toArray();

    return view('schools.edit', compact(
        'school', 'consultants', 'levels', 'selectedLevels',
        'digitales', 'ecas', 'elts', 'representantes', 'responsables',
        'serviceTypes', 'selectedServices'
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
        $school->update(array_merge(
            $request->only(['name', 'nexus_id', 'address', 'city', 'state', 'phone', 'email', 'status', 'notes']),
            ['custom_passwords' => $request->boolean('custom_passwords')]
        ));

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

        // Sincronizar servicios contables
        $school->services()->sync($request->input('services', []));

        ActivityLog::log('colegio', "Colegio \"{$school->name}\" actualizado", $school->id, '✏️');

        return redirect()->route('schools.index')
            ->with('success', 'Colegio actualizado correctamente.');

    }
    public function destroy(School $school)
    {
        $nombre = $school->name;
        $school->delete();
        ActivityLog::log('colegio', "Colegio \"{$nombre}\" eliminado", null, '🗑️');
        return redirect()->route('schools.index')
            ->with('success', 'Colegio eliminado correctamente.');
    }

    public function destroyAll()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        School::query()->delete();
        ActivityLog::log('colegio', 'Todos los colegios fueron eliminados', null, '🗑️');
        return redirect()->route('schools.index')
            ->with('success', 'Todos los colegios han sido eliminados.');
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

        // Pre-cargar consultores digitales (nombre normalizado → consultant)
        // Se normalizan acentos para que "Jose" y "José" coincidan
        $consultoresMap = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))
            ->with('user')
            ->get()
            ->keyBy(fn($c) => $this->normalizarTexto($c->user->name));

        // ── Fase 1: validar todas las filas sin tocar la BD ──────────────
        foreach ($sheet->getRowIterator(2) as $row) {
            $ri             = $row->getRowIndex();
            $nombre         = trim((string) $sheet->getCell("A{$ri}")->getValue());
            $nexusId        = trim((string) $sheet->getCell("B{$ri}")->getValue());
            $statusRaw      = trim((string) $sheet->getCell("C{$ri}")->getValue());
            $estadoRaw      = trim((string) $sheet->getCell("D{$ri}")->getValue());
            $consultorNombre= trim((string) $sheet->getCell("E{$ri}")->getValue());

            // Saltar filas completamente vacías
            if ($nombre === '' && $nexusId === '' && $statusRaw === '' && $estadoRaw === '' && $consultorNombre === '') continue;

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
                $omitidos[] = "Fila {$ri}: estado '{$estadoRaw}' no reconocido — usa el nombre oficial del estado (ej: Estado de México, Jalisco, Ciudad de México). Importado sin estado.";
            }

            // Consultor Digital: si se indicó, debe existir (búsqueda sin acentos)
            $consultorId = null;
            if ($consultorNombre !== '') {
                $consultorKey = $this->normalizarTexto($consultorNombre);
                if (!isset($consultoresMap[$consultorKey])) {
                    $omitidos[] = "Fila {$ri}: consultor digital '{$consultorNombre}' no encontrado. Verifica el nombre exacto.";
                    continue;
                }
                $consultorId = $consultoresMap[$consultorKey]->id;
            }

            $filasValidas[] = [
                'name'        => $nombre,
                'nexus_id'    => $nexusId ?: null,
                'status'      => $status,
                'state'       => $state,
                'consultor_id'=> $consultorId,
            ];
        }

        // ── Fase 2: insertar todo en una sola transacción ─────────────────
        $importados = 0;
        DB::transaction(function () use ($filasValidas, &$importados) {
            foreach ($filasValidas as $fila) {
                $consultorId = $fila['consultor_id'];
                unset($fila['consultor_id']);

                $school = School::create($fila);

                if ($consultorId) {
                    \App\Models\SchoolConsultant::create([
                        'school_id'     => $school->id,
                        'consultant_id' => $consultorId,
                        'role'          => 'digital',
                    ]);
                }
                $importados++;
            }
        });

        $msg = "{$importados} colegio(s) importados correctamente.";
        if (!empty($omitidos)) {
            $msg .= '  Avisos: ' . implode(' | ', $omitidos);
        }

        return redirect()->route('schools.index')->with('success', $msg);
    }

    private function normalizarTexto(string $s): string
    {
        return mb_strtolower(
            str_replace(
                ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ'],
                ['a','e','i','o','u','u','n','a','e','i','o','u','u','n'],
                trim($s)
            )
        );
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

        foreach ([
            'A' => 'Nombre del Colegio',
            'B' => 'Nexus ID',
            'C' => 'Status',
            'D' => 'Estado',
            'E' => 'Consultor Digital',
        ] as $col => $header) {
            $sheet->setCellValue("{$col}1", $header);
        }

        $sheet->getStyle('A1:E1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $ejemplos = [
            ['Colegio Lomas Verdes',          'MEXMP000001', 'Activo',   'Jalisco',          ''],
            ['Instituto Cultural del Sur',     'MEXMP000002', 'Activo',   'Ciudad de México', ''],
            ['Colegio San Felipe Neri',        'MEXMP000003', 'Inactivo', 'Nuevo León',       ''],
            ['Centro Educativo Benito Juárez', '',            'Activo',   '',                 ''],
        ];

        foreach ($ejemplos as $ri => $fila) {
            foreach ($fila as $ci => $val) {
                $sheet->setCellValue(chr(65 + $ci) . ($ri + 2), $val);
            }
        }

        $notas = [
            '* Consultor Digital: escribe el nombre EXACTO del consultor (ver hoja "Consultores"). Es opcional.',
            '* Status válidos: Activo, Inactivo  |  Nexus ID y Estado son opcionales',
            '* Estados válidos: Aguascalientes, Baja California, Baja California Sur, Campeche, Chiapas, Chihuahua,',
            '  Ciudad de México (o CDMX), Coahuila, Colima, Durango, Guanajuato, Guerrero, Hidalgo, Jalisco,',
            '  Estado de México (o Edomex), Michoacán, Morelos, Nayarit, Nuevo León, Oaxaca, Puebla, Querétaro,',
            '  Quintana Roo, San Luis Potosí, Sinaloa, Sonora, Tabasco, Tamaulipas, Tlaxcala, Veracruz, Yucatán, Zacatecas',
        ];

        foreach ($notas as $ni => $nota) {
            $fila = 7 + $ni;
            $sheet->setCellValue("A{$fila}", $nota);
            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->getStyle("A{$fila}")->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
            ]);
        }

        $sheet->getColumnDimension('A')->setWidth(38);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(30);

        // ── Hoja auxiliar: Consultores disponibles ────────────────────────
        $wsConsultores = $spreadsheet->createSheet(1);
        $wsConsultores->setTitle('Consultores');

        $wsConsultores->setCellValue('A1', 'Consultores Digitales disponibles');
        $wsConsultores->mergeCells('A1:C1');
        $wsConsultores->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $wsConsultores->getRowDimension(1)->setRowHeight(22);

        foreach (['A' => 'Nombre (usar tal cual)', 'B' => 'Email', 'C' => 'Rol'] as $col => $h) {
            $wsConsultores->setCellValue("{$col}2", $h);
        }
        $wsConsultores->getStyle('A2:C2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFEFEF']],
        ]);

        $consultoresDigitales = Consultant::whereHas('user', fn($q) => $q->role('consultor_digital'))
            ->with('user')
            ->orderBy('id')
            ->get();

        foreach ($consultoresDigitales as $i => $c) {
            $r = $i + 3;
            $wsConsultores->setCellValue("A{$r}", $c->user->name);
            $wsConsultores->setCellValue("B{$r}", $c->user->email);
            $wsConsultores->setCellValue("C{$r}", 'Consultor Digital');
            if ($i % 2) {
                $wsConsultores->getStyle("A{$r}:C{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9F9F9']],
                ]);
            }
        }

        $wsConsultores->getColumnDimension('A')->setWidth(32);
        $wsConsultores->getColumnDimension('B')->setWidth(34);
        $wsConsultores->getColumnDimension('C')->setWidth(22);

        $spreadsheet->setActiveSheetIndex(0);

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