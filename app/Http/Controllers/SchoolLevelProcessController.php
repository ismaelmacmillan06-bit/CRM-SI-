<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\SchoolLevelProcess;
use App\Models\Consultant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SchoolLevelProcessController extends Controller
{
    public function index(School $school)
    {
        $school->load('schoolLevels.level', 'schoolLevels.processes.process');
        return view('schools.processes', compact('school'));
    }

    public function update(Request $request, School $school, SchoolLevelProcess $schoolLevelProcess)
    {
        // Validador manual para poder re-keying el error de 'evidence' al proceso exacto
        // y mostrarlo en la fila correcta (no en una fila genérica sin contexto)
        $validator = Validator::make($request->all(), [
            'status'   => 'required|in:pending,in_progress,done,reopened',
            'notes'    => 'nullable|string',
            // 10 MB; HEIC no es soportado por PHP natively, se informa en el mensaje
            'evidence' => 'nullable|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'evidence.mimes' => 'Solo se aceptan JPG, PNG o WebP. Los archivos HEIC (iPhone) deben convertirse antes de subirse.',
            'evidence.max'   => 'La imagen no puede superar 10 MB.',
        ]);

        if ($validator->fails()) {
            // Re-keying: mueve el error de 'evidence' → 'evidence_{id}' para que
            // aparezca exactamente en la fila del proceso que falló
            $errors = collect($validator->errors()->toArray())
                ->mapWithKeys(fn($msgs, $field) =>
                    [$field === 'evidence' ? 'evidence_' . $schoolLevelProcess->id : $field => $msgs]
                )->all();

            return back()->withErrors($errors)->withInput();
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

        $oldStatus = $schoolLevelProcess->status;
        $schoolLevelProcess->update($data);
        $schoolLevelProcess->load('process');

        // Log del cambio de estado
        $statusLabels = [
            'pending'     => 'Pendiente',
            'in_progress' => 'En proceso',
            'done'        => 'Completado',
            'reopened'    => 'Reabierto',
        ];
        $processName = $schoolLevelProcess->process->name ?? 'Proceso';
        $de  = $statusLabels[$oldStatus]           ?? $oldStatus;
        $a   = $statusLabels[$request->status]     ?? $request->status;
        $ico = $request->status === 'done' ? '✅' : ($request->status === 'in_progress' ? '🔄' : '↩️');

        ActivityLog::log('proceso', "\"$processName\" cambió de $de → $a", $school->id, $ico);

        // Log especial si llega al 100%
        $school->load('schoolLevels.processes');
        $total = 0; $done = 0;
        foreach ($school->schoolLevels as $sl) {
            $total += $sl->processes->count();
            $done  += $sl->processes->where('status', 'done')->count();
        }
        if ($total > 0 && $done === $total) {
            ActivityLog::log('arranque', '100% de acciones de arranque completadas', $school->id, '🎉');
        }

        return back()->with('success', 'Proceso actualizado correctamente.');
    }

    public function destroyEvidence(School $school, SchoolLevelProcess $schoolLevelProcess)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'consultor_digital']), 403);

        if ($schoolLevelProcess->evidence) {
            Storage::disk('public')->delete($schoolLevelProcess->evidence);
            $schoolLevelProcess->update(['evidence' => null]);
            ActivityLog::log('proceso', 'Evidencia eliminada del proceso "' . ($schoolLevelProcess->process->name ?? 'Proceso') . '"', $school->id, '🗑️');
        }

        return back()->with('success', 'Evidencia eliminada.');
    }
}
