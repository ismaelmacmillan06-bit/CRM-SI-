@extends('layouts.app')

@section('title', 'Nueva Visita')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">📅 Registrar nueva visita</span>
            <a href="{{ route('schools.visits.index', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('schools.visits.store', $school) }}" enctype="multipart/form-data">
                @csrf

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Consultor *</label>
                        <select name="consultant_id" class="form-control" required>
                            <option value="">-- Selecciona --</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}"
                                    {{ old('consultant_id') == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('consultant_id')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="pendiente"  {{ old('status') == 'pendiente'  ? 'selected' : '' }} selected>⏳ Pendiente</option>
                            <option value="en_curso"   {{ old('status') == 'en_curso'   ? 'selected' : '' }}>🔄 En curso</option>
                            <option value="terminada"  {{ old('status') == 'terminada'  ? 'selected' : '' }}>✅ Terminada</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Fecha programada *</label>
                        <input type="date" name="scheduled_date" class="form-control"
                               value="{{ old('scheduled_date') }}" required>
                        @error('scheduled_date')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha realizada</label>
                        <input type="date" name="visit_date" class="form-control"
                               value="{{ old('visit_date') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Próxima visita</label>
                    <input type="date" name="next_visit_date" class="form-control"
                           value="{{ old('next_visit_date') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Notas previas a la visita...">{{ old('notes') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Resumen de visita</label>
                    <textarea name="summary" class="form-control" rows="4"
                              placeholder="Resumen de lo que se realizó en la visita...">{{ old('summary') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Evidencia (foto)</label>
                    <input type="file" name="evidence" class="form-control" accept="image/*">
                    <small style="color:var(--text-muted)">Máximo 2MB. Formatos: JPG, PNG, GIF</small>
                    @error('evidence')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('schools.visits.index', $school) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Visita</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection