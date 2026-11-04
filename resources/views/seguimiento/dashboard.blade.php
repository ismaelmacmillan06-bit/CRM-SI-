@extends('layouts.app')

@section('title', 'Seguimiento · ' . $nombre)

@section('content')
<div style="margin-bottom:24px">
    <h1 style="font-family:'Bricolage Grotesque',sans-serif; font-size:24px; font-weight:700; margin:0">
        {{ $icono }} Seguimiento {{ $nombre }}
    </h1>
</div>

@unless($servicioLigado)
<div style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:12px 16px;
            border-radius:8px; margin-bottom:20px; font-size:13.5px">
    ⚠️ Ningún servicio está ligado a {{ $nombre }}.
    @role('admin')
        Elígelo en el campo <strong>Gestor de plataformas</strong> de
        <a href="{{ route('configuracion.servicios.index') }}" style="color:#92400e; text-decoration:underline">Configuración → Servicios</a>.
    @else
        Pide a un administrador que lo configure.
    @endrole
</div>
@endunless

{{-- Cards --}}
<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px">
    <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px">
        <span style="font-size:26px; line-height:1">🏫</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#0d1117; line-height:1">{{ $filas->count() }}</div>
            <div style="font-size:12px; font-weight:600; color:#64748b; margin-top:2px">Colegios</div>
        </div>
    </div>
    <div style="background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px">
        <span style="font-size:26px; line-height:1">✅</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#16a34a; line-height:1">{{ $completos }}</div>
            <div style="font-size:12px; font-weight:600; color:#16a34a; opacity:.85; margin-top:2px">Implementación completa</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="gap:12px; flex-wrap:wrap">
        <span class="card-title">🏫 Colegios con {{ $nombre }}</span>
        @if($filas->isNotEmpty())
            <input type="search" id="buscador-colegios" class="form-control" autocomplete="off"
                   placeholder="🔍 Buscar colegio..." style="max-width:260px">
        @endif
    </div>
    <div style="overflow-x:auto">
    <table class="table">
        <thead>
            <tr>
                <th>Colegio</th>
                <th>Consultor digital</th>
                <th style="min-width:180px">Avance</th>
                <th>Siguiente acción</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="tabla-colegios">
            @forelse($filas as $fila)
            <tr data-nombre="{{ mb_strtolower($fila['colegio']->name . ' ' . $fila['colegio']->nexus_id) }}">
                <td>
                    <strong>{{ $fila['colegio']->name }}</strong>
                    <div style="font-size:12px; color:var(--text-muted)">{{ $fila['colegio']->state ?? $fila['colegio']->city ?? '—' }}</div>
                </td>
                <td style="font-size:13px">{{ $fila['consultor'] ?? 'Sin asignar' }}</td>
                <td>
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--text-muted); margin-bottom:4px">
                        <span>{{ $fila['hechas'] }}/{{ $totalAcciones }}</span>
                        <span>{{ $fila['pct'] }}%</span>
                    </div>
                    <div style="background:var(--surface2); border-radius:20px; height:7px; overflow:hidden">
                        <div style="height:100%; width:{{ $fila['pct'] }}%; border-radius:20px;
                                    background:{{ $fila['completo'] ? 'var(--success)' : 'var(--accent)' }}"></div>
                    </div>
                </td>
                <td style="font-size:13px">
                    @if($fila['completo'])
                        <span class="badge badge-success">Implementación completa</span>
                    @else
                        {{ $fila['siguiente'] }}
                    @endif
                </td>
                <td style="text-align:right">
                    <a href="{{ route('seguimiento.colegio', [$slug, $fila['colegio']]) }}" class="btn btn-secondary btn-sm"
                       style="white-space:nowrap">Gestionar →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay colegios con {{ $nombre }}. Márcalo en <strong>Servicios adicionales</strong> al editar un colegio.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<script>
    document.getElementById('buscador-colegios')?.addEventListener('input', function () {
        const texto = this.value.toLowerCase().trim();
        document.querySelectorAll('#tabla-colegios tr[data-nombre]').forEach(function (fila) {
            fila.style.display = fila.dataset.nombre.includes(texto) ? '' : 'none';
        });
    });
</script>
@endsection
