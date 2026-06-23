@extends('layouts.app')

@section('title', 'Editar Alumno')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar alumno</span>
            <a href="{{ route('schools.students.index', $student->school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('students.update', $student) }}">
                @csrf @method('PUT')

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $student->name) }}" required>
                        @error('name')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellidos *</label>
                        <input type="text" name="last_name" class="form-control"
                               value="{{ old('last_name', $student->last_name) }}" required>
                        @error('last_name')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nivel</label>
                        <select name="level" class="form-control">
                            <option value="">-- Selecciona --</option>
                            <option value="Maternal"     {{ old('level', $student->level) == 'Maternal'     ? 'selected' : '' }}>Maternal</option>
                            <option value="Preescolar"   {{ old('level', $student->level) == 'Preescolar'   ? 'selected' : '' }}>Preescolar</option>
                            <option value="Primaria"     {{ old('level', $student->level) == 'Primaria'     ? 'selected' : '' }}>Primaria</option>
                            <option value="Secundaria"   {{ old('level', $student->level) == 'Secundaria'   ? 'selected' : '' }}>Secundaria</option>
                            <option value="Preparatoria" {{ old('level', $student->level) == 'Preparatoria' ? 'selected' : '' }}>Preparatoria</option>
                            <option value="Licenciatura" {{ old('level', $student->level) == 'Licenciatura' ? 'selected' : '' }}>Licenciatura</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grado</label>
                        <input type="text" name="grade" class="form-control"
                               value="{{ old('grade', $student->grade) }}"
                               placeholder="Ej: 1°A, 2°B">
                    </div>
                </div>

                <div style="margin-bottom:20px">
                    <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:15px; font-weight:600;
                                margin-bottom:16px; padding-bottom:8px; border-bottom:1px solid var(--border)">
                        🔐 Credenciales MEE
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Usuario MEE *</label>
                            <input type="text" name="mee_username" class="form-control"
                                   value="{{ old('mee_username', $student->mee_username) }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contraseña MEE *</label>
                            <input type="text" name="mee_password" class="form-control"
                                   value="{{ old('mee_password', $student->mee_password) }}" required>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('schools.students.index', $student->school) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection