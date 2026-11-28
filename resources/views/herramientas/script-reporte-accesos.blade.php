@extends('layouts.app')

@section('title', 'Script del Reporte de Accesos')

@section('content')
@php
    $estados = [
        'original'  => ['Script original', '#64748b', 'rgba(100,116,139,.12)'],
        'editado'   => ['Script editado', '#1b8a4c', 'rgba(34,160,90,.14)'],
        'eliminado' => ['Sin script (eliminado)', '#b91c1c', 'rgba(220,38,38,.12)'],
    ];
    [$estadoTexto, $estadoColor, $estadoFondo] = $estados[$actual['estado']];
@endphp

<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px">
    <div>
        <h1 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; margin:0">
            🛠️ Script del Reporte de Accesos
        </h1>
        <p style="margin:4px 0 0; font-size:13.5px; color:var(--text-muted)">
            Es el script que copian los consultores para la consola de MEE. Lo que guardes aquí lo ven todos
            al instante en la herramienta, sin subir cambios al repositorio.
        </p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap">
        <a href="{{ route('herramientas.index') }}" class="btn btn-secondary btn-sm">← Herramientas SI</a>
        <a href="{{ route('herramientas.reporte-accesos') }}" target="_blank" class="btn btn-secondary btn-sm">Abrir herramienta ↗</a>
    </div>
</div>

<div class="card" style="padding:18px 20px">
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px">
        <span style="font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; color:{{ $estadoColor }}; background:{{ $estadoFondo }}">
            {{ $estadoTexto }}
        </span>
        @if($actual['usuario'])
            <span style="font-size:12.5px; color:var(--text-muted)">
                Último cambio: {{ $actual['usuario'] }} ·
                {{ \Illuminate\Support\Carbon::parse($actual['fecha'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
            </span>
        @endif
    </div>

    @if($errors->any())
        <div class="alert alert-danger">❌ {{ $errors->first() }}</div>
    @endif

    @if($actual['estado'] === 'eliminado')
        <div style="background:rgba(220,38,38,.08); border:1px solid rgba(220,38,38,.3); color:#b91c1c; border-radius:8px; padding:10px 14px; font-size:13px; margin-bottom:12px">
            La herramienta muestra el aviso "Sin script disponible" y el botón Copiar está desactivado.
            Pega un script nuevo y guárdalo, o restaura el original.
        </div>
    @endif

    <form method="POST" action="{{ route('herramientas.reporte-accesos.script.update') }}" id="form-script">
        @csrf
        @method('PUT')
        <label for="script" style="font-size:12px; font-weight:700; color:var(--text-muted); letter-spacing:.04em">SCRIPT DE CONSOLA</label>
        <textarea id="script" name="script" spellcheck="false" autocomplete="off"
                  style="width:100%; min-height:460px; margin-top:6px; font-family:Consolas,'Courier New',monospace; font-size:12.5px;
                         line-height:1.5; padding:12px; border:1px solid var(--border); border-radius:8px; background:var(--surface2);
                         color:var(--text); resize:vertical; tab-size:2; white-space:pre; overflow:auto">{{ old('script', $actual['estado'] === 'eliminado' ? '' : $actual['script']) }}</textarea>
    </form>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; align-items:center">
        <button type="submit" form="form-script" class="btn btn-primary">💾 Guardar script</button>

        @if($actual['estado'] !== 'eliminado')
            <form method="POST" action="{{ route('herramientas.reporte-accesos.script.destroy') }}"
                  onsubmit="return confirm('¿Eliminar el script? Los consultores ya no podrán copiarlo hasta que cargues uno nuevo o restaures el original.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">🗑️ Eliminar script</button>
            </form>
        @endif

        @if($actual['estado'] !== 'original')
            <form method="POST" action="{{ route('herramientas.reporte-accesos.script.restore') }}"
                  onsubmit="return confirm('¿Restaurar el script original de la herramienta? Se perderán los cambios guardados aquí.')">
                @csrf
                <button type="submit" class="btn btn-secondary">↩️ Restaurar original</button>
            </form>
        @endif
    </div>
</div>

<script>
// Tab inserta 2 espacios en el editor en lugar de saltar al siguiente campo
document.getElementById('script').addEventListener('keydown', function (e) {
    if (e.key !== 'Tab') return;
    e.preventDefault();
    var ini = this.selectionStart, fin = this.selectionEnd;
    this.value = this.value.slice(0, ini) + '  ' + this.value.slice(fin);
    this.selectionStart = this.selectionEnd = ini + 2;
});
</script>
@endsection
