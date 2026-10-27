@php
    $modalidadSel = old('modalidad', $visit->modalidad ?? 'presencial');
    $tipoSel      = old('tipo', $visit->tipo ?? 'programada');
    $prioridadSel = $tipoSel === 'emergencia' ? 'muy_urgente' : old('prioridad', $visit->prioridad ?? 'media');

    $tipos = [
        'programada' => ['📅', 'Programada', 'Agendada con anticipación', '#0ea5e9'],
        'emergencia' => ['🚨', 'Emergencia', 'No programada, atención inmediata', '#dc2626'],
    ];
    $modalidades = [
        'presencial' => ['🏫', 'Presencial', 'En el colegio', '#16a34a'],
        'virtual'    => ['💻', 'Virtual', 'Por videollamada', '#7c3aed'],
    ];
    $prioridades = [
        'baja'        => ['🟢', 'Baja', '#16a34a'],
        'media'       => ['🔵', 'Media', '#0369a1'],
        'alta'        => ['🟠', 'Alta', '#d97706'],
        'muy_urgente' => ['🔴', 'Muy urgente', '#dc2626'],
    ];
@endphp

<style>
    .ses-opts { display:grid; gap:10px; }
    .ses-opts.cols-2 { grid-template-columns: 1fr 1fr; }
    .ses-opts.cols-4 { grid-template-columns: repeat(4, 1fr); }
    .ses-opt { position:relative; cursor:pointer; display:block; }
    .ses-opt input { position:absolute; opacity:0; pointer-events:none; }
    .ses-opt .ses-box {
        display:flex; align-items:center; gap:10px; padding:11px 14px; height:100%;
        border:1.5px solid var(--border); border-radius:10px; background:var(--surface);
        transition:border-color .15s, background .15s, box-shadow .15s, transform .15s;
    }
    .ses-opt:hover .ses-box { border-color:var(--c); }
    .ses-opt input:checked + .ses-box {
        border-color:var(--c);
        background:color-mix(in srgb, var(--c) 10%, var(--surface));
        box-shadow:0 0 0 3px color-mix(in srgb, var(--c) 16%, transparent);
    }
    .ses-opt input:focus-visible + .ses-box { outline:2px solid var(--c); outline-offset:2px; }
    .ses-ico { font-size:20px; line-height:1; }
    .ses-title { font-size:13.5px; font-weight:600; color:var(--text); }
    .ses-sub { font-size:11.5px; color:var(--text-muted); margin-top:1px; }
    .ses-opt input:checked + .ses-box .ses-title { color:var(--c); }
    .ses-opts.cols-4 .ses-box { justify-content:center; padding:10px 8px; }
    .ses-opts.locked .ses-opt:not(.is-locked-on) { opacity:.4; pointer-events:none; }
    .ses-opts.locked .ses-opt { cursor:default; }
    .ses-panel {
        border:1px solid var(--border); border-radius:12px; padding:18px; margin-bottom:22px;
        background:var(--surface2);
    }
    .ses-panel .form-group:last-child { margin-bottom:0; }
    .ses-hint { display:none; margin-top:8px; font-size:12px; color:#dc2626; }
    @media (max-width: 600px) {
        .ses-opts.cols-4 { grid-template-columns: 1fr 1fr; }
    }
</style>

<div class="ses-panel">
    <div class="form-group">
        <label class="form-label">Tipo de sesión *</label>
        <div class="ses-opts cols-2">
            @foreach($tipos as $val => [$ico, $titulo, $sub, $color])
                <label class="ses-opt" style="--c:{{ $color }}">
                    <input type="radio" name="tipo" value="{{ $val }}" data-label="{{ $titulo }}"
                           {{ $tipoSel === $val ? 'checked' : '' }} required>
                    <span class="ses-box">
                        <span class="ses-ico">{{ $ico }}</span>
                        <span><div class="ses-title">{{ $titulo }}</div><div class="ses-sub">{{ $sub }}</div></span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('tipo')<small style="color:var(--danger)">{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label class="form-label">Modalidad *</label>
        <div class="ses-opts cols-2">
            @foreach($modalidades as $val => [$ico, $titulo, $sub, $color])
                <label class="ses-opt" style="--c:{{ $color }}">
                    <input type="radio" name="modalidad" value="{{ $val }}" data-label="{{ $titulo }}"
                           {{ $modalidadSel === $val ? 'checked' : '' }} required>
                    <span class="ses-box">
                        <span class="ses-ico">{{ $ico }}</span>
                        <span><div class="ses-title">{{ $titulo }}</div><div class="ses-sub">{{ $sub }}</div></span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('modalidad')<small style="color:var(--danger)">{{ $message }}</small>@enderror
    </div>

    <div class="form-group">
        <label class="form-label">Prioridad *</label>
        <div class="ses-opts cols-4" id="ses-prioridades">
            @foreach($prioridades as $val => [$ico, $titulo, $color])
                <label class="ses-opt {{ $val === 'muy_urgente' ? 'is-locked-on' : '' }}" style="--c:{{ $color }}">
                    <input type="radio" name="prioridad" value="{{ $val }}" data-label="{{ $titulo }}"
                           {{ $prioridadSel === $val ? 'checked' : '' }} required>
                    <span class="ses-box">
                        <span class="ses-ico" style="font-size:13px">{{ $ico }}</span>
                        <span class="ses-title">{{ $titulo }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        <div class="ses-hint" id="ses-prioridad-hint">🚨 Una sesión de emergencia siempre es Muy urgente.</div>
        @error('prioridad')<small style="color:var(--danger)">{{ $message }}</small>@enderror
    </div>
</div>
