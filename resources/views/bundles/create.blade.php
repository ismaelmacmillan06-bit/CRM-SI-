@extends('layouts.app')

@section('title', 'Nuevo Bundle')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">📚 Registrar nuevo bundle</span>
            <a href="{{ route('bundles.index') }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('bundles.store') }}">
                @csrf

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select name="type" class="form-control" required>
                            <option value="">-- Selecciona --</option>
                            <option value="ELT"           {{ old('type') == 'ELT'           ? 'selected' : '' }}>ELT</option>
                            <option value="Plan Lector"   {{ old('type') == 'Plan Lector'   ? 'selected' : '' }}>Plan Lector</option>
                            <option value="Imagina"       {{ old('type') == 'Imagina'       ? 'selected' : '' }}>Imagina</option>
                            <option value="Wikids"        {{ old('type') == 'Wikids'        ? 'selected' : '' }}>Wikids</option>
                            <option value="Pienso Contigo"{{ old('type') == 'Pienso Contigo'? 'selected' : '' }}>Pienso Contigo</option>
                            <option value="Entrelineas"   {{ old('type') == 'Entrelineas'   ? 'selected' : '' }}>Entrelineas</option>
                            <option value="Palabrario"    {{ old('type') == 'Palabrario'    ? 'selected' : '' }}>Palabrario</option>
                            <option value="Complemento"   {{ old('type') == 'Complemento'   ? 'selected' : '' }}>Complemento</option>
                            <option value="Frances"       {{ old('type') == 'Frances'       ? 'selected' : '' }}>Francés</option>
                            <option value="Ecosistemas"   {{ old('type') == 'Ecosistemas'   ? 'selected' : '' }}>Ecosistemas</option>
                            <option value="Praxis"        {{ old('type') == 'Praxis'        ? 'selected' : '' }}>Praxis</option>
                            <option value="Ekos"          {{ old('type') == 'Ekos'          ? 'selected' : '' }}>Ekos</option>
                            <option value="Carrousel"     {{ old('type') == 'Carrousel'     ? 'selected' : '' }}>Carrousel</option>
                        </select>
                        @error('type')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Serie *</label>
                        <input type="text" name="serie" class="form-control"
                               value="{{ old('serie') }}" required
                               placeholder="Ej: Academy Stars Second Edition">
                        @error('serie')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre del bundle *</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" required
                           placeholder="Ej: Academy Stars Level 1 Digital Pupil's Book">
                    @error('name')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nivel</label>
                        <select name="level" class="form-control">
                            <option value="">-- Selecciona --</option>
                            <option value="Preescolar"   {{ old('level') == 'Preescolar'   ? 'selected' : '' }}>Preescolar</option>
                            <option value="Primaria"     {{ old('level') == 'Primaria'     ? 'selected' : '' }}>Primaria</option>
                            <option value="Secundaria"   {{ old('level') == 'Secundaria'   ? 'selected' : '' }}>Secundaria</option>
                            <option value="Preparatoria" {{ old('level') == 'Preparatoria' ? 'selected' : '' }}>Preparatoria</option>
                            <option value="Licenciatura" {{ old('level') == 'Licenciatura' ? 'selected' : '' }}>Licenciatura</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grado</label>
                        <input type="text" name="grade" class="form-control"
                               value="{{ old('grade') }}" placeholder="Ej: 1°, 2°, 3°">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Rol *</label>
                    <select name="role" class="form-control" required>
                        <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>👨‍🎓 Alumno</option>
                        <option value="teacher" {{ old('role') == 'teacher' ? 'selected' : '' }}>👨‍🏫 Docente</option>
                    </select>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('bundles.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Bundle</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection