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

    {{-- Contraseñas personalizadas --}}
    <div style="background:var(--surface); border-radius:12px; padding:18px 20px;
                border-left:4px solid #0891b2; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">Personalización Colegio</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:34px; font-weight:800;
                    color:var(--text); line-height:1">{{ number_format($colegiosCustomPasswords) }}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:12px; line-height:1.5">
            Usuarios y contraseñas personalizadas
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


{{-- Botón Reporte General --}}
<div style="display:flex; justify-content:flex-end; margin-bottom:20px; margin-top:-8px">
    <a href="{{ route('reportes.general') }}"
       style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px;
              background:#1d4ed8; color:#fff; border-radius:9px; text-decoration:none;
              font-size:13px; font-weight:600; transition:background 0.2s;
              box-shadow:0 2px 6px rgba(29,78,216,0.3)"
       onmouseover="this.style.background='#1e40af'"
       onmouseout="this.style.background='#1d4ed8'">
        📊 Exportar Reporte
    </a>
</div>

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
         style="transition: all 0.2s; display:flex; flex-direction:column;">
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

const estadosData = {};
Object.entries(colegiosPorEstado).forEach(([estado, total]) => {
    estadosData[normalizar(estado)] = { nombre: estado, total };
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

@endsection
