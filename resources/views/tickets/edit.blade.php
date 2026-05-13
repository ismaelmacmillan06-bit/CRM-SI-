@extends('layouts.app')

@section('title', 'Editar Ticket')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar ticket</span>
            <a href="{{ route('schools.tickets.index', $ticket->school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('tickets.update', $ticket) }}">
                @csrf @method('PUT')

                <div class="grid-2">
    <div class="form-group">
        <label class="form-label">Título *</label>
        <select name="title" class="form-control" required>
            <option value="">-- Selecciona --</option>
            <option value="Alta de docente"       {{ old('title', $ticket->title) == 'Alta de docente'       ? 'selected' : '' }}>Alta de docente</option>
            <option value="Cambio de docente"     {{ old('title', $ticket->title) == 'Cambio de docente'     ? 'selected' : '' }}>Cambio de docente</option>
            <option value="Alta de alumno"        {{ old('title', $ticket->title) == 'Alta de alumno'        ? 'selected' : '' }}>Alta de alumno</option>
            <option value="Libros pendientes"     {{ old('title', $ticket->title) == 'Libros pendientes'     ? 'selected' : '' }}>Libros pendientes</option>
            <option value="Usuario y contraseña"  {{ old('title', $ticket->title) == 'Usuario y contraseña'  ? 'selected' : '' }}>Usuario y contraseña</option>
            <option value="Duda de funcionalidad" {{ old('title', $ticket->title) == 'Duda de funcionalidad' ? 'selected' : '' }}>Duda de funcionalidad</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Medio de ticket *</label>
        <select name="medium" class="form-control" required>
            <option value="salesforce" {{ old('medium', $ticket->medium) == 'salesforce' ? 'selected' : '' }}>Sales Force</option>
            <option value="whaticket"  {{ old('medium', $ticket->medium) == 'whaticket'  ? 'selected' : '' }}>Whaticket</option>
            <option value="whatsapp"   {{ old('medium', $ticket->medium) == 'whatsapp'   ? 'selected' : '' }}>WhatsApp</option>
        </select>
    </div>
</div>

                <div class="form-group">
                    <label class="form-label">Descripción *</label>
                    <textarea name="description" class="form-control" rows="4" required>{{ old('description', $ticket->description) }}</textarea>
                    @error('description')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Prioridad *</label>
                        <select name="priority" class="form-control" required>
                            <option value="low"    {{ old('priority', $ticket->priority) == 'low'    ? 'selected' : '' }}>🟢 Baja</option>
                            <option value="medium" {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>🟡 Media</option>
                            <option value="high"   {{ old('priority', $ticket->priority) == 'high'   ? 'selected' : '' }}>🔴 Alta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="open"        {{ old('status', $ticket->status) == 'open'        ? 'selected' : '' }}>Abierto</option>
                            <option value="in_progress" {{ old('status', $ticket->status) == 'in_progress' ? 'selected' : '' }}>En progreso</option>
                            <option value="closed"      {{ old('status', $ticket->status) == 'closed'      ? 'selected' : '' }}>Cerrado</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('schools.tickets.index', $ticket->school) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection