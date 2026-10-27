@php
    [$pBg, $pFg] = \App\Models\Visit::PRIORIDAD_COLORES[$sesion->prioridad] ?? ['#f3f4f6', '#374151'];
    $chip = 'display:inline-block; font-size:10.5px; padding:2px 8px; border-radius:999px; font-weight:600;';
@endphp
<div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:6px">
    <span style="{{ $chip }} background:#f1f5f9; color:#475569">{{ \App\Models\Visit::MODALIDADES[$sesion->modalidad] ?? '—' }}</span>
    <span style="{{ $chip }} background:#f1f5f9; color:#475569">{{ \App\Models\Visit::TIPOS[$sesion->tipo] ?? '—' }}</span>
    <span style="{{ $chip }} background:{{ $pBg }}; color:{{ $pFg }}">{{ \App\Models\Visit::PRIORIDADES[$sesion->prioridad] ?? '—' }}</span>
</div>
@if(!empty($sesion->productos))
    @include('partials.producto-chips', ['productos' => $sesion->productos, 'compacto' => true, 'contenedor' => 'margin-top:6px'])
@endif
