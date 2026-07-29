@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- Cards principales --}}
@php
/* Macro para filas de sub-estadísticas: punto de color + label + número alineado a la derecha */
function statRow(string $color, string $label, $value): string {
    return '<div style="display:flex;justify-content:space-between;align-items:center;
                        font-size:12px;color:var(--text-muted);margin-top:6px">
                <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;
                            background:' . $color . ';margin-right:6px;flex-shrink:0"></span>'
                    . e($label) . '</span>
                <span style="font-weight:600;color:var(--text)">' . e($value) . '</span>
            </div>';
}
@endphp

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:16px; margin-bottom:24px">

    {{-- Colegios --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid var(--accent); box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Colegios</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($totalSchools) }}</div>
        <div style="margin-top:10px">
            {!! statRow('#22c55e', 'activos',    $colegiosActivos)   !!}
            {!! statRow('#f59e0b', 'prospecto',  $colegiosProspecto) !!}
            {!! statRow('#94a3b8', 'inactivos',  $colegiosInactivos) !!}
        </div>
    </div>

    {{-- Directores --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid #8b5cf6; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Directores</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($totalDirectores) }}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:12px; line-height:1.5">
            Director General + Director de Nivel
        </div>
    </div>

    {{-- Docentes --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid #3b82f6; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Docentes</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($totalTeachers) }}</div>
        <div style="margin-top:10px">
            {!! statRow('#3b82f6', 'ELT', $docentesELT) !!}
            {!! statRow('#22c55e', 'ECA', $docentesECA) !!}
        </div>
    </div>

    {{-- Alumnos --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid #8b5cf6; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Alumnos</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($totalStudents) }}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:12px">registrados en MEE</div>
    </div>

    {{-- Tickets --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid #f59e0b; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Tickets</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($ticketsAbiertos + $ticketsEnProceso + $ticketsResueltos) }}</div>
        <div style="margin-top:10px">
            {!! statRow('#ef4444', 'abiertos',   $ticketsAbiertos)   !!}
            {!! statRow('#f59e0b', 'en proceso', $ticketsEnProceso)  !!}
            {!! statRow('#22c55e', 'resueltos',  $ticketsResueltos)  !!}
        </div>
    </div>

    {{-- Visitas --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid #0ea5e9; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Visitas</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($totalVisitas) }}</div>
        <div style="margin-top:10px">
            {!! statRow('#f59e0b', 'pendientes', $visitasPendientes)                    !!}
            {!! statRow('#22c55e', 'realizadas', $totalVisitas - $visitasPendientes)    !!}
        </div>
    </div>

    {{-- Admins MEE --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid #10b981; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Admins MEE</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($totalAdminsMee) }}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:12px">Administradores MEE registrados</div>
    </div>

    {{-- Colegios Entregados --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid #16a34a; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Colegios Entregados</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($colegiosEntregados) }}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:12px; line-height:1.5">
            Progreso general al 100%
        </div>
    </div>

    {{-- Resurtidos --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid #f97316; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Resurtidos</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($totalResurtidos) }}</div>
        <div style="margin-top:12px">
            @if(auth()->user()->hasAnyRole(['admin', 'consultor_digital']))
            <a href="{{ route('reportes.resurtidos') }}"
               style="display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600;
                      color:#f97316; text-decoration:none; padding:4px 10px;
                      border:1px solid #f97316; border-radius:6px; transition:all 0.2s"
               onmouseover="this.style.background='#f97316';this.style.color='#fff'"
               onmouseout="this.style.color='#f97316';this.style.background='transparent'">
                📥 Descargar reporte
            </a>
            @else
            <span style="font-size:11px; color:var(--text-muted)">actualizaciones de bundles</span>
            @endif
        </div>
    </div>

</div>

{{-- Botón Reporte General --}}
<div style="display:flex; justify-content:flex-end; margin-bottom:20px">
    <a href="#" onclick="iniciarDescargaReporte(event)"
       style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px;
              background:#1d4ed8; color:#fff; border-radius:9px; text-decoration:none;
              font-size:13px; font-weight:600; transition:background 0.2s;
              box-shadow:0 2px 6px rgba(29,78,216,0.3)"
       onmouseover="this.style.background='#1e40af'"
       onmouseout="this.style.background='#1d4ed8'">
        📊 Exportar Reporte
    </a>
</div>

{{-- ── Colegios por Nivel (full width, mismo orden y colores que Alumnos SI) ── --}}
@if($colegiosPorNivel->isNotEmpty())
@php
    $nivelColorMap = [
        'maternal'     => '#f59e0b',
        'preescolar'   => '#8b5cf6',
        'primaria'     => '#3b82f6',
        'secundaria'   => '#10b981',
        'preparatoria' => '#E2231A',
        'licenciatura' => '#0ea5e9',
    ];
    $nivelOrden = ['maternal','preescolar','primaria','secundaria','preparatoria','licenciatura'];
    $colegiosPorNivelOrdenados = $colegiosPorNivel->sortBy(function($n) use ($nivelOrden) {
        $idx = array_search(strtolower($n['name']), $nivelOrden);
        return $idx === false ? 99 : $idx;
    });
@endphp
<div style="margin-bottom:24px">
    <div style="display:flex; align-items:baseline; gap:10px; margin-bottom:14px">
        <span style="font-family:'Bricolage Grotesque',sans-serif; font-size:18px; font-weight:600; color:var(--text)">
            🏫 Colegios por Nivel
        </span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $totalSchools }} en total</span>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:16px">
        @foreach($colegiosPorNivelOrdenados as $nivel)
        @php $color = $nivelColorMap[strtolower($nivel['name'])] ?? '#94a3b8'; @endphp
        <div style="background:var(--surface); border-radius:14px; padding:18px 20px;
                    border-top:3px solid {{ $color }}; box-shadow:0 1px 3px rgba(0,0,0,0.06)">
            <div style="font-size:11px; font-weight:600; letter-spacing:.5px;
                         text-transform:uppercase; color:var(--text-muted); margin-bottom:8px">
                {{ $nivel['name'] }}
            </div>
            <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:30px; font-weight:700;
                        color:{{ $color }}; line-height:1">{{ $nivel['total'] }}</div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:6px">
                {{ $totalSchools > 0 ? round($nivel['total'] / $totalSchools * 100) : 0 }}% colegios
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Visitas pendientes alert --}}
@if($visitasPendientes > 0)
<div style="background:#fef3c7; border:1px solid #f59e0b; border-radius:10px;
            padding:14px 20px; margin-bottom:24px; display:flex; align-items:center; gap:10px">
    <span style="font-size:18px">📅</span>
    <span style="font-size:14px; color:#92400e">
        Tienes <strong>{{ $visitasPendientes }} visita(s) pendiente(s)</strong> por realizar.
    </span>
    <a href="{{ route('schools.index') }}" style="margin-left:auto; font-size:13px; color:#92400e; font-weight:600">
        Ver colegios →
    </a>
</div>
@endif


{{--Cards para alumnos SI --}}
@php
    // $conteoNiveles viene del controlador, ya filtrado por rol
    $nivelesSI = [
        ['nombre' => 'Maternal',     'icono' => '🍼', 'color' => '#f59e0b', 'alias' => ['maternal']],
        ['nombre' => 'Preescolar',   'icono' => '🧸', 'color' => '#8b5cf6', 'alias' => ['preescolar','prescolar','kinder','kínder']],
        ['nombre' => 'Primaria',     'icono' => '✏️', 'color' => '#3b82f6', 'alias' => ['primaria']],
        ['nombre' => 'Secundaria',   'icono' => '📐', 'color' => '#10b981', 'alias' => ['secundaria','secu']],
        ['nombre' => 'Preparatoria', 'icono' => '🎓', 'color' => '#E2231A', 'alias' => ['preparatoria','bachillerato','prepa','bach']],
        ['nombre' => 'Licenciatura', 'icono' => '🏛️', 'color' => '#0ea5e9', 'alias' => ['licenciatura','universidad','lic']],
    ];

    $totalAlumnosSI = $conteoNiveles->sum();
@endphp

<div style="margin-bottom:24px">
    <div style="display:flex; align-items:baseline; gap:10px; margin-bottom:14px">
        <span style="font-family:'Bricolage Grotesque',sans-serif; font-size:18px; font-weight:600; color:var(--text)">
            🎒 Alumnos SI
        </span>
        <span style="font-size:13px; color:var(--text-muted)">{{ number_format($totalAlumnosSI) }} en total</span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:16px">
        @foreach($nivelesSI as $n)
            @php $count = collect($n['alias'])->sum(fn($a) => $conteoNiveles[$a] ?? 0); @endphp
            <div style="background:var(--surface); border-radius:14px; padding:18px 20px;
                        border-top:3px solid {{ $n['color'] }}; box-shadow:0 1px 3px rgba(0,0,0,0.06)">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
                    <span style="font-size:11px; font-weight:600; letter-spacing:.5px;
                                 text-transform:uppercase; color:var(--text-muted)">{{ $n['nombre'] }}</span>
                    <span style="font-size:18px">{{ $n['icono'] }}</span>
                </div>
                <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:30px; font-weight:700;
                            color:{{ $n['color'] }}; line-height:1">{{ number_format($count) }}</div>
                <div style="font-size:11px; color:var(--text-muted); margin-top:6px">alumnos</div>
            </div>
        @endforeach
    </div>
</div>


{{-- ── Colegios por Servicio (full width, 2 cols si >5) ── --}}
@if($colegiosPorServicio->isNotEmpty())
@php $dosColumnas = $colegiosPorServicio->count() > 5; @endphp
<div style="margin-bottom:24px">
    <div style="display:flex; align-items:baseline; gap:10px; margin-bottom:14px; flex-wrap:wrap">
        <span style="font-family:'Bricolage Grotesque',sans-serif; font-size:18px; font-weight:600; color:var(--text)">
            📦 Colegios por Servicio
        </span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $totalSchools }} en total</span>
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('configuracion.servicios.index') }}"
           style="margin-left:auto; font-size:11.5px; color:var(--accent); text-decoration:none; font-weight:500">
            ⚙️ Gestionar servicios →
        </a>
        @endif
    </div>

    <div style="background:var(--surface); border-radius:14px; padding:6px 20px;
                border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,0.06)">
        <div style="{{ $dosColumnas ? 'display:grid; grid-template-columns:repeat(2,1fr); gap:0 40px;' : '' }}">
            @foreach($colegiosPorServicio as $srvIdx => $srv)
            @php $pct = $totalSchools > 0 ? round($srv['total'] / $totalSchools * 100) : 0; @endphp
            <div style="display:flex; align-items:center; gap:10px; padding:10px 0;
                        border-bottom:1px solid var(--border){{ $loop->last && !$dosColumnas ? ';border-bottom:none' : '' }}">

                {{-- Dot --}}
                <span style="width:9px; height:9px; border-radius:50%; background:{{ $srv['color'] }};
                             flex-shrink:0; box-shadow:0 0 0 2px {{ $srv['color'] }}28"></span>

                {{-- Icono --}}
                <span style="font-size:15px; line-height:1; flex-shrink:0">{{ $srv['icon'] }}</span>

                {{-- Nombre --}}
                <span style="flex:1; font-size:13px; font-weight:600; color:var(--text);
                             white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0"
                      title="{{ $srv['name'] }}">{{ $srv['name'] }}</span>

                {{-- Barra --}}
                <div style="width:80px; height:5px; border-radius:99px; background:var(--border);
                            flex-shrink:0; overflow:hidden">
                    <div style="width:{{ $pct }}%; height:100%; border-radius:99px;
                                background:{{ $srv['color'] }}; transition:width .4s ease"></div>
                </div>

                {{-- % --}}
                <span style="font-size:11px; color:var(--text-muted); width:30px; text-align:right;
                             flex-shrink:0; font-variant-numeric:tabular-nums">{{ $pct }}%</span>

                {{-- Conteo --}}
                <span style="font-size:15px; font-weight:800; color:{{ $srv['color'] }};
                             min-width:22px; text-align:right; flex-shrink:0;
                             font-variant-numeric:tabular-nums">{{ $srv['total'] }}</span>

                {{-- Ojo --}}
                @if($srv['total'] > 0)
                <button onclick="abrirModalServicio({{ $srvIdx }})"
                        title="Ver colegios"
                        style="background:none; border:none; cursor:pointer; padding:4px;
                               color:{{ $srv['color'] }}; opacity:0.5; transition:opacity .15s;
                               line-height:0; flex-shrink:0; border-radius:6px"
                        onmouseover="this.style.opacity='1';this.style.background='{{ $srv['color'] }}18'"
                        onmouseout="this.style.opacity='0.5';this.style.background='none'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                    </svg>
                </button>
                @else
                <span style="width:23px; flex-shrink:0"></span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Modales por servicio --}}
@foreach($colegiosPorServicio as $srvIdx => $srv)
@if($srv['total'] > 0)
<div id="modal-servicio-{{ $srvIdx }}"
     style="display:none; position:fixed; inset:0; z-index:1000; align-items:center; justify-content:center;
            background:rgba(0,0,0,0.45); padding:20px">
    <div style="background:var(--surface); border-radius:14px; width:100%; max-width:520px;
                max-height:80vh; display:flex; flex-direction:column;
                box-shadow:0 20px 60px rgba(0,0,0,0.25)">
        <div style="padding:20px 24px 16px; border-bottom:1px solid var(--border);
                    display:flex; align-items:center; gap:10px; flex-shrink:0">
            <span style="font-size:22px">{{ $srv['icon'] }}</span>
            <div style="flex:1">
                <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:16px;
                            font-weight:700; color:var(--text)">{{ $srv['name'] }}</div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:2px">
                    {{ $srv['total'] }} {{ $srv['total'] === 1 ? 'colegio' : 'colegios' }} con este servicio
                </div>
            </div>
            <button onclick="cerrarModalServicio({{ $srvIdx }})"
                    style="background:none; border:none; cursor:pointer; font-size:20px;
                           color:var(--text-muted); line-height:1; padding:4px">×</button>
        </div>
        <div style="overflow-y:auto; padding:16px 24px 20px; flex:1">
            @foreach($srv['schools'] as $sch)
            <a href="{{ route('schools.show', $sch->id) }}"
               style="display:flex; align-items:center; gap:10px; padding:10px 12px;
                      border-radius:8px; text-decoration:none; transition:background 0.12s;
                      border-bottom:1px solid var(--border)"
               onmouseover="this.style.background='{{ $srv['color'] }}18'"
               onmouseout="this.style.background='transparent'">
                <span style="width:8px; height:8px; border-radius:50%; background:{{ $srv['color'] }}; flex-shrink:0"></span>
                <span style="font-size:13.5px; font-weight:600; color:var(--text); flex:1">{{ $sch->name }}</span>
                @if($sch->state || $sch->city)
                <span style="font-size:11.5px; color:var(--text-muted)">{{ $sch->state ?? $sch->city }}</span>
                @endif
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="{{ $srv['color'] }}" viewBox="0 0 16 16" style="flex-shrink:0;opacity:0.6">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif
@endforeach

<script>
function abrirModalServicio(idx) {
    var m = document.getElementById('modal-servicio-' + idx);
    if (!m) return;
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function cerrarModalServicio(idx) {
    var m = document.getElementById('modal-servicio-' + idx);
    if (m) m.style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="modal-servicio-"]').forEach(function(m) { m.style.display = 'none'; });
        document.body.style.overflow = '';
    }
});
document.addEventListener('click', function(e) {
    if (e.target.matches('[id^="modal-servicio-"]')) {
        e.target.style.display = 'none';
        document.body.style.overflow = '';
    }
});
</script>
@endif

{{-- Mapa + Panel derecho --}}
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px; margin-bottom:24px">

    {{-- Mapa --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">🗺️ Colegios por estado</span>
            <span style="font-size:12px; color:var(--text-muted)">Clic para filtrar</span>
        </div>
        <div class="card-body" style="padding:16px">
            <div id="mapa-mexico" style="width:100%; height:320px; position:relative"></div>
        </div>
    </div>

    {{-- Panel derecho - Zonas --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">📍 Colegios por zona</span>
            <a href="{{ route('reportes.zonas') }}"
               style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px;
                      background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;
                      border-radius:8px; font-size:12px; font-weight:600; text-decoration:none;
                      transition:all 0.2s"
               onmouseover="this.style.background='#16a34a';this.style.color='#fff'"
               onmouseout="this.style.background='#f0fdf4';this.style.color='#16a34a'">
                📊 Reporte Zonas
            </a>
        </div>
        <div class="card-body" style="padding:16px">
            @php
                $zonasConfig = [
                    'Pacífico' => ['icon' => '🟢', 'color' => '#10b981', 'estados' => 'BC · BCS · Sonora · Sinaloa · Nayarit · Jalisco · Colima'],
                    'Norte'    => ['icon' => '🔵', 'color' => '#3b82f6', 'estados' => 'Chihuahua · Coahuila · Nuevo León · Tamaulipas · Durango'],
                    'Centro'   => ['icon' => '🟣', 'color' => '#8b5cf6', 'estados' => 'Hidalgo · Tlaxcala · Edo. Méx. · CDMX · Morelos · Guerrero'],
                    'Bajío'    => ['icon' => '🟠', 'color' => '#f97316', 'estados' => 'Guanajuato · Querétaro · Ags. · S.L.P. · Michoacán · Zacatecas'],
                    'Sureste'  => ['icon' => '🔴', 'color' => '#E2231A', 'estados' => 'Veracruz · Oaxaca · Puebla · Chiapas · Tabasco · Campeche · Yucatán · Q.Roo'],
                ];
            @endphp
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
                @foreach($zonasConfig as $nombre => $cfg)
                <div style="border:1px solid var(--border); border-radius:10px; padding:14px;
                            border-left:4px solid {{ $cfg['color'] }}">
                    <div style="font-size:11px; font-weight:600; text-transform:uppercase;
                                letter-spacing:1px; color:var(--text-muted); margin-bottom:6px">
                        {{ $cfg['icon'] }} {{ $nombre }}
                    </div>
                    <div style="font-size:28px; font-weight:700; font-family:'Bricolage Grotesque',sans-serif;
                                color:{{ $cfg['color'] }}">
                        {{ $colegiosPorZona[$nombre] ?? 0 }}
                    </div>
                    <div style="font-size:10px; color:var(--text-muted); margin-top:4px; line-height:1.4">
                        {{ $cfg['estados'] }}
                    </div>
                </div>
                @endforeach

                {{-- Sin zona si hay colegios sin clasificar --}}
                @if(($colegiosPorZona['Sin zona'] ?? 0) > 0)
                <div style="border:1px solid var(--border); border-radius:10px; padding:14px;
                            border-left:4px solid #9ca3af; grid-column:1/-1">
                    <div style="font-size:11px; font-weight:600; text-transform:uppercase;
                                letter-spacing:1px; color:var(--text-muted); margin-bottom:6px">
                        ⚪ Sin zona asignada
                    </div>
                    <div style="font-size:28px; font-weight:700; font-family:'Bricolage Grotesque',sans-serif;
                                color:#6b7280">
                        {{ $colegiosPorZona['Sin zona'] }}
                    </div>
                    <div style="font-size:10px; color:var(--text-muted); margin-top:4px">
                        Estado no reconocido en el catálogo de zonas
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Filtros de colegios --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px; display:flex; gap:12px; flex-wrap:wrap; align-items:center">
        <input type="text" id="buscador-colegios" class="form-control"
               placeholder="🔍 Buscar por nombre, consultor o estado..."
               style="max-width:320px; flex:1; min-width:200px">

        <div style="display:flex; align-items:center; gap:8px; flex:1; min-width:200px">
            <span style="font-size:13px; color:var(--text-muted); white-space:nowrap">📚 Filtrar por serie:</span>
            <select id="filtro-series" class="form-control" style="max-width:260px">
                <option value="">Todas las series</option>
                @foreach($seriesDisponibles as $serie)
                <option value="{{ strtolower($serie) }}">{{ $serie }}</option>
                @endforeach
            </select>
        </div>

        <button onclick="limpiarFiltros()"
                style="padding:8px 14px; background:var(--surface2); border:1px solid var(--border);
                       border-radius:8px; font-size:13px; color:var(--text-muted); cursor:pointer;
                       transition:all 0.15s; white-space:nowrap"
                onmouseover="this.style.background='var(--border)'"
                onmouseout="this.style.background='var(--surface2)'">
            ✕ Limpiar filtros
        </button>

        <span id="conteo-resultados" style="font-size:13px; color:var(--text-muted); white-space:nowrap"></span>
    </div>
</div>

{{-- Cards de colegios --}}
<div id="colegios-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:16px">
    @forelse($schools as $school)
    @php
        $schoolSeries = $school->bundles->pluck('serie')->filter()->unique()->map(fn($s) => strtolower($s))->values()->toJson();
    @endphp
    <div class="school-card card" data-nombre="{{ strtolower($school->name) }}"
         data-consultor="{{ strtolower($school->schoolConsultants->where('role','digital')->first()?->consultant->user->name ?? '') }}"
         data-estado="{{ strtolower($school->state ?? $school->city ?? '') }}"
         data-series="{{ $schoolSeries }}"
         style="transition: all 0.2s; display:flex; flex-direction:column; min-height:280px;">
        <div class="card-header" style="padding:16px 20px">
            <div>
                <div style="font-family:'Bricolage Grotesque',sans-serif; font-weight:600; font-size:15px">
                    {{ $school->name }}
                </div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:2px">
                    {{ $school->state ?? $school->city ?? 'Sin estado' }}
                </div>
            </div>
            @if($school->status === 'activo')
                <span class="badge badge-success">Activo</span>
            @elseif($school->status === 'prospecto')
                <span class="badge badge-warning">Prospecto</span>
            @else
                <span class="badge badge-gray">Inactivo</span>
            @endif
        </div>
        <div class="card-body" style="padding:16px 20px; flex:1; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px">
                <span style="color:var(--text-muted)">Consultor Digital</span>
                <span style="font-weight:500">{{ $school->schoolConsultants->where('role','digital')->first()?->consultant->user->name ?? '—' }}</span>
            </div>

            @if($school->meeAdmins->count())
            <div style="margin-bottom:12px">
                @foreach($school->meeAdmins as $admin)
                <div style="display:flex; justify-content:space-between; font-size:12px;
                            padding:6px 10px; background:var(--surface2); border-radius:6px; margin-bottom:4px">
                    <span style="color:var(--text-muted)">🔐 {{ $admin->username }}</span>
                    <span style="font-family:monospace; color:var(--text-muted)">{{ $admin->password_plain }}</span>
                </div>
                @endforeach
            </div>
            @endif

            @php
                $totalProcesos = 0;
                $totalDone = 0;
                foreach($school->schoolLevels as $sl) {
                    $totalProcesos += $sl->processes->count();
                    $totalDone += $sl->processes->where('status', 'done')->count();
                }
                $pct = $totalProcesos > 0 ? round(($totalDone / $totalProcesos) * 100) : 0;
            @endphp

            <div style="margin-bottom:12px">
                <div style="display:flex; justify-content:space-between; font-size:12px;
                            color:var(--text-muted); margin-bottom:4px">
                    <span>Progreso general</span>
                    <span>{{ $pct }}%</span>
                </div>
                <div style="background:var(--surface2); border-radius:20px; height:6px; overflow:hidden">
                    <div style="height:100%; width:{{ $pct }}%;
                                background:{{ $pct == 100 ? '#10b981' : 'var(--accent)' }};
                                border-radius:20px"></div>
                </div>
            </div>

            <div style="display:flex; gap:4px; flex-wrap:wrap; margin-bottom:12px">
                @foreach($school->schoolLevels as $sl)
                    <span class="badge badge-info" style="font-size:11px">
                        {{ $sl->level->name ?? '' }}
                    </span>
                @endforeach
            </div>

            <a href="{{ route('schools.show', $school) }}"
               style="display:block; text-align:center; padding:8px; background:var(--accent);
                      color:#fff; border-radius:8px; text-decoration:none; font-size:13px;
                      font-weight:500; transition:background 0.2s; margin-top:auto;"
               onmouseover="this.style.background='#d63651'"
               onmouseout="this.style.background='var(--accent)'">
                IR →
            </a>
        </div>
    </div>
    @empty
    <div style="grid-column:1/-1; text-align:center; color:var(--text-muted); padding:60px">
        No hay colegios registrados.
        <a href="{{ route('schools.create') }}">Registra el primero</a>
    </div>
    @endforelse
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
<script>
const colegiosPorEstado = @json($colegiosPorEstado);

const normalizar = str => str?.toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ').trim();

// El GeoJSON llama "México" al polígono de Estado de México (no "Estado de México")
const aliasGeoJSON = {
    'estado de mexico': 'mexico',
    'edomex':           'mexico',
};

const estadosData = {};
Object.entries(colegiosPorEstado).forEach(([estado, total]) => {
    const dbKey  = normalizar(estado);
    const geoKey = aliasGeoJSON[dbKey] ?? dbKey;
    estadosData[geoKey] = { nombre: estado, total };
});

fetch('https://raw.githubusercontent.com/angelnmara/geojson/master/mexicoHigh.json')
    .then(r => r.json())
    .then(geojson => {
        const container = document.getElementById('mapa-mexico');
        const width     = container.offsetWidth;
        const height    = 320;

        const svg = d3.select('#mapa-mexico')
            .append('svg')
            .attr('width', width)
            .attr('height', height);

        const projection = d3.geoMercator()
            .fitSize([width, height], geojson);

        const path = d3.geoPath().projection(projection);

        const tooltip = d3.select('body')
            .append('div')
            .style('position', 'fixed')
            .style('background', '#181311')
            .style('color', '#fff')
            .style('padding', '8px 14px')
            .style('border-radius', '8px')
            .style('font-size', '13px')
            .style('pointer-events', 'none')
            .style('opacity', 0)
            .style('z-index', 9999);

        svg.selectAll('path')
            .data(geojson.features)
            .enter()
            .append('path')
            .attr('d', path)
            .attr('fill', d => {
                const nombre = normalizar(d.properties.name || d.properties.NAME || d.properties.estado);
                const data   = estadosData[nombre];
                if (!data) return '#e5e7eb';
                const intensity = Math.min(data.total / 5, 1);
                return d3.interpolateRgb('#fecaca', '#E2231A')(intensity);
            })
            .attr('stroke', '#fff')
            .attr('stroke-width', 0.8)
            .style('cursor', d => {
                const nombre = normalizar(d.properties.name || d.properties.NAME || d.properties.estado);
                return estadosData[nombre] ? 'pointer' : 'default';
            })
            .on('mouseover', function(event, d) {
                const nombre = normalizar(d.properties.name || d.properties.NAME || d.properties.estado);
                const data   = estadosData[nombre];
                d3.select(this).attr('stroke-width', 2).attr('stroke', '#E2231A');
                tooltip.style('opacity', 1)
                    .html(data
                        ? `<strong>${data.nombre}</strong><br>🏫 ${data.total} colegio(s)`
                        : `<strong>${d.properties.name || d.properties.NAME}</strong><br>Sin colegios`
                    );
            })
            .on('mousemove', function(event) {
                tooltip
                    .style('left', (event.clientX + 12) + 'px')
                    .style('top',  (event.clientY - 28) + 'px');
            })
            .on('mouseout', function() {
                d3.select(this).attr('stroke-width', 0.8).attr('stroke', '#fff');
                tooltip.style('opacity', 0);
            })
            .on('click', function(event, d) {
                const nombre = normalizar(d.properties.name || d.properties.NAME || d.properties.estado);
                const data   = estadosData[nombre];
                if (data) {
                    document.getElementById('buscador-colegios').value = data.nombre;
                    document.getElementById('buscador-colegios').dispatchEvent(new Event('input'));
                    document.getElementById('colegios-grid').scrollIntoView({ behavior: 'smooth' });
                }
            });

        svg.selectAll('text')
            .data(geojson.features.filter(d => {
                const nombre = normalizar(d.properties.name || d.properties.NAME || d.properties.estado);
                return estadosData[nombre];
            }))
            .enter()
            .append('text')
            .attr('transform', d => `translate(${path.centroid(d)})`)
            .attr('text-anchor', 'middle')
            .attr('dominant-baseline', 'central')
            .attr('font-size', '11px')
            .attr('font-weight', '700')
            .attr('fill', '#fff')
            .attr('pointer-events', 'none')
            .text(d => {
                const nombre = normalizar(d.properties.name || d.properties.NAME || d.properties.estado);
                return estadosData[nombre]?.total;
            });
    })
    .catch(() => {
        document.getElementById('mapa-mexico').innerHTML =
            '<p style="text-align:center; color:var(--text-muted); padding:40px">No se pudo cargar el mapa</p>';
    });

// Filtros de colegios (buscador + series)
function aplicarFiltros() {
    const query  = document.getElementById('buscador-colegios').value.toLowerCase().trim();
    const serie  = document.getElementById('filtro-series').value.toLowerCase().trim();
    const cards  = document.querySelectorAll('.school-card');
    let visibles = 0;

    cards.forEach(card => {
        const nombre    = card.dataset.nombre    || '';
        const consultor = card.dataset.consultor || '';
        const estado    = card.dataset.estado    || '';
        let seriesCard  = [];
        try { seriesCard = JSON.parse(card.dataset.series || '[]'); } catch(e) {}

        const matchTexto = !query || nombre.includes(query) || consultor.includes(query) || estado.includes(query);
        const matchSerie = !serie || seriesCard.includes(serie);

        if (matchTexto && matchSerie) {
            card.style.display = '';
            visibles++;
        } else {
            card.style.display = 'none';
        }
    });

    const conteo = document.getElementById('conteo-resultados');
    if (conteo) {
        conteo.textContent = (query || serie)
            ? `${visibles} colegio(s) encontrado(s)`
            : '';
    }
}

function limpiarFiltros() {
    document.getElementById('buscador-colegios').value = '';
    document.getElementById('filtro-series').value = '';
    aplicarFiltros();
}

document.getElementById('buscador-colegios').addEventListener('input', aplicarFiltros);
document.getElementById('filtro-series').addEventListener('change', aplicarFiltros);
</script>

{{-- ── Overlay: Generando Reporte ── --}}
<div id="reporte-overlay"
     style="display:none; position:fixed; inset:0; z-index:9999;
            background:rgba(0,0,0,0.65); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);
            align-items:center; justify-content:center">

    <div style="background:var(--surface); border-radius:20px; padding:44px 52px;
                text-align:center; box-shadow:0 30px 70px rgba(0,0,0,0.4);
                max-width:380px; width:90%; animation:reporteFadeIn .25s ease">

        <div style="width:60px; height:60px; border:5px solid var(--border);
                    border-top-color:#1d4ed8; border-radius:50%;
                    margin:0 auto 28px; animation:reporteSpin .85s linear infinite"></div>

        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:20px;
                    font-weight:700; color:var(--text); margin-bottom:10px; line-height:1.2">
            Generando Reporte Completo
        </div>

        <div style="font-size:14px; color:var(--text-muted); margin-bottom:20px">
            Un momento por favor<span id="reporte-dots"></span>
        </div>

        <div style="font-size:12px; color:var(--text-muted); opacity:.6;
                    border-top:1px solid var(--border); padding-top:16px; line-height:1.6">
            Este proceso puede tardar 1–3 minutos<br>dependiendo del número de alumnos registrados.
        </div>
    </div>
</div>

<style>
@keyframes reporteSpin  { to { transform: rotate(360deg); } }
@keyframes reporteFadeIn { from { opacity:0; transform:scale(.96); } to { opacity:1; transform:scale(1); } }
</style>

<script>
(function () {
    var overlay  = document.getElementById('reporte-overlay');
    var dotsEl   = document.getElementById('reporte-dots');
    var dotTimer = setInterval(function () {
        var n = ((dotsEl.textContent.length + 1) % 4);
        dotsEl.textContent = '.'.repeat(n);
    }, 500);

    window.iniciarDescargaReporte = function (e) {
        e.preventDefault();

        // Borrar cookie anterior
        document.cookie = 'download_complete=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';

        // Mostrar overlay
        overlay.style.display = 'flex';

        // Iframe oculto: la descarga corre en segundo plano y el JS de esta
        // página nunca se interrumpe (window.location.href puede matar el setInterval)
        var iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = '{{ route("reportes.general") }}';
        document.body.appendChild(iframe);

        // Polling: detectar la cookie que pone el servidor al terminar
        var pollId = setInterval(function () {
            if (document.cookie.indexOf('download_complete=1') !== -1) {
                clearInterval(pollId);
                clearTimeout(safetyId);
                overlay.style.display = 'none';
                document.cookie = 'download_complete=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
                if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
            }
        }, 800);

        // Seguridad: ocultar a los 5 min aunque falle
        var safetyId = setTimeout(function () {
            clearInterval(pollId);
            overlay.style.display = 'none';
            if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
        }, 300000);
    };
})();
</script>

@endsection
