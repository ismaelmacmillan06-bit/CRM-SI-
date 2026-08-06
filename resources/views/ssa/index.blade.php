@extends('layouts.app')
@section('title', 'Calendario SSA')

@section('content')
<style>
    /* ── Stats ─────────────────────────────────────────────────── */
    .ssa-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .ssa-stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        position: relative;
    }
    .ssa-stat-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .ssa-stat-num { font-size: 24px; font-weight: 700; line-height: 1; color: var(--text); }
    .ssa-stat-lbl { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
    .ssa-eye-btn {
        position: absolute; top: 10px; right: 10px;
        background: none; border: none; cursor: pointer;
        font-size: 15px; padding: 4px 6px; border-radius: 6px;
        color: var(--text-muted); line-height: 1;
        transition: background 0.15s, color 0.15s;
    }
    .ssa-eye-btn:hover { background: var(--surface2); color: var(--text); }

    /* ── Chips de fecha ─────────────────────────────────────────── */
    .fecha-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px; border-radius: 20px;
        font-size: 12px; font-weight: 500;
        cursor: pointer; border: none;
        transition: opacity 0.15s, transform 0.1s;
        margin-bottom: 4px; white-space: nowrap;
    }
    .fecha-chip:hover  { opacity: 0.82; transform: translateY(-1px); }
    .fecha-chip:active { transform: translateY(0); }

    .chip-pendiente  { background: #fef3c7; color: #92400e; }
    .chip-confirmado { background: #dbeafe; color: #1e40af; }
    .chip-realizado  { background: #d1fae5; color: #065f46; }
    .chip-cancelado  { background: #f3f4f6; color: #9ca3af; text-decoration: line-through; }
    .chip-vencida    { background: #fee2e2; color: #991b1b; }

    .dias-badge {
        font-size: 10px; font-weight: 700;
        padding: 1px 6px; border-radius: 8px;
        background: rgba(0,0,0,0.10); letter-spacing: 0.2px;
    }

    /* Indicador "hoy" en fila */
    .hoy-tag {
        font-size: 10px; font-weight: 700;
        padding: 2px 7px; border-radius: 10px;
        background: #fef08a; color: #713f12;
        flex-shrink: 0;
    }

    .btn-agregar-cap {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 3px 9px; border-radius: 20px;
        font-size: 11px; font-weight: 600;
        cursor: pointer; border: 1.5px dashed;
        background: transparent; transition: all 0.15s;
        margin-bottom: 4px;
    }
    .btn-agregar-eca { border-color: #93c5fd; color: #3b82f6; }
    .btn-agregar-eca:hover { background: #eff6ff; }
    .btn-agregar-elt { border-color: #c4b5fd; color: #7c3aed; }
    .btn-agregar-elt:hover { background: #f5f3ff; }

    .th-eca { color: #2563eb !important; }
    .th-elt { color: #7c3aed !important; }

    /* ── Búsqueda ───────────────────────────────────────────────── */
    .ssa-search {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 20px;
    }
    .ssa-search-input {
        flex: 1; padding: 10px 14px;
        border: 1px solid var(--border); border-radius: 8px;
        font-size: 14px; font-family: 'Inter', sans-serif;
        color: var(--text); background: var(--surface); outline: none;
    }
    .ssa-search-input:focus { border-color: var(--accent); }
    .ssa-filter-select {
        padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px;
        font-size: 13px; font-family: 'Inter', sans-serif;
        color: var(--text); background: var(--surface);
        outline: none; cursor: pointer;
    }
    .ssa-filter-select:focus { border-color: var(--accent); }

    /* ── Modal ──────────────────────────────────────────────────── */
    .ssa-modal-bg {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.55); z-index: 1500;
        align-items: center; justify-content: center;
    }
    .ssa-modal-bg.open { display: flex; }
    .ssa-modal {
        background: var(--surface);
        border-radius: 16px; padding: 32px;
        width: 100%; max-width: 500px;
        box-shadow: 0 20px 60px rgba(0,0,0,.3);
        position: relative; max-height: 90vh; overflow-y: auto;
    }
    .ssa-modal-title {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 18px; font-weight: 700; margin-bottom: 20px;
    }
    .ssa-modal-close {
        position: absolute; top: 20px; right: 20px;
        background: var(--surface2); border: none;
        width: 32px; height: 32px; border-radius: 50%;
        cursor: pointer; font-size: 16px;
        display: flex; align-items: center; justify-content: center;
        color: var(--text-muted); transition: background 0.15s;
    }
    .ssa-modal-close:hover { background: var(--border); color: var(--text); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    .tipo-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 20px;
        font-size: 12px; font-weight: 600; margin-bottom: 18px;
    }
    .tipo-badge-eca { background: #dbeafe; color: #1e40af; }
    .tipo-badge-elt { background: #ede9fe; color: #5b21b6; }

    /* Modal Hoy */
    .hoy-item {
        display: flex; align-items: center; gap: 12px;
        padding: 13px 0;
        border-bottom: 1px solid var(--border);
    }
    .hoy-item:last-child { border-bottom: none; }
    .hoy-tipo-pill {
        font-size: 11px; font-weight: 700;
        padding: 3px 9px; border-radius: 20px;
        flex-shrink: 0;
    }
    .hoy-tipo-eca { background: #dbeafe; color: #1e40af; }
    .hoy-tipo-elt { background: #ede9fe; color: #5b21b6; }

    /* Sin colegios */
    .ssa-no-schools {
        text-align: center; padding: 60px 20px; color: var(--text-muted);
    }
    .ssa-no-schools div:first-child { font-size: 48px; margin-bottom: 12px; }

    /* ── Tabs ──────────────────────────────────────────────────── */
    .ssa-tab-btn {
        padding: 9px 20px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text-muted);
        font-size: 13px; font-weight: 500;
        cursor: pointer; transition: all 0.15s;
        font-family: 'Inter', sans-serif;
    }
    .ssa-tab-btn:hover { background: var(--surface2); color: var(--text); }
    .ssa-tab-btn.active {
        background: var(--accent); color: #fff;
        border-color: var(--accent);
    }

    /* ── Calendario físico ──────────────────────────────────────── */
    .cal-weekdays {
        display: grid; grid-template-columns: repeat(7, 1fr);
        background: var(--surface2);
        border-bottom: 2px solid var(--border);
    }
    .cal-weekday {
        padding: 10px 8px; text-align: center;
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.6px;
        color: var(--text-muted);
    }
    .cal-grid {
        display: grid; grid-template-columns: repeat(7, 1fr);
    }
    .cal-cell {
        min-height: 108px; padding: 7px 8px;
        border-right: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        background: var(--surface);
        vertical-align: top;
    }
    .cal-cell:nth-child(7n) { border-right: none; }
    .cal-cell.cal-empty { background: var(--surface2); }
    .cal-cell.cal-hoy { background: #eff6ff; }
    .cal-day-num {
        display: flex; justify-content: flex-end;
        margin-bottom: 5px;
    }
    .cal-day-num span {
        font-size: 12px; font-weight: 600;
        color: var(--text-muted);
        width: 22px; height: 22px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        line-height: 1;
    }
    .cal-day-num span.hoy-circle {
        background: var(--accent); color: #fff; font-weight: 700;
    }
    .cal-chip-cal {
        display: flex; align-items: center; gap: 4px;
        padding: 2px 7px; border-radius: 4px;
        font-size: 10px; font-weight: 500;
        margin-bottom: 3px; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
        max-width: 100%;
    }
    .cal-chip-eca { background: #2563eb; color: #fff; }
    .cal-chip-elt { background: #7c3aed; color: #fff; }
    .cal-more {
        font-size: 10px; color: var(--text-muted);
        padding: 1px 2px; font-style: italic;
    }
    .cal-leyenda-dot {
        width: 12px; height: 12px; border-radius: 3px;
        display: inline-block; flex-shrink: 0;
    }
    .cal-eye-btn {
        display: block; width: 100%;
        margin-top: 5px; padding: 3px 6px;
        background: none; border: none; cursor: pointer;
        font-size: 11px; font-weight: 500;
        color: var(--text-muted); border-radius: 4px;
        text-align: left; transition: background 0.12s, color 0.12s;
        font-family: 'Inter', sans-serif;
    }
    .cal-eye-btn:hover { background: var(--surface2); color: var(--text); }

    @media (max-width: 900px) {
        .ssa-stats { grid-template-columns: repeat(2, 1fr); }
        .form-row  { grid-template-columns: 1fr; }
        .cal-cell  { min-height: 70px; }
        .cal-weekday { font-size: 10px; }
    }
    @media (max-width: 600px) {
        .cal-chip-cal { font-size: 9px; padding: 1px 4px; }
        .cal-cell { min-height: 55px; padding: 4px; }
    }
</style>

{{-- ── Stats ─────────────────────────────────────────────────────── --}}
<div class="ssa-stats">
    <div class="ssa-stat-card">
        <div class="ssa-stat-icon" style="background:#eff6ff">🏫</div>
        <div>
            <div class="ssa-stat-num">{{ $stats['total_escuelas'] }}</div>
            <div class="ssa-stat-lbl">Colegios asignados</div>
        </div>
    </div>
    <div class="ssa-stat-card">
        <div class="ssa-stat-icon" style="background:#dbeafe">📘</div>
        <div>
            <div class="ssa-stat-num" style="color:#2563eb">{{ $stats['eca_proximas'] }}</div>
            <div class="ssa-stat-lbl">Cap. ECA próximas</div>
        </div>
        <button class="ssa-eye-btn" onclick="abrirModalLista('modal-eca')" title="Ver ECA próximas">👁</button>
    </div>
    <div class="ssa-stat-card">
        <div class="ssa-stat-icon" style="background:#ede9fe">📗</div>
        <div>
            <div class="ssa-stat-num" style="color:#7c3aed">{{ $stats['elt_proximas'] }}</div>
            <div class="ssa-stat-lbl">Cap. ELT próximas</div>
        </div>
        <button class="ssa-eye-btn" onclick="abrirModalLista('modal-elt')" title="Ver ELT próximas">👁</button>
    </div>
    <div class="ssa-stat-card">
        <div class="ssa-stat-icon" style="background:#d1fae5">✅</div>
        <div>
            <div class="ssa-stat-num">{{ $stats['realizadas'] }}</div>
            <div class="ssa-stat-lbl">Realizadas</div>
        </div>
        <button class="ssa-eye-btn" onclick="abrirModalLista('modal-realizadas')" title="Ver realizadas">👁</button>
    </div>
    {{-- Card Hoy con ojito --}}
    <div class="ssa-stat-card">
        <div class="ssa-stat-icon" style="background:#fef9c3">🗓️</div>
        <div>
            <div class="ssa-stat-num" style="color:#d97706">{{ $stats['hoy'] }}</div>
            <div class="ssa-stat-lbl">Capacitaciones hoy</div>
        </div>
        <button class="ssa-eye-btn" onclick="abrirModalHoy()" title="Ver capacitaciones de hoy">
            👁
        </button>
    </div>
</div>

{{-- ── Tabs ────────────────────────────────────────────────────────── --}}
<div style="display:flex; gap:8px; margin-bottom:20px">
    <button id="tab-btn-lista" class="ssa-tab-btn active" onclick="cambiarTab('lista')">
        📋 Lista
    </button>
    <button id="tab-btn-calendario" class="ssa-tab-btn" onclick="cambiarTab('calendario')">
        📅 Calendario mensual
    </button>
</div>

<div id="tab-lista">
{{-- ── Búsqueda ──────────────────────────────────────────────────── --}}
<div class="ssa-search">
    <input type="text" id="ssa-buscar" class="ssa-search-input"
           placeholder="🔍  Buscar colegio..." oninput="filtrarEscuelas()">
    <select id="ssa-filtro" class="ssa-filter-select" onchange="filtrarEscuelas()">
        <option value="">Todos los colegios</option>
        <option value="hoy">Con capacitación hoy</option>
        <option value="sin-eca">Sin cap. ECA programada</option>
        <option value="sin-elt">Sin cap. ELT programada</option>
        <option value="proximas-eca">Con ECA próxima</option>
        <option value="proximas-elt">Con ELT próxima</option>
    </select>
</div>

{{-- ── Tabla ─────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">📅 Capacitaciones por Colegio</span>
        <span style="font-size:12px; color:var(--text-muted)">
            {{ $schools->count() }} {{ $schools->count() === 1 ? 'colegio' : 'colegios' }}
        </span>
    </div>

    @if($schools->isEmpty())
        <div class="ssa-no-schools">
            <div>📋</div>
            <p>No tienes colegios asignados en este rol.</p>
        </div>
    @else
    <div class="table-scroll">
        <table class="table" id="ssa-tabla">
            <thead>
                <tr>
                    <th>Colegio</th>
                    <th>Estado</th>
                    <th>Consultor Digital</th>
                    <th class="th-eca">📘 Capacitación ECA</th>
                    <th class="th-elt">📗 Capacitación ELT</th>
                </tr>
            </thead>
            <tbody>
                @php $hoy = today(); @endphp
                @foreach($schools as $school)
                @php
                    $capsEca = $school->ssaCapacitaciones->where('tipo', 'eca')->sortBy('fecha');
                    $capsElt = $school->ssaCapacitaciones->where('tipo', 'elt')->sortBy('fecha');
                    $tieneHoy       = $school->ssaCapacitaciones->filter(fn($c) => $c->fecha->isToday())->isNotEmpty();
                    $tieneEcaProxima = $capsEca->whereIn('estatus', ['pendiente','confirmado'])
                                               ->where('fecha', '>=', $hoy)->isNotEmpty();
                    $tieneEltProxima = $capsElt->whereIn('estatus', ['pendiente','confirmado'])
                                               ->where('fecha', '>=', $hoy)->isNotEmpty();
                @endphp
                <tr class="ssa-fila"
                    data-name="{{ strtolower($school->name) }}"
                    data-hoy="{{ $tieneHoy ? '1' : '0' }}"
                    data-tiene-eca="{{ $capsEca->isNotEmpty() ? '1' : '0' }}"
                    data-tiene-elt="{{ $capsElt->isNotEmpty() ? '1' : '0' }}"
                    data-proxima-eca="{{ $tieneEcaProxima ? '1' : '0' }}"
                    data-proxima-elt="{{ $tieneEltProxima ? '1' : '0' }}">

                    <td>
                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap">
                            <span style="font-weight:500">{{ $school->name }}</span>
                            @if($tieneHoy)
                                <span class="hoy-tag">📅 Hoy</span>
                            @endif
                        </div>
                        @if($school->nexus_id)
                            <div style="font-size:11px; color:var(--text-muted)">
                                Nexus {{ $school->nexus_id }}
                            </div>
                        @endif
                    </td>

                    <td style="font-size:13px; color:var(--text-muted); white-space:nowrap">
                        {{ $school->state ?? '—' }}
                    </td>

                    <td style="font-size:13px; white-space:nowrap">
                        @if(isset($consultoresDigitales[$school->id]))
                            <span style="color:var(--text)">
                                {{ $consultoresDigitales[$school->id]->nombre }}
                            </span>
                        @else
                            <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>

                    {{-- ── Columna ECA ── --}}
                    <td>
                        <div style="display:flex; flex-wrap:wrap; gap:4px; align-items:center">
                            @foreach($capsEca as $cap)
                                @php [$chipClass, $fechaLabel, $diasLabel] = ssaChipData($cap, $hoy); @endphp
                                <button type="button"
                                    class="fecha-chip {{ $chipClass }}"
                                    onclick="abrirEditar({{ $cap->id }},'{{ $cap->fecha->format('Y-m-d') }}','{{ $cap->hora ?? '' }}','{{ $cap->tipo }}','{{ $cap->estatus }}',{{ json_encode($cap->notas ?? '') }})"
                                    title="{{ ucfirst($cap->estatus) }}{{ $cap->notas ? ' — '.$cap->notas : '' }}">
                                    {{ $fechaLabel }}
                                    @if($diasLabel)<span class="dias-badge">{{ $diasLabel }}</span>@endif
                                </button>
                            @endforeach
                            <button type="button" class="btn-agregar-cap btn-agregar-eca"
                                    onclick="abrirNueva({{ $school->id }},{{ json_encode($school->name) }},'eca')">
                                + ECA
                            </button>
                        </div>
                    </td>

                    {{-- ── Columna ELT ── --}}
                    <td>
                        <div style="display:flex; flex-wrap:wrap; gap:4px; align-items:center">
                            @foreach($capsElt as $cap)
                                @php [$chipClass, $fechaLabel, $diasLabel] = ssaChipData($cap, $hoy); @endphp
                                <button type="button"
                                    class="fecha-chip {{ $chipClass }}"
                                    onclick="abrirEditar({{ $cap->id }},'{{ $cap->fecha->format('Y-m-d') }}','{{ $cap->hora ?? '' }}','{{ $cap->tipo }}','{{ $cap->estatus }}',{{ json_encode($cap->notas ?? '') }})"
                                    title="{{ ucfirst($cap->estatus) }}{{ $cap->notas ? ' — '.$cap->notas : '' }}">
                                    {{ $fechaLabel }}
                                    @if($diasLabel)<span class="dias-badge">{{ $diasLabel }}</span>@endif
                                </button>
                            @endforeach
                            <button type="button" class="btn-agregar-cap btn-agregar-elt"
                                    onclick="abrirNueva({{ $school->id }},{{ json_encode($school->name) }},'elt')">
                                + ELT
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
</div>{{-- /tab-lista --}}

@php
$capsCalendario = $schools->flatMap(function ($school) {
    return $school->ssaCapacitaciones
        ->where('estatus', 'confirmado')
        ->map(function ($cap) use ($school) {
            return [
                'fecha'   => $cap->fecha->format('Y-m-d'),
                'tipo'    => $cap->tipo,
                'colegio' => $school->name,
                'hora'    => $cap->hora ? substr($cap->hora, 0, 5) : null,
            ];
        });
})->values();
@endphp

{{-- ── Calendario físico ──────────────────────────────────────────── --}}
<div id="tab-calendario" style="display:none">

    {{-- Cabecera de navegación --}}
    <div style="display:flex; align-items:center; justify-content:space-between;
                flex-wrap:wrap; gap:12px; margin-bottom:20px">
        <div>
            <div id="cal-titulo-mes"
                 style="font-family:'Bricolage Grotesque',sans-serif;
                        font-size:22px; font-weight:700; color:var(--text); line-height:1.1">
            </div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:4px">
                Solo capacitaciones <strong>confirmadas</strong>
            </div>
        </div>
        <div style="display:flex; gap:8px">
            <button class="btn btn-secondary btn-sm" onclick="calMes(-1)">← Anterior</button>
            <button class="btn btn-secondary btn-sm" onclick="calHoy()">Hoy</button>
            <button class="btn btn-secondary btn-sm" onclick="calMes(1)">Siguiente →</button>
        </div>
    </div>

    {{-- Grid del mes --}}
    <div class="card" style="overflow:hidden; padding:0">
        <div class="cal-weekdays">
            <div class="cal-weekday">Lun</div>
            <div class="cal-weekday">Mar</div>
            <div class="cal-weekday">Mié</div>
            <div class="cal-weekday">Jue</div>
            <div class="cal-weekday">Vie</div>
            <div class="cal-weekday">Sáb</div>
            <div class="cal-weekday">Dom</div>
        </div>
        <div id="cal-grid" class="cal-grid"></div>
    </div>

    {{-- Leyenda --}}
    <div style="display:flex; gap:20px; margin-top:14px; flex-wrap:wrap; align-items:center">
        <div style="display:flex; align-items:center; gap:7px; font-size:13px; color:var(--text-muted)">
            <span class="cal-leyenda-dot" style="background:#2563eb"></span>
            Capacitación ECA confirmada
        </div>
        <div style="display:flex; align-items:center; gap:7px; font-size:13px; color:var(--text-muted)">
            <span class="cal-leyenda-dot" style="background:#7c3aed"></span>
            Capacitación ELT confirmada
        </div>
    </div>
</div>

{{-- ── Modal detalle día calendario ───────────────────────────────── --}}
<div class="ssa-modal-bg" id="cal-modal-dia">
    <div class="ssa-modal" style="max-width:520px">
        <button class="ssa-modal-close" onclick="cerrarModal('cal-modal-dia')">✕</button>
        <div class="ssa-modal-title" id="cal-modal-titulo"></div>
        <div id="cal-modal-body"></div>
        <div style="margin-top:20px; text-align:right">
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('cal-modal-dia')">
                Cerrar
            </button>
        </div>
    </div>
</div>

{{-- ── Modal NUEVA CAPACITACIÓN ──────────────────────────────────── --}}
<div class="ssa-modal-bg" id="modal-nueva">
    <div class="ssa-modal">
        <button class="ssa-modal-close" onclick="cerrarModal('modal-nueva')">✕</button>
        <div class="ssa-modal-title">Nueva Capacitación</div>

        <form method="POST" action="{{ route('ssa.store') }}">
            @csrf
            <input type="hidden" name="school_id" id="nueva-school-id">
            <input type="hidden" name="tipo"      id="nueva-tipo">

            <div style="font-weight:500; font-size:14px; padding:10px 14px; margin-bottom:12px;
                        background:var(--surface2); border-radius:8px; border:1px solid var(--border)"
                 id="nueva-school-name"></div>

            <div id="nueva-tipo-badge" class="tipo-badge"></div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Fecha <span style="color:var(--accent)">*</span></label>
                    <input type="date" name="fecha" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Hora (opcional)</label>
                    <input type="time" name="hora" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Estatus <span style="color:var(--accent)">*</span></label>
                <select name="estatus" class="form-control" required>
                    <option value="pendiente">Pendiente</option>
                    <option value="confirmado">Confirmado</option>
                    <option value="realizado">Realizado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Notas (opcional)</label>
                <textarea name="notas" class="form-control" rows="3"
                          placeholder="Temas a tratar, contacto, etc."></textarea>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modal-nueva')">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal EDITAR ────────────────────────────────────────────────── --}}
<div class="ssa-modal-bg" id="modal-editar">
    <div class="ssa-modal">
        <button class="ssa-modal-close" onclick="cerrarModal('modal-editar')">✕</button>
        <div class="ssa-modal-title">Editar Capacitación</div>
        <div id="editar-tipo-badge" class="tipo-badge"></div>

        <form method="POST" id="form-editar" action="">
            @csrf
            @method('PATCH')

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Fecha <span style="color:var(--accent)">*</span></label>
                    <input type="date" name="fecha" id="editar-fecha" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Hora (opcional)</label>
                    <input type="time" name="hora" id="editar-hora" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Estatus <span style="color:var(--accent)">*</span></label>
                <select name="estatus" id="editar-estatus" class="form-control" required>
                    <option value="pendiente">Pendiente</option>
                    <option value="confirmado">Confirmado</option>
                    <option value="realizado">Realizado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Notas (opcional)</label>
                <textarea name="notas" id="editar-notas" class="form-control" rows="3"></textarea>
            </div>

            <div style="display:flex; gap:10px; justify-content:space-between; margin-top:8px">
                <button type="button" class="btn btn-danger btn-sm"
                        onclick="pedirEliminarCap()">
                    🗑 Eliminar
                </button>
                <div style="display:flex; gap:10px">
                    <button type="button" class="btn btn-secondary"
                            onclick="cerrarModal('modal-editar')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </div>
        </form>

        {{-- Form oculto de eliminación --}}
        <form method="POST" id="form-eliminar-cap" action="" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

{{-- ── Modal CAPACITACIONES HOY ──────────────────────────────────── --}}
<div class="ssa-modal-bg" id="modal-hoy">
    <div class="ssa-modal" style="max-width:560px">
        <button class="ssa-modal-close" onclick="cerrarModal('modal-hoy')">✕</button>
        <div class="ssa-modal-title">
            🗓️ Capacitaciones hoy &nbsp;
            <span style="font-size:14px; font-weight:400; color:var(--text-muted)">
                {{ today()->translatedFormat('d \d\e F Y') }}
            </span>
        </div>

        @if($capsHoy->isEmpty())
            <div style="text-align:center; padding:40px 0; color:var(--text-muted)">
                <div style="font-size:40px; margin-bottom:12px">🎉</div>
                <p style="font-size:14px">No hay capacitaciones programadas para hoy.</p>
            </div>
        @else
            @foreach($capsHoy as $cap)
            <div class="hoy-item">
                {{-- Tipo pill --}}
                <span class="hoy-tipo-pill {{ $cap->tipo === 'eca' ? 'hoy-tipo-eca' : 'hoy-tipo-elt' }}">
                    {{ strtoupper($cap->tipo) }}
                </span>

                {{-- Info --}}
                <div style="flex:1; min-width:0">
                    <div style="font-weight:500; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                        {{ $cap->school_name }}
                    </div>
                    @if($cap->notas)
                        <div style="font-size:12px; color:var(--text-muted); margin-top:2px">
                            {{ $cap->notas }}
                        </div>
                    @endif
                </div>

                {{-- Hora --}}
                <div style="font-size:13px; font-weight:600; color:var(--text); white-space:nowrap">
                    @if($cap->hora)
                        🕐 {{ \Carbon\Carbon::parse($cap->hora)->format('H:i') }}
                    @else
                        <span style="color:var(--text-muted)">Sin hora</span>
                    @endif
                </div>

                {{-- Estatus --}}
                @php
                    $badgeMap = [
                        'pendiente'  => 'badge-warning',
                        'confirmado' => 'badge-info',
                        'realizado'  => 'badge-success',
                        'cancelado'  => 'badge-gray',
                    ];
                @endphp
                <span class="badge {{ $badgeMap[$cap->estatus] ?? 'badge-gray' }}"
                      style="flex-shrink:0">
                    {{ ucfirst($cap->estatus) }}
                </span>
            </div>
            @endforeach
        @endif

        <div style="margin-top:20px; text-align:right">
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modal-hoy')">
                Cerrar
            </button>
        </div>
    </div>
</div>

{{-- ── Modales de listas (ECA / ELT / Realizadas) ─────────────── --}}
@php
    $hoyRef = today();
    $listaEca = $schools->map(fn($s) => [
        'school' => $s,
        'caps'   => $s->ssaCapacitaciones->where('tipo', 'eca')
                        ->whereIn('estatus', ['pendiente','confirmado'])
                        ->where('fecha', '>=', $hoyRef)->sortBy('fecha')->values(),
    ])->filter(fn($r) => $r['caps']->isNotEmpty())->values();

    $listaElt = $schools->map(fn($s) => [
        'school' => $s,
        'caps'   => $s->ssaCapacitaciones->where('tipo', 'elt')
                        ->whereIn('estatus', ['pendiente','confirmado'])
                        ->where('fecha', '>=', $hoyRef)->sortBy('fecha')->values(),
    ])->filter(fn($r) => $r['caps']->isNotEmpty())->values();

    $listaRealizadas = $schools->map(fn($s) => [
        'school' => $s,
        'caps'   => $s->ssaCapacitaciones->where('estatus', 'realizado')
                        ->sortByDesc('fecha')->values(),
    ])->filter(fn($r) => $r['caps']->isNotEmpty())->values();

    $badgeEstatus = ['pendiente'=>'badge-warning','confirmado'=>'badge-info','realizado'=>'badge-success','cancelado'=>'badge-gray'];
@endphp

@foreach([
    ['id'=>'modal-eca',       'titulo'=>'📘 Cap. ECA próximas',  'tipo'=>'eca', 'lista'=>$listaEca],
    ['id'=>'modal-elt',       'titulo'=>'📗 Cap. ELT próximas',  'tipo'=>'elt', 'lista'=>$listaElt],
    ['id'=>'modal-realizadas','titulo'=>'✅ Capacitaciones realizadas','tipo'=>null,'lista'=>$listaRealizadas],
] as $m)
<div class="ssa-modal-bg" id="{{ $m['id'] }}">
    <div class="ssa-modal" style="max-width:580px">
        <button class="ssa-modal-close" onclick="cerrarModal('{{ $m['id'] }}')">✕</button>
        <div class="ssa-modal-title">{{ $m['titulo'] }}</div>

        @if($m['lista']->isEmpty())
            <div style="text-align:center; padding:40px 0; color:var(--text-muted)">
                <div style="font-size:36px; margin-bottom:10px">📋</div>
                <p style="font-size:14px">No hay registros para mostrar.</p>
            </div>
        @else
            @foreach($m['lista'] as $fila)
            <div style="padding:12px 0; border-bottom:1px solid var(--border)">
                <div style="font-weight:500; font-size:14px; margin-bottom:6px">
                    {{ $fila['school']->name }}
                    @if(isset($consultoresDigitales[$fila['school']->id]))
                        <span style="font-size:11px; font-weight:400; color:var(--text-muted)">
                            — {{ $consultoresDigitales[$fila['school']->id]->nombre }}
                        </span>
                    @endif
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:6px">
                    @foreach($fila['caps'] as $cap)
                    @php
                        $tipoPill = $cap->tipo === 'eca'
                            ? '<span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;background:#dbeafe;color:#1e40af">ECA</span>'
                            : '<span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;background:#ede9fe;color:#5b21b6">ELT</span>';
                    @endphp
                    <div style="display:inline-flex; align-items:center; gap:5px;
                                background:var(--surface2); border:1px solid var(--border);
                                border-radius:20px; padding:3px 10px; font-size:12px">
                        {!! $m['tipo'] === null ? $tipoPill : '' !!}
                        <span style="font-weight:500">{{ $cap->fecha->format('d/m/Y') }}</span>
                        @if($cap->hora)
                            <span style="color:var(--text-muted)">
                                {{ \Carbon\Carbon::parse($cap->hora)->format('H:i') }}
                            </span>
                        @endif
                        <span class="badge {{ $badgeEstatus[$cap->estatus] ?? 'badge-gray' }}"
                              style="font-size:10px; padding:1px 7px">
                            {{ ucfirst($cap->estatus) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        @endif

        <div style="margin-top:20px; text-align:right">
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('{{ $m['id'] }}')">
                Cerrar
            </button>
        </div>
    </div>
</div>
@endforeach

@php
function ssaChipData($cap, $hoy): array
{
    if ($cap->estatus === 'realizado') {
        return ['chip-realizado', '✓ ' . $cap->fecha->format('d/m'), ''];
    }
    if ($cap->estatus === 'cancelado') {
        return ['chip-cancelado', $cap->fecha->format('d/m'), ''];
    }
    if ($cap->fecha->isToday()) {
        return ['chip-' . $cap->estatus, $cap->fecha->format('d/m'), 'Hoy'];
    }
    if ($cap->fecha->gt($hoy)) {
        $dias = $hoy->diffInDays($cap->fecha);
        return ['chip-' . $cap->estatus, $cap->fecha->format('d/m'), $dias === 1 ? 'mañana' : $dias . ' días'];
    }
    return ['chip-vencida', $cap->fecha->format('d/m'), 'vencida'];
}
@endphp

<script>
// ── Calendario ─────────────────────────────────────────────────────
const CAL_DATA = @json($capsCalendario);
const CAL_MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                   'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
let calYear  = new Date().getFullYear();
let calMonth = new Date().getMonth();

function cambiarTab(tab) {
    document.getElementById('tab-lista').style.display      = tab === 'lista'      ? '' : 'none';
    document.getElementById('tab-calendario').style.display = tab === 'calendario' ? '' : 'none';
    document.querySelectorAll('.ssa-tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById('tab-btn-' + tab).classList.add('active');
    if (tab === 'calendario') renderCal();
}

function calMes(delta) {
    calMonth += delta;
    if (calMonth < 0)  { calMonth = 11; calYear--; }
    if (calMonth > 11) { calMonth = 0;  calYear++; }
    renderCal();
}

function calHoy() {
    var now = new Date();
    calYear  = now.getFullYear();
    calMonth = now.getMonth();
    renderCal();
}

function renderCal() {
    var hoyObj    = new Date();
    var primerDia = new Date(calYear, calMonth, 1);
    var lastDate  = new Date(calYear, calMonth + 1, 0).getDate();

    document.getElementById('cal-titulo-mes').textContent = CAL_MESES[calMonth] + ' ' + calYear;

    // Offset: lunes = 0 (getDay devuelve 0=dom)
    var dow      = primerDia.getDay();
    var startPad = dow === 0 ? 6 : dow - 1;

    var grid = document.getElementById('cal-grid');
    grid.innerHTML = '';

    for (var i = 0; i < startPad; i++) {
        var empty = document.createElement('div');
        empty.className = 'cal-cell cal-empty';
        grid.appendChild(empty);
    }

    for (var d = 1; d <= lastDate; d++) {
        var mStr     = String(calMonth + 1).padStart(2, '0');
        var dStr     = String(d).padStart(2, '0');
        var fechaStr = calYear + '-' + mStr + '-' + dStr;
        var esHoy    = hoyObj.getFullYear() === calYear &&
                       hoyObj.getMonth()    === calMonth &&
                       hoyObj.getDate()     === d;

        var cell = document.createElement('div');
        cell.className = 'cal-cell' + (esHoy ? ' cal-hoy' : '');

        var numDiv  = document.createElement('div');
        numDiv.className = 'cal-day-num';
        var numSpan = document.createElement('span');
        if (esHoy) numSpan.className = 'hoy-circle';
        numSpan.textContent = d;
        numDiv.appendChild(numSpan);
        cell.appendChild(numDiv);

        var caps = CAL_DATA.filter(function(c) { return c.fecha === fechaStr; });
        var MAX  = 3;

        caps.slice(0, MAX).forEach(function(cap) {
            var chip = document.createElement('div');
            chip.className = 'cal-chip-cal cal-chip-' + cap.tipo;
            chip.title     = cap.colegio + (cap.hora ? '  ·  ' + cap.hora : '');

            var tag = document.createElement('span');
            tag.style.cssText    = 'font-weight:900; font-size:9px; opacity:0.85; flex-shrink:0';
            tag.textContent      = cap.tipo.toUpperCase();

            var nom = document.createElement('span');
            nom.style.cssText    = 'overflow:hidden; text-overflow:ellipsis; flex:1; min-width:0';
            nom.textContent      = cap.colegio.length > 17 ? cap.colegio.substring(0, 17) + '…' : cap.colegio;

            chip.appendChild(tag);
            chip.appendChild(nom);
            cell.appendChild(chip);
        });

        if (caps.length > MAX) {
            var more = document.createElement('div');
            more.className   = 'cal-more';
            more.textContent = '+' + (caps.length - MAX) + ' más';
            cell.appendChild(more);
        }

        if (caps.length > 0) {
            var eyeBtn = document.createElement('button');
            eyeBtn.type      = 'button';
            eyeBtn.className = 'cal-eye-btn';
            eyeBtn.innerHTML = '👁 ' + caps.length + (caps.length === 1 ? ' capacitación' : ' capacitaciones');
            eyeBtn.onclick   = (function(f, day) {
                return function() { verDiaDetalle(f, day); };
            })(fechaStr, d);
            cell.appendChild(eyeBtn);
        }

        grid.appendChild(cell);
    }
}

function verDiaDetalle(fechaStr, dia) {
    var caps  = CAL_DATA.filter(function(c) { return c.fecha === fechaStr; });
    var parts = fechaStr.split('-');
    var titulo = dia + ' de ' + CAL_MESES[parseInt(parts[1]) - 1] + ' ' + parts[0];

    document.getElementById('cal-modal-titulo').textContent = '📅 ' + titulo;

    var body = document.getElementById('cal-modal-body');
    body.innerHTML = '';

    caps.forEach(function(cap) {
        var item = document.createElement('div');
        item.className = 'hoy-item';

        var pill = document.createElement('span');
        pill.className   = 'hoy-tipo-pill ' + (cap.tipo === 'eca' ? 'hoy-tipo-eca' : 'hoy-tipo-elt');
        pill.textContent = cap.tipo.toUpperCase();

        var info = document.createElement('div');
        info.style.cssText = 'flex:1; min-width:0';
        var nameEl = document.createElement('div');
        nameEl.style.cssText = 'font-weight:500; font-size:14px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap';
        nameEl.textContent   = cap.colegio;
        info.appendChild(nameEl);

        var hora = document.createElement('div');
        hora.style.cssText = 'font-size:13px; font-weight:600; white-space:nowrap';
        if (cap.hora) {
            hora.style.color = 'var(--text)';
            hora.textContent = '🕐 ' + cap.hora;
        } else {
            hora.style.color = 'var(--text-muted)';
            hora.textContent = 'Sin hora';
        }

        item.appendChild(pill);
        item.appendChild(info);
        item.appendChild(hora);
        body.appendChild(item);
    });

    document.getElementById('cal-modal-dia').classList.add('open');
}

// ── Modales / tabla ────────────────────────────────────────────────
function abrirNueva(schoolId, schoolName, tipo) {
    document.getElementById('nueva-school-id').value       = schoolId;
    document.getElementById('nueva-tipo').value            = tipo;
    document.getElementById('nueva-school-name').textContent = schoolName;
    var badge = document.getElementById('nueva-tipo-badge');
    badge.className   = 'tipo-badge tipo-badge-' + tipo;
    badge.textContent = tipo === 'eca' ? '📘 Capacitación ECA' : '📗 Capacitación ELT';
    document.getElementById('modal-nueva').classList.add('open');
}

function abrirEditar(id, fecha, hora, tipo, estatus, notas) {
    var base = '{{ url("ssa") }}/' + id;
    document.getElementById('form-editar').action       = base;
    document.getElementById('form-eliminar-cap').action = base;
    document.getElementById('editar-fecha').value   = fecha;
    document.getElementById('editar-hora').value    = hora ? hora.substring(0, 5) : '';
    document.getElementById('editar-estatus').value = estatus;
    document.getElementById('editar-notas').value   = notas || '';
    var badge = document.getElementById('editar-tipo-badge');
    badge.className   = 'tipo-badge tipo-badge-' + tipo;
    badge.textContent = tipo === 'eca' ? '📘 Capacitación ECA' : '📗 Capacitación ELT';
    document.getElementById('modal-editar').classList.add('open');
}

function abrirModalHoy() {
    document.getElementById('modal-hoy').classList.add('open');
}

function abrirModalLista(id) {
    document.getElementById(id).classList.add('open');
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('open');
}

function pedirEliminarCap() {
    cerrarModal('modal-editar');
    confirmarEliminar(
        'Eliminar capacitación',
        'Esta acción eliminará la capacitación permanentemente. No se puede deshacer.',
        'form-eliminar-cap'
    );
}

// Cerrar al click fuera del modal
['modal-nueva', 'modal-editar', 'modal-hoy', 'modal-eca', 'modal-elt', 'modal-realizadas', 'cal-modal-dia'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('click', function(e) {
        if (e.target === this) cerrarModal(id);
    });
});

// Búsqueda + filtro
function filtrarEscuelas() {
    var buscar = document.getElementById('ssa-buscar').value.toLowerCase().trim();
    var filtro = document.getElementById('ssa-filtro').value;
    document.querySelectorAll('.ssa-fila').forEach(function(fila) {
        var matchBuscar = !buscar || (fila.dataset.name || '').includes(buscar);
        var matchFiltro = true;
        if      (filtro === 'hoy')          matchFiltro = fila.dataset.hoy       === '1';
        else if (filtro === 'sin-eca')      matchFiltro = fila.dataset.tieneEca  === '0';
        else if (filtro === 'sin-elt')      matchFiltro = fila.dataset.tieneElt  === '0';
        else if (filtro === 'proximas-eca') matchFiltro = fila.dataset.proximaEca === '1';
        else if (filtro === 'proximas-elt') matchFiltro = fila.dataset.proximaElt === '1';
        fila.style.display = (matchBuscar && matchFiltro) ? '' : 'none';
    });
}
</script>
@endsection
