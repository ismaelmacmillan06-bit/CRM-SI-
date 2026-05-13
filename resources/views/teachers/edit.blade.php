@extends('layouts.app')

@section('title', 'Editar Docente')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar docente</span>
            <a href="{{ route('schools.teachers.index', $teacher->school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('teachers.update', $teacher) }}">
                @csrf @method('PUT')

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $teacher->name) }}" required>
                        @error('name')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellidos *</label>
                        <input type="text" name="last_name" class="form-control"
                               value="{{ old('last_name', $teacher->last_name) }}" required>
                        @error('last_name')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', $teacher->email) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grado</label>
                        <input type="text" name="grade" class="form-control"
                               value="{{ old('grade', $teacher->grade) }}"
                               placeholder="Ej: 1°, 2°A, Preescolar">
                    </div>
                </div>


                <div class="grid-2">
    <div class="form-group">
        <label class="form-label">Rol *</label>
        <select name="role" class="form-control" required>
            <option value="docente"          {{ old('role', $teacher->role) == 'docente'          ? 'selected' : '' }}>Docente</option>
            <option value="director_general" {{ old('role', $teacher->role) == 'director_general' ? 'selected' : '' }}>Director General</option>
            <option value="director_nivel"   {{ old('role', $teacher->role) == 'director_nivel'   ? 'selected' : '' }}>Director de Nivel</option>
            <option value="coordinador"      {{ old('role', $teacher->role) == 'coordinador'      ? 'selected' : '' }}>Coordinador</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Materia *</label>
        <select name="subject" class="form-control" required>
            <option value="ninguno" {{ old('subject', $teacher->subject) == 'ninguno' ? 'selected' : '' }}>Sin materia</option>
            <option value="ECA"     {{ old('subject', $teacher->subject) == 'ECA'     ? 'selected' : '' }}>ECA (Español)</option>
            <option value="ELT"     {{ old('subject', $teacher->subject) == 'ELT'     ? 'selected' : '' }}>ELT (Inglés)</option>
            <option value="ambos"   {{ old('subject', $teacher->subject) == 'ambos'   ? 'selected' : '' }}>Ambos</option>
        </select>
    </div>
</div>


                <div style="margin-bottom:20px">
                    <div style="font-family:'Space Grotesk',sans-serif; font-size:15px; font-weight:600;
                                margin-bottom:16px; padding-bottom:8px; border-bottom:1px solid var(--border)">
                        🔐 Credenciales MEE
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Usuario MEE</label>
                            <input type="text" name="mee_username" class="form-control"
                                   value="{{ old('mee_username', $teacher->mee_username) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contraseña MEE</label>
                            <input type="text" name="mee_password" class="form-control"
                                   value="{{ old('mee_password', $teacher->mee_password) }}">
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('schools.teachers.index', $teacher->school) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection