@php
    $tamano = $compacto ?? false;
    $estilo = $tamano ? 'font-size:10.5px; padding:2px 8px;' : 'font-size:12px; padding:3px 10px;';
@endphp
<div style="display:flex; flex-wrap:wrap; gap:5px; {{ $contenedor ?? '' }}">
    @forelse($productos ?? [] as $producto)
        @php [$ico, $color] = \App\Models\Visit::PRODUCTO_ESTILOS[$producto] ?? ['📦', '#64748b']; @endphp
        <span style="display:inline-flex; align-items:center; gap:4px; white-space:nowrap; border-radius:999px;
                     font-weight:600; {{ $estilo }} color:{{ $color }};
                     background:color-mix(in srgb, {{ $color }} 12%, #fff);
                     border:1px solid color-mix(in srgb, {{ $color }} 28%, transparent)">{{ $ico }} {{ $producto }}</span>
    @empty
        <span style="color:var(--text-muted)">—</span>
    @endforelse
</div>