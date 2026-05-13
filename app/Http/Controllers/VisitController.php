<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\School;
use App\Models\Consultant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VisitController extends Controller
{
    public function index(School $school)
    {
        $visits = $school->visits()->with('consultant.user')->latest()->get();
        return view('visits.index', compact('school', 'visits'));
    }

    public function create(School $school)
    {
        $consultants = Consultant::with('user')->get();
        return view('visits.create', compact('school', 'consultants'));
    }

    public function store(Request $request, School $school)
    {
        $request->validate([
            'consultant_id'  => 'required|exists:consultants,id',
            'scheduled_date' => 'required|date',
            'visit_date'     => 'nullable|date',
            'status'         => 'required|in:pendiente,en_curso,terminada',
            'notes'          => 'nullable|string',
            'summary'        => 'nullable|string',
            'next_visit_date'=> 'nullable|date',
            'evidence'       => 'nullable|image|max:2048',
        ]);

        $data = $request->except('evidence');

        if ($request->hasFile('evidence')) {
            $data['evidence'] = $request->file('evidence')->store('evidences', 'public');
        }

        $school->visits()->create($data);

        return redirect()->route('schools.visits.index', $school)
                         ->with('success', 'Visita registrada correctamente.');
    }

    public function edit(Visit $visit)
    {
        $consultants = Consultant::with('user')->get();
        return view('visits.edit', compact('visit', 'consultants'));
    }

    public function update(Request $request, Visit $visit)
    {
        $request->validate([
            'consultant_id'  => 'required|exists:consultants,id',
            'scheduled_date' => 'required|date',
            'visit_date'     => 'nullable|date',
            'status'         => 'required|in:pendiente,en_curso,terminada',
            'notes'          => 'nullable|string',
            'summary'        => 'nullable|string',
            'next_visit_date'=> 'nullable|date',
            'evidence'       => 'nullable|image|max:2048',
        ]);

        $data = $request->except('evidence');

        if ($request->hasFile('evidence')) {
            if ($visit->evidence) {
                Storage::disk('public')->delete($visit->evidence);
            }
            $data['evidence'] = $request->file('evidence')->store('evidences', 'public');
        }

        $visit->update($data);

        return redirect()->route('schools.visits.index', $visit->school)
                         ->with('success', 'Visita actualizada correctamente.');
    }

    public function destroy(Visit $visit)
    {
        $school = $visit->school;
        if ($visit->evidence) {
            Storage::disk('public')->delete($visit->evidence);
        }
        $visit->delete();
        return redirect()->route('schools.visits.index', $school)
                         ->with('success', 'Visita eliminada correctamente.');
    }
}