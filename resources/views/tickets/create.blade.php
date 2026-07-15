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
            <form method="POST" action="{{ route('schools.tickets.store', $school) }}"
                  enctype="multipart/form-data">
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

                <div class="form-group">
                    <label class="form-label">Foto de evidencia <span style="color:var(--text-muted); font-size:12px">(opcional · JPG, PNG, WEBP · máx. 5 MB)</span></label>
                    <div id="evidence-drop-zone" style="border:2px dashed var(--border); border-radius:8px; padding:24px; text-align:center; cursor:pointer; transition:border-color .2s"
                         onclick="document.getElementById('evidence-input').click()"
                         ondragover="event.preventDefault(); this.style.borderColor='var(--primary)'"
                         ondragleave="this.style.borderColor='var(--border)'"
                         ondrop="handleDrop(event)">
                        <div id="evidence-placeholder" style="color:var(--text-muted); font-size:14px">
                            📸 Haz clic o arrastra una imagen aquí
                        </div>
                        <img id="evidence-preview" src="" alt="preview" style="display:none; max-height:200px; max-width:100%; border-radius:6px; margin-top:8px">
                        <div id="evidence-name" style="display:none; font-size:12px; color:var(--text-muted); margin-top:6px"></div>
                    </div>
                    <input type="file" id="evidence-input" name="evidence" accept="image/*"
                           style="display:none" onchange="previewEvidence(this.files[0])">
                    @error('evidence')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('schools.tickets.index', $school) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function previewEvidence(file) {
    if (!file) return;
    const zone = document.getElementById('evidence-drop-zone');
    const placeholder = document.getElementById('evidence-placeholder');
    const preview = document.getElementById('evidence-preview');
    const nameEl = document.getElementById('evidence-name');

    const reader = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
        nameEl.textContent = file.name;
        nameEl.style.display = 'block';
        zone.style.borderColor = 'var(--primary)';
    };
    reader.readAsDataURL(file);
}

function handleDrop(e) {
    e.preventDefault();
    document.getElementById('evidence-drop-zone').style.borderColor = 'var(--border)';
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        const input = document.getElementById('evidence-input');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        previewEvidence(file);
    }
}
</script>
@endsection