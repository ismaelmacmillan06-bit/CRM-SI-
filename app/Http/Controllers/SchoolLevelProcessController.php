<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolLevelProcess;
use App\Models\Consultant;
use Illuminate\Http\Request;

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
            'status' => 'required|in:pending,in_progress,done',
            'notes'  => 'nullable|string',
        ]);

        $data = [
            'status' => $request->status,
            'notes'  => $request->notes,
        ];

        if ($request->status === 'done') {
            $consultant = Consultant::where('user_id', auth()->id())->first();
            $data['completed_at'] = now();
            $data['completed_by'] = $consultant?->id;
        }

        $schoolLevelProcess->update($data);

        return back()->with('success', 'Proceso actualizado correctamente.');
    }
}