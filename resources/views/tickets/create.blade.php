@extends('layouts.app')

@section('title', 'Nuevo Ticket')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">🎫 Registrar nuevo ticket</span>
            <a href="{{ route('schools.tickets.index', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('schools.tickets.store', $school) }}">
                @csrf

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Título *</label>
                        <select name="title" class="form-control" required>
                            <option value="">-- Selecciona --</option>
                            <option value="Alta de docente"       {{ old('title') == 'Alta de docente'       ? 'selected' : '' }}>Alta de docente</option>
                            <option value="Cambio de docente"     {{ old('title') == 'Cambio de docente'     ? 'selected' : '' }}>Cambio de docente</option>
                            <option value="Alta de alumno"        {{ old('title') == 'Alta de alumno'        ? 'selected' : '' }}>Alta de alumno</option>
                            <option value="Libros pendientes"     {{ old('title') == 'Libros pendientes'     ? 'selected' : '' }}>Libros pendientes</option>
                            <option value="Usuario y contraseña"  {{ old('title') == 'Usuario y contraseña'  ? 'selected' : '' }}>Usuario y contraseña</option>
                            <option value="Duda de funcionalidad" {{ old('title') == 'Duda de funcionalidad' ? 'selected' : '' }}>Duda de funcionalidad</option>
                        </select>
                        @error('title')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Medio de ticket *</label>
                        <select name="medium" class="form-control" required>
                            <option value="salesforce" {{ old('medium') == 'salesforce' ? 'selected' : '' }}>Sales Force</option>
                            <option value="whaticket"  {{ old('medium') == 'whaticket'  ? 'selected' : '' }}>Whaticket</option>
                            <option value="whatsapp"   {{ old('medium') == 'whatsapp'   ? 'selected' : '' }}>WhatsApp</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción *</label>
                    <textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
                    @error('description')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Prioridad *</label>
                        <select name="priority" class="form-control" required>
                            <option value="low"    {{ old('priority') == 'low'    ? 'selected' : '' }}>🟢 Baja</option>
                            <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }} selected>🟡 Media</option>
                            <option value="high"   {{ old('priority') == 'high'   ? 'selected' : '' }}>🔴 Alta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Consultor asignado *</label>
                        <select name="consultant_id" class="form-control" required>
                            <option value="">-- Selecciona --</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}"
                                    {{ old('consultant_id') == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('schools.tickets.index', $school) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection