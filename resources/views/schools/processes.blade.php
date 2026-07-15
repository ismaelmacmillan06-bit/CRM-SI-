@extends('layouts.app')

@section('title', "Acciones de arranque — {$school->name}")

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
</div>

@foreach($school->schoolLevels as $schoolLevel)
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-title">🎓 {{ $schoolLevel->level->name }}</span>
        @php
            $total = $schoolLevel->processes->count();
            $done  = $schoolLevel->processes->where('status', 'done')->count();
            $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
        @endphp
        <span style="font-size:13px; color:var(--text-muted)">{{ $done }}/{{ $total }} completados</span>
    </div>

    <div style="padding:0 24px 0; margin-top:16px">
        <div style="background:var(--surface2); border-radius:20px; height:6px; overflow:hidden">
            <div style="height:100%; width:{{ $pct }}%; background:var(--accent);
                        border-radius:20px; transition:width 0.3s"></div>
        </div>
    </div>

    <div style="overflow-x:auto">
    <table class="table" style="margin-top:8px; table-layout:fixed; width:100%">
        <colgroup>
            <col style="width:3%">
            <col style="width:18%">
            <col style="width:12%">
            <col style="width:11%">
            <col style="width:8%">
            <col style="width:17%">
            <col style="width:6%">
            <col style="width:25%">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Proceso</th>
                <th>Status</th>
                <th>Completado por</th>
                <th>Fecha</th>
                <th>Notas</th>
                <th>Evidencia</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($schoolLevel->processes->sortBy('process.order') as $slp)
            <tr>
                <td style="color:var(--text-muted); font-size:13px">{{ $slp->process->order }}</td>

                <td style="overflow:hidden; text-overflow:ellipsis">
                    <strong>{{ $slp->process->name }}</strong>
                </td>

                <td>
                    @if($slp->status === 'done')
                        <span class="badge badge-success">✅ Completado</span>
                    @elseif($slp->status === 'in_progress')
                        <span class="badge badge-warning">🔄 En progreso</span>
                    @elseif($slp->status === 'reopened')
                        <span class="badge" style="background:rgba(255,120,0,0.15); color:#ff7800; border:1px solid rgba(255,120,0,0.3)">🔁 Reapertura</span>
                    @else
                        <span class="badge badge-gray">⏳ Pendiente</span>
                    @endif
                </td>

                <td style="font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">
                    {{ $slp->completedBy->user->name ?? '—' }}
                </td>

                <td style="font-size:12px; color:var(--text-muted); white-space:nowrap">
                    {{ $slp->completed_at ? \Carbon\Carbon::parse($slp->completed_at)->format('d/m/Y') : '—' }}
                </td>

                {{-- Notas: siempre editable, asociada al form via form= --}}
                <td>
                    <textarea name="notes"
                              form="form-slp-{{ $slp->id }}"
                              rows="2"
                              placeholder="Sin notas..."
                              style="width:100%; font-size:12px; padding:5px 8px; border-radius:6px;
                                     background:var(--surface2); border:1px solid var(--border);
                                     color:var(--text); resize:vertical; min-height:36px;
                                     font-family:inherit; line-height:1.4; box-sizing:border-box">{{ $slp->notes }}</textarea>
                </td>

                {{-- Evidencia: thumbnail + borrar --}}
                <td style="text-align:center">
                    @if($slp->evidence)
                        <a href="{{ Storage::url($slp->evidence) }}" target="_blank" title="Ver evidencia">
                            <img src="{{ Storage::url($slp->evidence) }}"
                                 style="width:40px; height:40px; object-fit:cover; border-radius:6px;
                                        border:2px solid #22c55e; display:block; margin:0 auto">
                        </a>
                        @hasanyrole('admin|consultor_digital')
                        <form method="POST"
                              action="{{ route('schools.processes.evidence.destroy', [$school, $slp]) }}"
                              id="form-del-ev-{{ $slp->id }}"
                              style="margin-top:4px">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                    style="font-size:10px; padding:2px 6px; line-height:1.4"
                                    onclick="confirmarEliminar('Eliminar evidencia', '¿Deseas borrar la imagen de evidencia de este proceso?', 'form-del-ev-{{ $slp->id }}')">
                                🗑
                            </button>
                        </form>
                        @endhasanyrole
                    @else
                        <span id="ev-placeholder-{{ $slp->id }}" style="color:var(--text-muted); font-size:12px">—</span>
                    @endif
                </td>

                {{-- Acción: solo visible para roles con escritura --}}
                <td>
                    @error("evidence_{$slp->id}")
                        <div style="color:#e55; font-size:11px; margin-bottom:4px">{{ $message }}</div>
                    @enderror

                    @hasanyrole('admin|consultor_digital')
                    <form method="POST"
                          action="{{ route('schools.processes.update', [$school, $slp]) }}"
                          enctype="multipart/form-data"
                          id="form-slp-{{ $slp->id }}"
                          style="display:flex; gap:4px; align-items:center; flex-wrap:wrap">
                        @csrf @method('POST')

                        <select name="status"
                                id="status-{{ $slp->id }}"
                                class="form-control"
                                style="flex:1; min-width:0; padding:6px 6px; font-size:12px">
                            <option value="pending"     {{ $slp->status === 'pending'     ? 'selected' : '' }}>⏳ Pendiente</option>
                            <option value="done"        {{ $slp->status === 'done'        ? 'selected' : '' }}>✅ Completado</option>
                            <option value="in_progress" {{ $slp->status === 'in_progress' ? 'selected' : '' }}>🔄 En proceso</option>
                            <option value="reopened"    {{ $slp->status === 'reopened'    ? 'selected' : '' }}>🔁 Reapertura</option>
                        </select>

                        {{-- Input oculto; solo JPG/PNG/WebP, máx 10 MB --}}
                        <input type="file"
                               name="evidence"
                               accept="image/jpeg,image/png,image/webp"
                               id="file-{{ $slp->id }}"
                               data-slp="{{ $slp->id }}"
                               data-has-evidence="{{ $slp->evidence ? '1' : '0' }}"
                               style="display:none">

                        {{-- Botón icono evidencia: verde si ya hay imagen, naranja si hay nueva seleccionada --}}
                        <label for="file-{{ $slp->id }}"
                               id="file-label-{{ $slp->id }}"
                               title="{{ $slp->evidence ? 'Cambiar evidencia (JPG, PNG o WebP · máx 10 MB)' : 'Subir evidencia (opcional) — JPG, PNG o WebP · máx 10 MB' }}"
                               style="cursor:pointer; flex-shrink:0; width:30px; height:30px;
                                      display:inline-flex; align-items:center; justify-content:center;
                                      background:var(--surface2); border-radius:6px; font-size:14px;
                                      border:1px solid {{ $slp->evidence ? '#22c55e' : 'var(--border)' }}">
                            📎
                        </label>

                        {{-- Nombre del archivo nuevo seleccionado (antes de guardar) --}}
                        <span id="file-name-{{ $slp->id }}"
                              style="display:none; font-size:10px; color:#f59e0b; flex-basis:100%;
                                     white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:150px"></span>

                        <button type="submit" class="btn btn-primary btn-sm"
                                style="flex-shrink:0; padding:6px 10px; font-size:12px; white-space:nowrap">
                            Guardar
                        </button>
                    </form>
                    @endhasanyrole
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
@endforeach

<script>
document.querySelectorAll('input[type="file"][data-slp]').forEach(function (fileInput) {
    const slpId    = fileInput.dataset.slp;
    const fileLabel = document.getElementById('file-label-' + slpId);
    const fileName  = document.getElementById('file-name-' + slpId);

    fileInput.addEventListener('change', function () {
        const file = this.files && this.files[0];

        if (!file) {
            if (fileLabel) fileLabel.style.borderColor = fileInput.dataset.hasEvidence === '1' ? '#22c55e' : '';
            if (fileName)  { fileName.style.display = 'none'; fileName.textContent = ''; }
            return;
        }

        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            alert('Formato no aceptado: ' + (file.type || 'desconocido') + '.\nSolo se permiten JPG, PNG o WebP.\nSi la foto es HEIC (iPhone), conviértela primero en "Archivos" → compartir → JPEG.');
            this.value = '';
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            alert('La imagen pesa ' + (file.size / 1024 / 1024).toFixed(1) + ' MB y el límite es 10 MB.\nReduce el tamaño o la calidad antes de subirla.');
            this.value = '';
            return;
        }

        // Si el proceso sigue en Pendiente, avanzar automáticamente a Completado al subir evidencia
        const statusSelect = document.getElementById('status-' + slpId);
        if (statusSelect && statusSelect.value === 'pending') {
            statusSelect.value = 'done';
        }

        if (fileLabel) fileLabel.style.borderColor = '#f59e0b';
        if (fileName)  { fileName.textContent = '📎 ' + file.name; fileName.style.display = 'inline-block'; }
    });
});
</script>
@endsection
