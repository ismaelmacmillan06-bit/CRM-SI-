<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolLevelProcess;
use App\Models\Consultant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolLevelProcessController extends Controller
{
    public function index(School $school)
    {
        $school->load('schoolLevels.level', 'schoolLevels.processes.process');
        return view('schools.processes', compact('school'));
    }

    public function update(Request $request, School $school, SchoolLevelProcess $schoolLevelProcess)
    {
        $request->validate([
            'status'   => 'required|in:pending,in_progress,done,reopened',
            'notes'    => 'nullable|string',
            'evidence' => 'nullable|image|max:4096',
        ]);

        if ($request->status === 'done' && !$schoolLevelProcess->evidence && !$request->hasFile('evidence')) {
            return back()
                ->withErrors(['evidence_' . $schoolLevelProcess->id => 'Debes subir una evidencia antes de marcar como Completado.'])
                ->withInput();
        }

        $data = [
            'status' => $request->status,
            'notes'  => $request->notes,
        ];

        if ($request->hasFile('evidence')) {
            if ($schoolLevelProcess->evidence) {
                Storage::disk('public')->delete($schoolLevelProcess->evidence);
            }
            $data['evidence'] = $request->file('evidence')->store('process-evidence', 'public');
        }

        if ($request->status === 'done') {
            $consultant = Consultant::where('user_id', auth()->id())->first();
            $data['completed_at'] = now();
            $data['completed_by'] = $consultant?->id;
        } elseif ($request->status === 'reopened') {
            $data['completed_at'] = null;
            $data['completed_by'] = null;
        }

        $schoolLevelProcess->update($data);

        return back()->with('success', 'Proceso actualizado correctamente.');
    }
}
