<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Visit;
use App\Models\School;
use App\Models\Consultant;
use App\Traits\BloqueaColegioInactivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VisitController extends Controller
{
    use BloqueaColegioInactivo;

    public function index(School $school)
    {
        $visits = $school->visits()->with('consultant.user')->latest()->get();
        return view('visits.index', compact('school', 'visits'));
    }

    public function create(School $school)
    {
        if ($redirect = $this->bloqueadoPorInactivo($school)) {
            return $redirect;
        }

        $consultants = Consultant::with('user')->get();
        return view('visits.create', compact('school', 'consultants'));
    }

    public function store(Request $request, School $school)
    {
        if ($redirect = $this->bloqueadoPorInactivo($school)) {
            return $redirect;
        }

        $request->validate([
            'consultant_id'  => 'required|exists:consultants,id',
            'scheduled_date' => 'required|date',
            'visit_date'     => ['nullable', 'date', 'required_if:status,en_curso,terminada'],
            'status'         => 'required|in:pendiente,en_curso,terminada',
            'notes'          => 'nullable|string',
            'summary'        => 'nullable|string',
            'motivo'         => 'nullable|string|max:255',
            'next_visit_date'=> 'nullable|date',
            'evidence'       => 'nullable|image|max:2048',
            'attendees'      => 'nullable|array',
            'attendees.*'    => 'exists:consultants,id',
        ], [
            'visit_date.required_if' => 'La fecha realizada es obligatoria cuando la visita ya está en curso o terminada.',
        ]);

        $data = $request->except(['evidence', 'attendees']);

        if ($request->hasFile('evidence')) {
            $data['evidence'] = $request->file('evidence')->store('evidences', 'public');
        }

        $visit = $school->visits()->create($data);
        $visit->attendees()->sync($request->input('attendees', []));

        $fecha = \Carbon\Carbon::parse($request->scheduled_date)->format('d/m/Y');
        ActivityLog::log('visita', "Visita agendada para el $fecha (estado: {$request->status})", $school->id, '📅');

        return redirect()->route('schools.visits.index', $school)
                         ->with('success', 'Visita registrada correctamente.');
    }

    public function edit(Visit $visit)
    {
        $consultants = Consultant::with('user')->get();
        $visit->load('attendees');
        return view('visits.edit', compact('visit', 'consultants'));
    }

    public function update(Request $request, Visit $visit)
    {
        $request->validate([
            'consultant_id'  => 'required|exists:consultants,id',
            'scheduled_date' => 'required|date',
            'visit_date'     => ['nullable', 'date', 'required_if:status,en_curso,terminada'],
            'status'         => 'required|in:pendiente,en_curso,terminada',
            'notes'          => 'nullable|string',
            'summary'        => 'nullable|string',
            'motivo'         => 'nullable|string|max:255',
            'next_visit_date'=> 'nullable|date',
            'evidence'       => 'nullable|image|max:2048',
            'attendees'      => 'nullable|array',
            'attendees.*'    => 'exists:consultants,id',
        ], [
            'visit_date.required_if' => 'La fecha realizada es obligatoria cuando la visita ya está en curso o terminada.',
        ]);

        $data = $request->except(['evidence', 'attendees']);

        if ($request->hasFile('evidence')) {
            if ($visit->evidence) {
                Storage::disk('public')->delete($visit->evidence);
            }
            $data['evidence'] = $request->file('evidence')->store('evidences', 'public');
        }

        $oldStatus = $visit->status;
        $visit->update($data);
        $visit->attendees()->sync($request->input('attendees', []));

        if ($oldStatus !== $request->status) {
            $statusLabel = ['pendiente' => 'Pendiente', 'en_curso' => 'En curso', 'terminada' => 'Terminada'];
            $de = $statusLabel[$oldStatus]        ?? $oldStatus;
            $a  = $statusLabel[$request->status]  ?? $request->status;
            $ico = $request->status === 'terminada' ? '✅' : '🔄';
            ActivityLog::log('visita', "Visita cambió de $de → $a", $visit->school_id, $ico);
        }

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