@extends('layouts.app')

@section('title', 'Editar Docente')

@section('content')
@php $currentRoles = old('roles', $teacher->roles->pluck('role')->toArray()); @endphp

<div style="max-width:700px">
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

                {{-- Roles (múltiple selección) --}}
                <div class="form-group">
                    <label class="form-label">Roles <small style="color:var(--text-muted); font-weight:400">(puede seleccionar varios)</small></label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:4px">
                        @foreach(\App\Models\Teacher::ROLES as $key => $label)
                        <label style="display:flex; align-items:center; gap:8px; padding:10px 12px;
                                      border:1px solid var(--border); border-radius:8px; cursor:pointer;
                                      font-size:13px; transition:background 0.15s"
                               onmouseover="this.style.background='var(--surface2)'"
                               onmouseout="this.style.background=''">
                            <input type="checkbox" name="roles[]" value="{{ $key }}"
                                   style="accent-color:var(--accent); width:16px; height:16px"
                                   {{ in_array($key, $currentRoles) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
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
