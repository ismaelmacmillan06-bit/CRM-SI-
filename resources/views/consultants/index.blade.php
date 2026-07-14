@extends('layouts.app')

@section('title', 'Equipo SI')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">👥 Equipo SI</span>
        @role('admin')
        <a href="{{ route('consultants.create') }}" class="btn btn-primary">+ Nuevo Miembro</a>
        @endrole
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Zona</th>
                <th>Rol</th>
                <th>Colegios</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($consultants as $consultant)
            <tr>
                <td><strong>{{ $consultant->user->name }}</strong></td>
                <td style="font-size:13px; color:var(--text-muted)">{{ $consultant->user->email }}</td>
                <td>{{ $consultant->phone ?? '—' }}</td>
                <td>{{ $consultant->zone ?? '—' }}</td>
                <td>
                    <span class="badge badge-info">{{ $consultant->user->getRoleNames()->first() }}</span>
                </td>
                <td>{{ $consultant->schoolConsultants->count() }}</td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('consultants.show', $consultant) }}" class="btn btn-secondary btn-sm">Ver</a>
                        <a href="{{ route('consultants.edit', $consultant) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <form method="POST" action="{{ route('consultants.destroy', $consultant) }}"
                              id="form-eliminar-consultor-{{ $consultant->id }}">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                    onclick="confirmarEliminar('Eliminar miembro', '¿Deseas eliminar a {{ addslashes($consultant->user->name) }} del equipo? Esta acción no se puede deshacer.', 'form-eliminar-consultor-{{ $consultant->id }}')">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay consultores registrados.
                    <a href="{{ route('consultants.create') }}">Registra el primero</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection