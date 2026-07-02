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
            'name' => 'required|string|max:255',
            'consultant_id' => 'nullable|exists:consultants,id',
            'levels' => 'required|array',
            'status' => 'required|in:prospecto,activo,inactivo',
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
            'name'   => 'required|string|max:255',
            'status' => 'required|in:prospecto,activo,inactivo',
        ]);

        $school->update($request->only([
            'name', 'nexus_id', 'address', 'city',
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
            ['archivo.required' => 'Selecciona un archivo Excel.',
             'archivo.mimes'    => 'Solo se permiten archivos .xlsx o .xls.',
             'archivo.max'      => 'El archivo no puede superar 10 MB.']
        );

        $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
        $sheet       = $spreadsheet->getActiveSheet();

        $importados     = 0;
        $omitidos       = [];
        $nexusEnArchivo = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $ri        = $row->getRowIndex();
            $nombre    = trim((string) $sheet->getCell("A{$ri}")->getValue());
            $nexusId   = trim((string) $sheet->getCell("B{$ri}")->getValue());
            $statusRaw = trim((string) $sheet->getCell("C{$ri}")->getValue());

            if ($nombre === '' && $nexusId === '' && $statusRaw === '') continue;

            if ($nombre === '') {
                $omitidos[] = "Fila {$ri}: nombre vacío.";
                continue;
            }

            $statusKey = mb_strtolower($statusRaw);
            $statusKey = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $statusKey);

            if ($statusKey === '' || $statusKey === 'activo') {
                $status = 'activo';
            } elseif ($statusKey === 'inactivo') {
                $status = 'inactivo';
            } else {
                $omitidos[] = "Fila {$ri}: status '{$statusRaw}' no válido (usa Activo o Inactivo).";
                continue;
            }

            if ($nexusId !== '') {
                if (in_array($nexusId, $nexusEnArchivo)) {
                    $omitidos[] = "Fila {$ri}: Nexus ID '{$nexusId}' duplicado en el archivo.";
                    continue;
                }
                if (School::where('nexus_id', $nexusId)->exists()) {
                    $omitidos[] = "Fila {$ri}: Nexus ID '{$nexusId}' ya existe en el sistema.";
                    continue;
                }
                $nexusEnArchivo[] = $nexusId;
            }

            School::create([
                'name'     => $nombre,
                'nexus_id' => $nexusId ?: null,
                'status'   => $status,
            ]);

            $importados++;
        }

        $msg = "{$importados} colegio(s) importados correctamente.";
        if (!empty($omitidos)) {
            $msg .= ' Filas omitidas: ' . implode(' | ', $omitidos);
        }

        return redirect()->route('schools.index')->with('success', $msg);
    }

    public function descargarPlantilla()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Colegios');

        foreach (['A' => 'Nombre del Colegio', 'B' => 'Nexus ID', 'C' => 'Status'] as $col => $header) {
            $sheet->setCellValue("{$col}1", $header);
        }

        $sheet->getStyle('A1:C1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $ejemplos = [
            ['Colegio Lomas Verdes',         'NX-001', 'Activo'],
            ['Instituto Cultural del Sur',    'NX-002', 'Activo'],
            ['Colegio San Felipe Neri',       'NX-003', 'Inactivo'],
            ['Centro Educativo Benito Juárez','',       'Activo'],
        ];

        foreach ($ejemplos as $ri => $fila) {
            $sheet->setCellValue('A' . ($ri + 2), $fila[0]);
            $sheet->setCellValue('B' . ($ri + 2), $fila[1]);
            $sheet->setCellValue('C' . ($ri + 2), $fila[2]);
        }

        $sheet->setCellValue('A7', '* Status válidos: Activo, Inactivo  |  Nexus ID es opcional  |  No modifiques la fila de encabezados');
        $sheet->mergeCells('A7:C7');
        $sheet->getStyle('A7')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
        ]);

        $sheet->getColumnDimension('A')->setWidth(38);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(14);

        $tempFile = tempnam(sys_get_temp_dir(), 'plantilla_colegios_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, 'plantilla-colegios.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}