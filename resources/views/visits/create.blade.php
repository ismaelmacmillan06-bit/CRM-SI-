@extends('layouts.app')

@section('title', 'Nueva Sesión')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">📅 Registrar nueva sesión</span>
            <a href="{{ route('schools.visits.index', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('schools.visits.store', $school) }}" enctype="multipart/form-data">
                @csrf

                @include('visits.partials.sesion-clasificacion')

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

                <div class="grid-2" id="grid-fechas">
                    <div class="form-group" id="grupo-fecha-programada">
                        <label class="form-label">Fecha programada *</label>
                        <input type="date" name="scheduled_date" id="input-scheduled-date" class="form-control"
                               value="{{ old('scheduled_date') }}" required>
                        @error('scheduled_date')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="label-visit-date">Fecha realizada</label>
                        <input type="date" name="visit_date" id="input-visit-date" class="form-control"
                               value="{{ old('visit_date') }}">
                        @error('visit_date')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Próxima sesión</label>
                    <input type="date" name="next_visit_date" class="form-control"
                           value="{{ old('next_visit_date') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo de sesión</label>
                    <input type="text" name="motivo" class="form-control"
                           placeholder="Ej: Seguimiento de arranque, capacitación, entrega de bundles..."
                           value="{{ old('motivo') }}">
                </div>

                @include('visits.partials.sesion-productos')

                @include('visits.partials.acudieron-picker', ['selectedIds' => old('attendees', [])])

                <div class="form-group">
                    <label class="form-label">Descripción del problema</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Describe el problema que se atiende en la sesión...">{{ old('notes') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Solución proporcionada</label>
                    <textarea name="summary" class="form-control" rows="4"
                              placeholder="Describe la solución que se dio al problema...">{{ old('summary') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Evidencia (foto)</label>
                    <input type="file" name="evidence" class="form-control" accept="image/*">
                    <small style="color:var(--text-muted)">Máximo 2MB. Formatos: JPG, PNG, GIF</small>
                    @error('evidence')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('schools.visits.index', $school) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Sesión</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Emergencia: sin fecha programada; la fecha realizada pasa a ser obligatoria y ocupa todo el ancho
    const statusSelectVisita = document.querySelector('select[name="status"]');
    const inputVisitDate     = document.getElementById('input-visit-date');
    const labelVisitDate     = document.getElementById('label-visit-date');
    const gridFechas         = document.getElementById('grid-fechas');
    const grupoProgramada    = document.getElementById('grupo-fecha-programada');
    const inputProgramada    = document.getElementById('input-scheduled-date');
    const radiosTipo         = document.querySelectorAll('input[name="tipo"]');
    const radiosPrioridad    = document.querySelectorAll('input[name="prioridad"]');
    const wrapPrioridades    = document.getElementById('ses-prioridades');
    const hintPrioridad      = document.getElementById('ses-prioridad-hint');

    function esEmergencia() {
        return document.querySelector('input[name="tipo"]:checked')?.value === 'emergencia';
    }

    function actualizarFormularioSesion() {
        const emergencia = esEmergencia();

        grupoProgramada.style.display = emergencia ? 'none' : '';
        gridFechas.style.gridTemplateColumns = emergencia ? '1fr' : '';
        inputProgramada.required = !emergencia;

        if (emergencia) {
            radiosPrioridad.forEach(r => { r.checked = r.value === 'muy_urgente'; });
        }
        wrapPrioridades.classList.toggle('locked', emergencia);
        hintPrioridad.style.display = emergencia ? 'block' : 'none';

        const requerida = emergencia || ['en_curso', 'terminada'].includes(statusSelectVisita.value);
        inputVisitDate.required = requerida;
        labelVisitDate.textContent = requerida ? 'Fecha realizada *' : 'Fecha realizada';
    }

    statusSelectVisita.addEventListener('change', actualizarFormularioSesion);
    radiosTipo.forEach(r => r.addEventListener('change', actualizarFormularioSesion));
    actualizarFormularioSesion();
</script>
@endsection