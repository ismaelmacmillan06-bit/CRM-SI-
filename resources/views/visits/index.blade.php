@extends('layouts.app')

@section('title', 'Visitas — ' . $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <a href="{{ route('schools.visits.create', $school) }}" class="btn btn-primary">+ Nueva Visita</a>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">📅 Visitas — {{ $school->name }}</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $visits->count() }} registradas</span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Consultor</th>
                <th>Fecha programada</th>
                <th>Fecha realizada</th>
                <th>Status</th>
                <th>Próxima visita</th>
                <th>Evidencia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($visits as $visit)
            <tr>
                <td><strong>{{ $visit->consultant->user->name ?? '—' }}</strong></td>
                <td style="font-size:13px">
                    {{ $visit->scheduled_date ? \Carbon\Carbon::parse($visit->scheduled_date)->format('d/m/Y') : '—' }}
                </td>
                <td style="font-size:13px">
                    {{ $visit->visit_date ? \Carbon\Carbon::parse($visit->visit_date)->format('d/m/Y') : '—' }}
                </td>
                <td>
                    @if($visit->status === 'terminada')
                        <span class="badge badge-success">✅ Terminada</span>
                    @elseif($visit->status === 'en_curso')
                        <span class="badge badge-warning">🔄 En curso</span>
                    @else
                        <span class="badge badge-gray">⏳ Pendiente</span>
                    @endif
                </td>
                <td style="font-size:13px; color:var(--text-muted)">
                    {{ $visit->next_visit_date ? \Carbon\Carbon::parse($visit->next_visit_date)->format('d/m/Y') : '—' }}
                </td>
                <td>
                    @if($visit->evidence)
                        <a href="{{ asset('storage/' . $visit->evidence) }}" target="_blank">
                            <img src="{{ asset('storage/' . $visit->evidence) }}"
                                 style="width:40px; height:40px; object-fit:cover; border-radius:6px;">
                        </a>
                    @else
                        <span style="color:var(--text-muted)">—</span>
                    @endif
                </td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('visits.edit', $visit) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <form method="POST" action="{{ route('visits.destroy', $visit) }}"
                              onsubmit="return confirm('¿Eliminar esta visita?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay visitas registradas para este colegio.
                    <a href="{{ route('schools.visits.create', $school) }}">Registra la primera</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection