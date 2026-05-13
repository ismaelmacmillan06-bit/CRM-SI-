@extends('layouts.app')

@section('title', 'Docentes — ' . $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <a href="{{ route('schools.teachers.create', $school) }}" class="btn btn-primary">+ Nuevo Docente</a>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">👨‍🏫 Docentes — {{ $school->name }}</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $teachers->count() }} registrados</span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Grado</th>
                <th>Rol</th>
                <th>Materia</th>
                <th>Usuario MEE</th>
                <th>Contraseña MEE</th>
                <th>Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teachers as $teacher)
            <tr>
                <td><strong>{{ $teacher->name }} {{ $teacher->last_name }}</strong></td>
                <td style="font-size:13px; color:var(--text-muted)">{{ $teacher->email ?? '—' }}</td>
                <td>{{ $teacher->grade ?? '—' }}</td>
                
                <td>
    @if($teacher->role == 'director_general')
        <span class="badge badge-info">Director General</span>
    @elseif($teacher->role == 'director_nivel')
        <span class="badge badge-warning">Director Nivel</span>
    @elseif($teacher->role == 'coordinador')
        <span class="badge badge-success">Coordinador</span>
    @else
        <span class="badge badge-gray">Docente</span>
    @endif
</td>
<td>
    @if($teacher->subject == 'ECA')
        <span class="badge badge-info">ECA</span>
    @elseif($teacher->subject == 'ELT')
        <span class="badge badge-success">ELT</span>
    @elseif($teacher->subject == 'ambos')
        <span class="badge badge-warning">Ambos</span>
    @else
        <span class="badge badge-gray">—</span>
    @endif
</td>


                <td>
                    @if($teacher->mee_username)
                        <span style="font-family:monospace; font-size:13px">{{ $teacher->mee_username }}</span>
                    @else
                        <span style="color:var(--text-muted)">—</span>
                    @endif
                </td>
                <td>
                    @if($teacher->mee_password)
                        <span style="font-family:monospace; font-size:13px">{{ $teacher->mee_password }}</span>
                    @else
                        <span style="color:var(--text-muted)">—</span>
                    @endif
                </td>
                <td style="font-size:13px; color:var(--text-muted)">
                    {{ $teacher->created_at->format('d/m/Y') }}
                </td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <form method="POST" action="{{ route('teachers.destroy', $teacher) }}"
                              onsubmit="return confirm('¿Eliminar este docente?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay docentes registrados para este colegio.
                    <a href="{{ route('schools.teachers.create', $school) }}">Registra el primero</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection