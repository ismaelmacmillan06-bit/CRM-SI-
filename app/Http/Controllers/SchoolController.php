<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Consultant;
use App\Models\Level;
use App\Models\Process;
use App\Models\SchoolLevel;
use App\Models\SchoolLevelProcess;
use Illuminate\Http\Request;
use App\Models\MeeAdmin;

class SchoolController extends Controller
{
    public function index()
{
    $schools = School::with('levels', 'schoolConsultants.consultant.user')->get();
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
            'name' => 'required|string|max:255',
            'consultant_id' => 'required|exists:consultants,id',
            'status' => 'required|in:prospecto,activo,inactivo',
        ]);

        $school->update($request->except('levels', 'mee_usernames', 'mee_passwords'));

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
}