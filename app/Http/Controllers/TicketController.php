<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\School;
use App\Models\Consultant;
use Illuminate\Http\Request;

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
        ]);

        $school->tickets()->create($request->all());

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
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'priority'    => 'required|in:low,medium,high',
            'status'      => 'required|in:open,in_progress,closed',
        ]);

        $data = $request->all();

        if ($request->status === 'closed' && $ticket->status !== 'closed') {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);

        return redirect()->route('schools.tickets.index', $ticket->school)
                         ->with('success', 'Ticket actualizado correctamente.');
    }

    public function destroy(Ticket $ticket)
    {
        $school = $ticket->school;
        $ticket->delete();
        return redirect()->route('schools.tickets.index', $school)
                         ->with('success', 'Ticket eliminado correctamente.');
    }
}