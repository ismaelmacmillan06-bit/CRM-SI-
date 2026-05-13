@extends('layouts.app')

@section('title', 'Colegios')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">🏫 Colegios registrados</span>
        <a href="{{ route('schools.create') }}" class="btn btn-primary">+ Nuevo Colegio</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Colegio</th>
                <th>Nexus ID</th>
                <th>Consultor</th>
                <th>Niveles</th>
                <th>Status</th>
                <th>Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schools as $school)
            <tr>
                <td>
                    <strong>{{ $school->name }}</strong>
                    @if($school->city)
                        <br><small style="color:var(--text-muted)">{{ $school->city }}</small>
                    @endif
                </td>
                <td>{{ $school->nexus_id ?? '—' }}</td>
                <td>{{ $school->consultant->user->name ?? '—' }}</td>
                <td>
                    @foreach($school->levels as $level)
                        <span class="badge badge-info">{{ $level->name }}</span>
                    @endforeach
                </td>
                <td>
                    @if($school->status === 'activo')
                        <span class="badge badge-success">Activo</span>
                    @elseif($school->status === 'prospecto')
                        <span class="badge badge-warning">Prospecto</span>
                    @else
                        <span class="badge badge-gray">Inactivo</span>
                    @endif
                </td>
                <td style="color:var(--text-muted); font-size:13px">
                    {{ $school->created_at->format('d/m/Y') }}
                </td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">Ver</a>
                        <a href="{{ route('schools.edit', $school) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <form method="POST" action="{{ route('schools.destroy', $school) }}"
                              onsubmit="return confirm('¿Eliminar este colegio?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay colegios registrados aún.
                    <a href="{{ route('schools.create') }}">Registra el primero</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection