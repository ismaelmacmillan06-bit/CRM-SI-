{{-- Responsable de plataforma (solo admin): en los colegios que llevan la plataforma puede
     trabajar como si fueran suyos, menos borrar. Aplica a consultores digitales. --}}
@php $marcadas = old('responsable_seguimiento', $seleccionadas ?? []); @endphp
<div class="form-group">
    <label class="form-label">Responsable de Seguimiento</label>
    <input type="hidden" name="responsable_seguimiento_enviado" value="1">
    <div style="display:flex; flex-wrap:wrap; gap:8px">
        @foreach(\App\Http\Controllers\SeguimientoController::PLATAFORMAS as $slugResp => $plataformaResp)
            <label style="display:inline-flex; align-items:center; gap:7px; padding:8px 12px; border:1px solid var(--border);
                          border-radius:8px; cursor:pointer; font-size:13px; background:var(--surface)">
                <input type="checkbox" name="responsable_seguimiento[]" value="{{ $slugResp }}"
                       @checked(in_array($slugResp, (array) $marcadas, true))>
                {{ $plataformaResp['icono'] }} {{ $plataformaResp['nombre'] }}
            </label>
        @endforeach
    </div>
    <small style="color:var(--text-muted); display:block; margin-top:6px">
        Solo aplica a <strong>consultores digitales</strong>. En todos los colegios con esa plataforma podrá capturar,
        registrar tickets y sesiones, generar Report Master y exportar, pero <strong>no borrar</strong>
        (eso queda para el consultor asignado al colegio).
    </small>
    @error('responsable_seguimiento')<small style="color:var(--danger)">{{ $message }}</small>@enderror
</div>
