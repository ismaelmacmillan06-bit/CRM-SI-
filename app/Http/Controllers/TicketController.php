<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\School;
use App\Models\Consultant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index(School $school)
    {
        $tickets = $school->tickets()->with('consultant.user')->latest()->get();
        return view('tickets.index', compact('school', 'tickets'));
    }

    public function create(School $school)
    {
        $consultants = Consultant::with('user')->get();
        return view('tickets.create', compact('school', 'consultants'));
    }

    public function store(Request $request, School $school)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'priority'      => 'required|in:low,medium,high',
            'consultant_id' => 'required|exists:consultants,id',
            'medium'        => 'required|in:salesforce,whaticket,whatsapp',
            'evidence'      => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'evidence.mimes' => 'Solo se permiten imágenes JPG, PNG o WEBP (máx. 5 MB).',
        ]);

        $data = $request->only(['title', 'description', 'priority', 'consultant_id', 'medium']);

        if ($request->hasFile('evidence')) {
            $data['evidence'] = $request->file('evidence')->store('tickets', 'public');
        }

        $ticket = $school->tickets()->create($data);

        $prioridades = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'];
        $prio = $prioridades[$request->priority] ?? $request->priority;
        ActivityLog::log('ticket', "Ticket creado: \"{$request->title}\" (prioridad: $prio)", $school->id, '🎫');

        return redirect()->route('schools.tickets.index', $school)
                         ->with('success', 'Ticket registrado correctamente.');
    }

    public function edit(Ticket $ticket)
    {
        $consultants = Consultant::with('user')->get();
        return view('tickets.edit', compact('ticket', 'consultants'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'priority'      => 'required|in:low,medium,high',
            'status'        => 'required|in:open,in_progress,closed',
            'consultant_id' => 'nullable|exists:consultants,id',
            'medium'        => 'required|in:salesforce,whaticket,whatsapp',
            'evidence'      => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'evidence.mimes' => 'Solo se permiten imágenes JPG, PNG o WEBP (máx. 5 MB).',
        ]);

        $data = $request->only(['title', 'description', 'priority', 'status', 'consultant_id', 'medium']);

        if ($request->hasFile('evidence')) {
            if ($ticket->evidence) {
                Storage::disk('public')->delete($ticket->evidence);
            }
            $data['evidence'] = $request->file('evidence')->store('tickets', 'public');
        } elseif ($request->boolean('remove_evidence')) {
            if ($ticket->evidence) {
                Storage::disk('public')->delete($ticket->evidence);
            }
            $data['evidence'] = null;
        }

        if ($request->status === 'closed' && $ticket->status !== 'closed') {
            $data['resolved_at'] = now();
        } elseif ($request->status !== 'closed' && $ticket->status === 'closed') {
            $data['resolved_at'] = null;
        }

        $oldStatus = $ticket->status;
        $ticket->update($data);

        if ($oldStatus !== $request->status) {
            $statusLabel = ['open' => 'Abierto', 'in_progress' => 'En proceso', 'closed' => 'Cerrado'];
            $de  = $statusLabel[$oldStatus]        ?? $oldStatus;
            $a   = $statusLabel[$request->status]  ?? $request->status;
            $ico = $request->status === 'closed' ? '✅' : '🔄';
            ActivityLog::log('ticket', "Ticket \"{$ticket->title}\" cambió de $de → $a", $ticket->school_id, $ico);
        }

        return redirect()->route('schools.tickets.index', $ticket->school)
                         ->with('success', 'Ticket actualizado correctamente.');
    }

    public function destroy(Ticket $ticket)
    {
        $school = $ticket->school;
        $titulo = $ticket->title;
        if ($ticket->evidence) {
            Storage::disk('public')->delete($ticket->evidence);
        }
        $ticket->delete();
        ActivityLog::log('ticket', "Ticket \"{$titulo}\" eliminado", $school->id, '🗑️');
        return redirect()->route('schools.tickets.index', $school)
                         ->with('success', 'Ticket eliminado correctamente.');
    }
}