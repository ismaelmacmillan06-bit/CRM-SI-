@extends('layouts.app')

@section('title', 'Editar Visita')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar visita</span>
            <a href="{{ route('schools.visits.index', $visit->school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('visits.update', $visit) }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Consultor *</label>
                        <select name="consultant_id" class="form-control" required>
                            <option value="">-- Selecciona --</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}"
                                    {{ old('consultant_id', $visit->consultant_id) == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="pendiente" {{ old('status', $visit->status) == 'pendiente' ? 'selected' : '' }}>⏳ Pendiente</option>
                            <option value="en_curso"  {{ old('status', $visit->status) == 'en_curso'  ? 'selected' : '' }}>🔄 En curso</option>
                            <option value="terminada" {{ old('status', $visit->status) == 'terminada' ? 'selected' : '' }}>✅ Terminada</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Fecha programada *</label>
                        <input type="date" name="scheduled_date" class="form-control"
                               value="{{ old('scheduled_date', $visit->scheduled_date) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha realizada</label>
                        <input type="date" name="visit_date" class="form-control"
                               value="{{ old('visit_date', $visit->visit_date) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Próxima visita</label>
                    <input type="date" name="next_visit_date" class="form-control"
                           value="{{ old('next_visit_date', $visit->next_visit_date) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $visit->notes) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Resumen de visita</label>
                    <textarea name="summary" class="form-control" rows="4">{{ old('summary', $visit->summary) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Evidencia (foto)</label>
                    @if($visit->evidence)
                        <div style="margin-bottom:10px">
                            <img src="{{ asset('storage/' . $visit->evidence) }}"
                                 style="width:120px; height:120px; object-fit:cover; border-radius:8px;">
                            <br>
                            <small style="color:var(--text-muted)">Evidencia actual — sube una nueva para reemplazarla</small>
                        </div>
                    @endif
                    <input type="file" name="evidence" class="form-control" accept="image/*">
                    <small style="color:var(--text-muted)">Máximo 2MB. Formatos: JPG, PNG, GIF</small>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('schools.visits.index', $visit->school) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection