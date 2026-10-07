<div class="audiencia-checks" style="display:grid; grid-template-columns:1fr 1fr; gap:8px 14px">
    @foreach(\App\Models\Comunicado::AUDIENCIAS as $valor => $etiqueta)
        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer">
            <input type="checkbox" name="audiencia[]" value="{{ $valor }}" class="aud-check"
                   {{ in_array($valor, $seleccion ?? []) ? 'checked' : '' }}>
            {{ $etiqueta }}
        </label>
    @endforeach
</div>
