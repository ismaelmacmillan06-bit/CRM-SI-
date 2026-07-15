@extends('layouts.app')

@section('title', 'Tickets — ' . $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <a href="{{ route('schools.tickets.create', $school) }}" class="btn btn-primary">+ Nuevo Ticket</a>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">🎫 Tickets — {{ $school->name }}</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $tickets->count() }} registrados</span>
    </div>
    <table class="table">
        <thead>
    <tr>
        <th>Título</th>
        <th>Medio</th>
        <th>Prioridad</th>
        <th>Status</th>
        <th>Consultor</th>
        <th>Evidencia</th>
        <th>Creado</th>
        <th>Resuelto</th>
        <th>Acciones</th>
    </tr>
</thead>

        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td>
                    <strong>{{ $ticket->title }}</strong>
                    <br>
                    <small style="color:var(--text-muted)">{{ Str::limit($ticket->description, 60) }}</small>
                </td>

                <td>
                    @if($ticket->medium == 'salesforce')
                        <span class="badge badge-info">Sales Force</span>
                    @elseif($ticket->medium == 'whaticket')
                        <span class="badge badge-warning">Whaticket</span>
                    @else
                        <span class="badge badge-success">WhatsApp</span>
                    @endif
                </td>


                <td>
                    @if($ticket->priority === 'high')
                        <span class="badge badge-danger">🔴 Alta</span>
                    @elseif($ticket->priority === 'medium')
                        <span class="badge badge-warning">🟡 Media</span>
                    @else
                        <span class="badge badge-gray">🟢 Baja</span>
                    @endif
                </td>
                <td>
                    @if($ticket->status === 'open')
                        <span class="badge badge-danger">Abierto</span>
                    @elseif($ticket->status === 'in_progress')
                        <span class="badge badge-warning">En progreso</span>
                    @else
                        <span class="badge badge-success">Cerrado</span>
                    @endif
                </td>
                <td style="font-size:13px">{{ $ticket->consultant->user->name ?? '—' }}</td>
                <td>
                    @if($ticket->evidence)
                        <a href="{{ Storage::url($ticket->evidence) }}" target="_blank" title="Ver evidencia">
                            <img src="{{ Storage::url($ticket->evidence) }}" alt="evidencia"
                                 style="height:40px; width:40px; object-fit:cover; border-radius:4px; border:1px solid var(--border)">
                        </a>
                    @else
                        <span style="color:var(--text-muted); font-size:12px">—</span>
                    @endif
                </td>
                <td style="font-size:13px; color:var(--text-muted)">
                    {{ $ticket->created_at->format('d/m/Y') }}
                </td>
                <td style="font-size:13px; color:var(--text-muted)">
                    {{ $ticket->resolved_at ? \Carbon\Carbon::parse($ticket->resolved_at)->format('d/m/Y') : '—' }}
                </td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <form method="POST" action="{{ route('tickets.destroy', $ticket) }}"
                              id="form-ticket-{{ $ticket->id }}"
                              onsubmit="return confirm('¿Eliminar este ticket?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay tickets registrados para este colegio.
                    <a href="{{ route('schools.tickets.create', $school) }}">Registra el primero</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection