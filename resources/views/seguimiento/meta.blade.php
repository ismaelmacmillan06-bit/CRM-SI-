@extends('layouts.app')

@section('title', 'Seguimiento · ' . $nombre)

@section('content')
@php
    $proximasCap = $filas->where('capacitacion_proxima', true)->count();
    $proximasApl = $filas->where('aplicacion_proxima', true)->count();
    $pendientes  = $filas->where('pendiente', true)->count();
    // Si una edición regresó con errores, se vuelve a abrir el modal de ese colegio
    $colegioConError = $filas->first(fn ($f) => $errors->getBag('meta_' . $f['colegio']->id)->any())['colegio'] ?? null;
@endphp

<style>
    .mt-cards { display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:12px; margin-bottom:20px; }
    .mt-card { background:#fff; border:1px solid var(--border); border-left:4px solid var(--accent); border-radius:12px;
               padding:14px 18px; text-align:left; cursor:pointer; font:inherit; color:inherit; transition:box-shadow .15s, transform .15s; }
    .mt-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.07); transform:translateY(-1px); }
    .mt-card.activa { background:#fff5f5; box-shadow:0 0 0 2px rgba(226,35,26,.25); }
    .mt-card .num { font-size:24px; font-weight:800; line-height:1; color:var(--accent); }
    .mt-card .lbl { font-size:12.5px; font-weight:600; color:var(--text-muted); margin-top:6px; }
    .mt-card.verde { border-left-color:#16a34a; } .mt-card.verde .num { color:#16a34a; }
    .mt-card.azul  { border-left-color:#2563eb; } .mt-card.azul .num  { color:#2563eb; }
    .mt-card.ambar { border-left-color:#d97706; } .mt-card.ambar .num { color:#d97706; }

    .mt-table td { vertical-align:middle; font-size:13.5px; }
    .mt-table th { white-space:nowrap; }
    .mt-vacio { color:#b8b2aa; }
    .mt-pwd { display:inline-flex; align-items:center; gap:6px; font-family:ui-monospace,Consolas,monospace; white-space:nowrap; }
    .mt-pwd button { background:none; border:none; cursor:pointer; padding:2px; color:var(--text-muted); line-height:0; }
    .mt-fecha { white-space:nowrap; }
    .mt-chip { display:inline-block; font-size:11px; font-weight:600; border-radius:20px; padding:1px 8px; margin-top:3px; }
    .mt-chip.prox { background:#dbeafe; color:#1d4ed8; }
    .mt-chip.hoy  { background:#fee2e2; color:#b91c1c; }
    .mt-chip.pas  { background:var(--surface2); color:var(--text-muted); }
    .mt-notas { max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text-muted); }
    .mt-acc { display:inline-flex; gap:6px; flex-wrap:nowrap; }
    /* La columna de botones queda fija a la derecha aunque la tabla se deslice de lado */
    .mt-table th:last-child, .mt-table td:last-child { position:sticky; right:0; background:var(--surface);
                                                       box-shadow:-8px 0 10px -8px rgba(0,0,0,.18); z-index:1; }
    .mt-table thead th:last-child { background:var(--surface2); }

    .mt-overlay { display:none; position:fixed; inset:0; background:rgba(24,19,17,.55); z-index:999;
                  align-items:center; justify-content:center; padding:20px; }
    .mt-overlay.abierto { display:flex; }
    .mt-modal { background:var(--surface); border-radius:16px; width:620px; max-width:100%; max-height:90vh;
                display:flex; flex-direction:column; box-shadow:0 20px 50px rgba(0,0,0,.25); overflow:hidden; }
    .mt-head { display:flex; align-items:center; gap:10px; padding:16px 22px; border-bottom:1px solid var(--border); }
    .mt-head h3 { font-family:'Bricolage Grotesque',sans-serif; font-size:17px; font-weight:600; margin:0; }
    .mt-head small { display:block; font-size:12px; color:var(--text-muted); font-weight:500; margin-top:2px; }
    .mt-close { margin-left:auto; background:none; border:none; width:34px; height:34px; border-radius:8px; font-size:18px;
                cursor:pointer; color:var(--text-muted); }
    .mt-close:hover { background:var(--surface2); }
    .mt-body { padding:18px 22px; overflow-y:auto; display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .mt-body .full { grid-column:1 / -1; }
    .mt-body label { display:block; font-size:12.5px; font-weight:600; color:var(--text-muted); margin-bottom:5px; }
    .mt-sec { grid-column:1 / -1; font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
              color:var(--text-muted); border-bottom:1px solid var(--border); padding-bottom:6px; margin-top:4px; }
    .mt-foot { display:flex; justify-content:flex-end; gap:8px; padding:14px 22px; border-top:1px solid var(--border); }
    .mt-err { grid-column:1 / -1; background:#fee2e2; color:#991b1b; border-radius:8px; padding:10px 12px; font-size:13px; }

    /* Selector de vista */
    .mt-barra { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
    .mt-vistas { display:inline-flex; background:var(--surface2); border-radius:10px; padding:4px; gap:4px; }
    .mt-contacto-btn { white-space:nowrap; }

    /* Contacto responsable */
    .mt-ct-persona { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
    .mt-ct-avatar { flex:none; width:46px; height:46px; border-radius:50%; background:var(--accent); color:#fff;
                    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:19px; }
    .mt-ct-nombre { font-weight:700; font-size:16px; }
    .mt-ct-dato { font-size:12.5px; color:var(--text-muted); }
    .mt-ct-fila { display:flex; gap:8px; align-items:center; background:var(--surface2); border-radius:8px; padding:8px 12px;
                  margin-bottom:6px; font-size:13.5px; }
    .mt-ct-fila span { overflow-wrap:anywhere; }
    .mt-ct-label { display:block; font-size:12.5px; font-weight:600; color:var(--text-muted); margin:14px 0 5px; }
    .mt-ct-mensaje { background:#fff; border:1px solid var(--border); border-radius:10px; padding:12px 14px; font-size:13.5px;
                     line-height:1.5; white-space:pre-line; }
    .mt-ct-acciones { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:16px; }
    .mt-ct-acciones .btn { justify-content:center; gap:7px; color:#fff; border:none; font-weight:600; display:inline-flex; align-items:center; }
    .mt-ct-correo { background:#2563eb; } .mt-ct-correo:hover { background:#1d4ed8; }
    .mt-ct-wa { background:#16a34a; } .mt-ct-wa:hover { background:#15803d; }
    @media (max-width: 600px) { .mt-contacto-btn { flex:1 1 100%; justify-content:center; } }
    .mt-vista-btn { border:none; background:none; font:inherit; font-size:13.5px; font-weight:600; color:var(--text-muted);
                    padding:7px 16px; border-radius:7px; cursor:pointer; }
    .mt-vista-btn.activa { background:#fff; color:var(--text); box-shadow:0 1px 3px rgba(0,0,0,.1); }

    /* Calendario */
    .mt-dot { display:inline-block; width:10px; height:10px; border-radius:3px; margin-right:6px; vertical-align:-1px; }
    .mt-dot.azul { background:#2563eb; } .mt-dot.naranja { background:#ea580c; }
    .mt-cal-nav { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:16px 20px; border-bottom:1px solid var(--border); }
    .mt-cal-titulo { font-family:'Bricolage Grotesque',sans-serif; font-size:21px; font-weight:700; line-height:1.1; }
    .mt-cal-leyenda { display:flex; gap:16px; flex-wrap:wrap; font-size:12.5px; color:var(--text-muted); margin-top:6px; }
    .mt-cal-dias, .mt-cal-grid { display:grid; grid-template-columns:repeat(7, minmax(0, 1fr)); }
    .mt-cal-dias { background:var(--surface2); border-bottom:1px solid var(--border); }
    .mt-cal-dias div { padding:9px 6px; text-align:center; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--text-muted); }
    .mt-cal-celda { min-height:112px; padding:6px 7px; border-right:1px solid var(--border); border-bottom:1px solid var(--border); background:#fff; min-width:0; }
    .mt-cal-celda:nth-child(7n) { border-right:none; }
    .mt-cal-celda.fuera { background:var(--surface2); }
    .mt-cal-celda.hoy { background:#fff7ed; }
    .mt-cal-num { text-align:right; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:4px; }
    .mt-cal-num span { display:inline-flex; width:23px; height:23px; border-radius:50%; align-items:center; justify-content:center; }
    .mt-cal-celda.hoy .mt-cal-num span { background:var(--accent); color:#fff; }
    .mt-ev { display:flex; align-items:center; gap:4px; width:100%; border:none; font:inherit; font-size:11px; font-weight:600; color:#fff;
             border-radius:5px; padding:3px 6px; margin-bottom:3px; cursor:pointer; text-align:left; min-width:0; }
    .mt-ev span.nom { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; min-width:0; flex:1; }
    .mt-ev span.h { flex:none; opacity:.9; font-weight:700; }
    .mt-ev.cap { background:#2563eb; } .mt-ev.cap:hover { background:#1d4ed8; }
    .mt-ev.apl { background:#ea580c; } .mt-ev.apl:hover { background:#c2410c; }
    .mt-ev.extra { display:none; }
    .mt-cal-celda.expandida .mt-ev.extra { display:flex; }
    .mt-cal-mas { border:none; background:none; font:inherit; font-size:11px; color:var(--text-muted); cursor:pointer; padding:1px 2px; }
    .mt-cal-mas:hover { color:var(--text); text-decoration:underline; }
    .mt-cal-lista-card { display:none; margin-top:14px; }
    .mt-li { display:flex; align-items:center; gap:12px; padding:11px 16px; border-bottom:1px solid var(--border); cursor:pointer; }
    .mt-li:last-child { border-bottom:none; }
    .mt-li:hover { background:var(--surface2); }
    .mt-li .fecha { flex:none; width:46px; text-align:center; border-radius:8px; padding:4px 0; color:#fff; line-height:1.1; }
    .mt-li .fecha b { display:block; font-size:17px; }
    .mt-li .fecha small { font-size:10px; text-transform:uppercase; font-weight:600; }
    .mt-li .fecha.cap { background:#2563eb; } .mt-li .fecha.apl { background:#ea580c; }
    .mt-li .info { min-width:0; flex:1; }
    .mt-li .info strong { display:block; font-size:14px; }
    .mt-li .info span { font-size:12px; color:var(--text-muted); }

    @media (max-width: 700px) {
        .mt-cal-celda { min-height:58px; padding:3px; }
        .mt-cal-num { text-align:center; font-size:11px; margin-bottom:2px; }
        .mt-cal-num span { width:21px; height:21px; }
        /* En celular cada evento es un punto de color; los nombres van en la lista de abajo */
        .mt-ev, .mt-ev.extra { width:8px; height:8px; padding:0; border-radius:50%; display:inline-block; margin:0 1px; }
        .mt-ev span { display:none; }
        .mt-cal-mas { display:none; }
        .mt-cal-eventos { text-align:center; line-height:8px; }
        .mt-cal-lista-card { display:block; }
        .mt-cal-dias div { font-size:10px; padding:8px 2px; letter-spacing:0; }
    }

    @media (max-width: 600px) {
        .mt-overlay { align-items:flex-end; padding:0; }
        .mt-modal { width:100%; border-radius:18px 18px 0 0; max-height:92vh; }
        .mt-body { grid-template-columns:1fr; padding:16px; }
        .mt-foot { padding:12px 16px calc(12px + env(safe-area-inset-bottom)); }
        .mt-foot .btn { flex:1; justify-content:center; }
    }
</style>

<div style="margin-bottom:24px">
    <h1 style="font-family:'Bricolage Grotesque',sans-serif; font-size:24px; font-weight:700; margin:0">
        {{ $icono }} Agenda de exámenes {{ $nombre }}
    </h1>
    <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
        Capacitaciones y fechas de aplicación del examen de los colegios con {{ $nombre }}.
    </p>
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

{{-- Cards: también sirven de filtro --}}
<div class="mt-cards">
    <button type="button" class="mt-card" data-filtro-card="">
        <div class="num">{{ $filas->count() }}</div>
        <div class="lbl">🏫 Colegios</div>
    </button>
    <button type="button" class="mt-card azul" data-filtro-card="cap">
        <div class="num">{{ $proximasCap }}</div>
        <div class="lbl">👩‍🏫 Capacitaciones próximas (30 días)</div>
    </button>
    <button type="button" class="mt-card verde" data-filtro-card="apl">
        <div class="num">{{ $proximasApl }}</div>
        <div class="lbl">📝 Aplicaciones próximas (30 días)</div>
    </button>
    <button type="button" class="mt-card ambar" data-filtro-card="pend">
        <div class="num">{{ $pendientes }}</div>
        <div class="lbl">⏳ Con fechas pendientes</div>
    </button>
</div>

{{-- Selector de vista + contacto responsable --}}
<div class="mt-barra">
    <div class="mt-vistas" role="tablist">
        <button type="button" class="mt-vista-btn activa" data-vista="tabla" role="tab">📋 Tabla</button>
        <button type="button" class="mt-vista-btn" data-vista="calendario" role="tab">📅 Calendario</button>
    </div>
    @if($contacto)
        <button type="button" class="btn btn-secondary mt-contacto-btn" onclick="mtContacto(true)">
            👤 Contacto Responsable
        </button>
    @endif
</div>

@if($contacto)
<div id="mt-contacto" class="mt-overlay" role="dialog" aria-modal="true" aria-labelledby="mt-contacto-titulo">
    <div class="mt-modal" style="width:440px">
        <div class="mt-head">
            <h3 id="mt-contacto-titulo">👤 Contacto Responsable</h3>
            <button type="button" class="mt-close" onclick="mtContacto(false)" aria-label="Cerrar">✕</button>
        </div>
        <div style="padding:20px 22px; overflow-y:auto">
            <div class="mt-ct-persona">
                <div class="mt-ct-avatar">{{ mb_substr($contacto['nombre'], 0, 1) }}</div>
                <div style="min-width:0">
                    <div class="mt-ct-nombre">{{ $contacto['nombre'] }}</div>
                    <div class="mt-ct-dato">Responsable de {{ $nombre }}</div>
                </div>
            </div>
            <div class="mt-ct-fila">✉️ <span>{{ $contacto['correo'] }}</span></div>
            <div class="mt-ct-fila">📱 <span>{{ preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1 $2 $3', $contacto['telefono']) }}</span></div>

            <label for="mt-ct-colegio" class="mt-ct-label">Colegio a revisar <span style="font-weight:400">(opcional)</span></label>
            <select id="mt-ct-colegio" class="form-control">
                <option value="">— Sin colegio específico —</option>
                @foreach($filas as $fila)
                    <option value="{{ $fila['colegio']->name }}{{ $fila['colegio']->nexus_id ? ' (ID ' . $fila['colegio']->nexus_id . ')' : '' }}">{{ $fila['colegio']->name }}</option>
                @endforeach
            </select>

            <div class="mt-ct-label" style="margin-top:14px">Mensaje</div>
            <div class="mt-ct-mensaje" id="mt-ct-mensaje"></div>

            <div class="mt-ct-acciones">
                <a id="mt-ct-correo" class="btn mt-ct-correo" target="_blank" rel="noopener">✉️ Correo</a>
                <a id="mt-ct-wa" class="btn mt-ct-wa" target="_blank" rel="noopener">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.75-.72 2-1.41.25-.69.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12.04 21.5h-.01a9.45 9.45 0 0 1-4.82-1.32l-.35-.21-3.58.94.96-3.49-.23-.36a9.43 9.43 0 0 1-1.45-5.03c0-5.22 4.25-9.46 9.48-9.46 2.53 0 4.91.99 6.7 2.78a9.4 9.4 0 0 1 2.77 6.69c0 5.22-4.25 9.46-9.47 9.46zm8.06-17.52A11.32 11.32 0 0 0 12.04.65C5.76.65.65 5.76.65 12.03c0 2 .52 3.96 1.52 5.69L.55 23.35l5.77-1.51a11.37 11.37 0 0 0 5.72 1.46h.01c6.28 0 11.39-5.11 11.39-11.38 0-3.04-1.18-5.9-3.34-8.05z"/></svg>
                    WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Vista calendario ─────────────────────────────────────────── --}}
@php
    $eventos = collect();
    foreach ($filas as $fila) {
        $m = $fila['meta'];
        $base = ['id' => $fila['colegio']->id, 'colegio' => $fila['colegio']->name,
                 'administrador' => $m->administrador, 'capacitacion' => $m->capacitacion];
        if ($m->fecha_capacitacion) {
            $eventos->push($base + ['tipo' => 'cap', 'fecha' => $m->fecha_capacitacion->format('Y-m-d'), 'hora' => $m->horaCorta()]);
        }
        if ($m->fecha_aplicacion) {
            $eventos->push($base + ['tipo' => 'apl', 'fecha' => $m->fecha_aplicacion->format('Y-m-d'), 'hora' => null]);
        }
    }
@endphp
<div id="mt-vista-calendario" style="display:none">
    <div class="card" style="overflow:hidden; padding:0">
        <div class="mt-cal-nav">
            <div>
                <div id="mt-cal-titulo" class="mt-cal-titulo"></div>
                <div class="mt-cal-leyenda">
                    <span><span class="mt-dot azul"></span>Capacitación de administradores</span>
                    <span><span class="mt-dot naranja"></span>Aplicación de examen</span>
                </div>
            </div>
            <div style="display:flex; gap:6px">
                <button type="button" class="btn btn-secondary btn-sm" onclick="mtCalMes(-1)" aria-label="Mes anterior">←</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="mtCalHoy()">Hoy</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="mtCalMes(1)" aria-label="Mes siguiente">→</button>
            </div>
        </div>
        <div class="mt-cal-dias">
            <div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div><div>Sáb</div><div>Dom</div>
        </div>
        <div id="mt-cal-grid" class="mt-cal-grid"></div>
    </div>

    {{-- Lista del mes: en celular el calendario es muy chico para leer nombres --}}
    <div class="card mt-cal-lista-card">
        <div class="card-header"><span class="card-title" id="mt-cal-lista-titulo">Eventos del mes</span></div>
        <div id="mt-cal-lista"></div>
    </div>
</div>

<div class="card" id="mt-vista-tabla">
    <div class="card-header" style="gap:10px; flex-wrap:wrap">
        <span class="card-title">🏫 Colegios con {{ $nombre }}</span>
        @if($filas->isNotEmpty())
        <div style="display:flex; gap:8px; flex-wrap:wrap; flex:1; justify-content:flex-end">
            <input type="search" id="mt-buscar" class="form-control" autocomplete="off"
                   placeholder="🔍 Buscar colegio, ID, administrador, capacitación..." style="flex:1 1 240px; max-width:380px">
            <select id="mt-filtro" class="form-control" style="max-width:240px">
                <option value="">Todos</option>
                <option value="cap">Capacitación próxima (30 días)</option>
                <option value="apl">Aplicación próxima (30 días)</option>
                <option value="pend">Con fechas pendientes</option>
                <option value="listo">Con ambas fechas</option>
            </select>
        </div>
        @endif
    </div>
    <div style="overflow-x:auto">
    <table class="table mt-table">
        <thead>
            <tr>
                <th>ID Colegio</th>
                <th>Colegio</th>
                <th>Administrador</th>
                <th>Usuario</th>
                <th>Contraseña</th>
                <th>Capacitación</th>
                <th>Fecha capacitación</th>
                <th>Fecha aplicación</th>
                <th>Notas</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="mt-tbody">
            @forelse($filas as $fila)
            @php
                $c = $fila['colegio'];
                $m = $fila['meta'];
                $estado = collect([
                    $fila['capacitacion_proxima'] ? 'cap' : null,
                    $fila['aplicacion_proxima'] ? 'apl' : null,
                    $fila['pendiente'] ? 'pend' : 'listo',
                ])->filter()->implode(' ');
                $texto = mb_strtolower(implode(' ', [$c->nexus_id, $c->name, $m->administrador, $m->usuario, $m->capacitacion, $m->notas]));
                $datos = [
                    'id'                 => $c->id,
                    'nombre'             => $c->name,
                    'administrador'      => $m->administrador,
                    'usuario'            => $m->usuario,
                    'contrasena'         => $m->contrasena,
                    'capacitacion'       => $m->capacitacion,
                    'fecha_capacitacion' => $m->fecha_capacitacion?->format('Y-m-d'),
                    'hora_capacitacion'  => $m->horaCorta(),
                    'fecha_aplicacion'   => $m->fecha_aplicacion?->format('Y-m-d'),
                    'notas'              => $m->notas,
                    'action'             => route('seguimiento.guardar', [$slug, $c]),
                    'editado'            => $m->updated_at ? 'Última edición: ' . $m->updated_at->format('d/m/Y H:i') : '',
                ];
            @endphp
            <tr data-estado="{{ $estado }}" data-texto="{{ $texto }}">
                <td style="white-space:nowrap">
                    @if($c->nexus_id)<strong>{{ $c->nexus_id }}</strong>@else<span class="mt-vacio" title="Sin Nexus ID">—</span>@endif
                </td>
                <td style="min-width:160px">
                    <a href="{{ route('schools.show', $c) }}" style="font-weight:600; color:var(--text); text-decoration:none">{{ $c->name }}</a>
                    @if($c->state)<div style="font-size:12px; color:var(--text-muted)">{{ $c->state }}</div>@endif
                </td>
                <td>{!! $m->administrador ? e($m->administrador) : '<span class="mt-vacio">—</span>' !!}</td>
                <td style="font-family:ui-monospace,Consolas,monospace; font-size:12.5px">{!! $m->usuario ? e($m->usuario) : '<span class="mt-vacio" style="font-family:inherit">—</span>' !!}</td>
                <td>
                    @if($m->contrasena)
                        <span class="mt-pwd">
                            <span data-pwd="{{ $m->contrasena }}">••••••••</span>
                            <button type="button" onclick="mtTogglePwd(this)" title="Mostrar / ocultar">👁</button>
                        </span>
                    @else
                        <span class="mt-vacio">—</span>
                    @endif
                </td>
                <td>{!! $m->capacitacion ? e($m->capacitacion) : '<span class="mt-vacio">—</span>' !!}</td>
                @foreach(['fecha_capacitacion', 'fecha_aplicacion'] as $campoFecha)
                    @php $f = $m->{$campoFecha}; @endphp
                    <td class="mt-fecha">
                        @if($f)
                            {{ $f->format('d/m/Y') }}
                            @if($campoFecha === 'fecha_capacitacion' && $m->horaCorta())
                                <span style="color:var(--text-muted); font-size:12.5px">· {{ $m->horaCorta() }} h</span>
                            @endif
                            <br>
                            @if($f->isToday())
                                <span class="mt-chip hoy">Hoy</span>
                            @elseif($f->isPast())
                                <span class="mt-chip pas">Realizada</span>
                            @elseif($f->lte(today()->addDays(30)))
                                <span class="mt-chip prox">En {{ (int) today()->diffInDays($f) }} día{{ (int) today()->diffInDays($f) === 1 ? '' : 's' }}</span>
                            @endif
                        @else
                            <span class="badge badge-warning">Pendiente</span>
                        @endif
                    </td>
                @endforeach
                <td><div class="mt-notas" title="{{ $m->notas }}">{{ $m->notas ?: '—' }}</div></td>
                <td style="text-align:right">
                    <div class="mt-acc">
                        <a href="{{ route('schools.tickets.index', $c) }}" class="btn btn-secondary btn-sm" title="Tickets de {{ $c->name }}">🎫</a>
                        <a href="{{ route('schools.visits.index', $c) }}" class="btn btn-secondary btn-sm" title="Sesiones de {{ $c->name }}">📅</a>
                        <button type="button" class="btn btn-primary btn-sm" style="white-space:nowrap"
                                data-colegio="{{ $c->id }}" data-meta="{{ json_encode($datos) }}" onclick="mtEditar(this)">✏️ Editar</button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay colegios con {{ $nombre }}. Agrega el servicio en la ficha del colegio (📦 Servicios adicionales).
                </td>
            </tr>
            @endforelse
            <tr id="mt-sin-resultados" style="display:none">
                <td colspan="10" style="text-align:center; color:var(--text-muted); padding:30px">Ningún colegio coincide con la búsqueda.</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

{{-- Modal de edición (uno solo, se llena con los datos del colegio) --}}
<div id="mt-modal" class="mt-overlay" role="dialog" aria-modal="true" aria-labelledby="mt-titulo">
    <form method="POST" id="mt-form" class="mt-modal">
        @csrf
        <div class="mt-head">
            <div>
                <h3 id="mt-titulo">✏️ Editar Meta</h3>
                <small id="mt-editado"></small>
            </div>
            <button type="button" class="mt-close" onclick="mtCerrar()" aria-label="Cerrar">✕</button>
        </div>
        <div class="mt-body">
            @if($colegioConError)
                <div class="mt-err">
                    @foreach($errors->getBag('meta_' . $colegioConError->id)->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="mt-sec">🔐 Acceso a la plataforma</div>
            <div class="full">
                <label for="mt-administrador">Administrador</label>
                <input type="text" name="administrador" id="mt-administrador" class="form-control" maxlength="255" placeholder="Nombre de quien administra Meta en el colegio">
            </div>
            <div>
                <label for="mt-usuario">Usuario</label>
                <input type="text" name="usuario" id="mt-usuario" class="form-control" maxlength="255" autocomplete="off">
            </div>
            <div>
                <label for="mt-contrasena">Contraseña</label>
                <input type="text" name="contrasena" id="mt-contrasena" class="form-control" maxlength="255" autocomplete="off">
            </div>

            <div class="mt-sec">📅 Capacitación y examen</div>
            <div class="full">
                <label for="mt-capacitacion">Capacitación</label>
                <input type="text" name="capacitacion" id="mt-capacitacion" class="form-control" maxlength="255" placeholder="Ej. Capacitación a docentes de inglés">
            </div>
            <div>
                <label for="mt-fecha_capacitacion"><span class="mt-dot azul"></span>Fecha de capacitación</label>
                <input type="date" name="fecha_capacitacion" id="mt-fecha_capacitacion" class="form-control">
            </div>
            <div>
                <label for="mt-hora_capacitacion">🕒 Horario de capacitación</label>
                <input type="time" name="hora_capacitacion" id="mt-hora_capacitacion" class="form-control">
            </div>
            <div class="full">
                <label for="mt-fecha_aplicacion"><span class="mt-dot naranja"></span>Fecha de aplicación del examen</label>
                <input type="date" name="fecha_aplicacion" id="mt-fecha_aplicacion" class="form-control">
            </div>
            <div class="full">
                <label for="mt-notas">Notas</label>
                <textarea name="notas" id="mt-notas" class="form-control" rows="3" maxlength="2000"></textarea>
            </div>
        </div>
        <div class="mt-foot">
            <button type="button" class="btn btn-secondary" onclick="mtCerrar()">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>

<script>
(function () {
    const CAMPOS = @json(array_keys(\App\Models\SeguimientoMeta::CAMPOS));
    const modal = document.getElementById('mt-modal');
    const form  = document.getElementById('mt-form');

    function abrir(d) {
        form.action = d.action;
        document.getElementById('mt-titulo').textContent = '✏️ ' + d.nombre;
        document.getElementById('mt-editado').textContent = d.editado || 'Sin datos capturados todavía';
        CAMPOS.forEach(c => { document.getElementById('mt-' + c).value = d[c] || ''; });
        modal.classList.add('abierto');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('mt-administrador').focus(), 50);
    }

    window.mtEditar = btn => abrir(JSON.parse(btn.dataset.meta));
    window.mtCerrar = () => { modal.classList.remove('abierto'); document.body.style.overflow = ''; };
    modal.addEventListener('click', e => { if (e.target === modal) mtCerrar(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('abierto')) mtCerrar(); });

    window.mtTogglePwd = btn => {
        const s = btn.previousElementSibling;
        const oculta = s.textContent === '••••••••';
        s.textContent = oculta ? s.dataset.pwd : '••••••••';
        btn.textContent = oculta ? '🙈' : '👁';
    };

    // Búsqueda + filtro (select y cards)
    const buscar = document.getElementById('mt-buscar');
    const filtro = document.getElementById('mt-filtro');
    const cards  = document.querySelectorAll('[data-filtro-card]');
    const filas  = document.querySelectorAll('#mt-tbody tr[data-estado]');
    const sinRes = document.getElementById('mt-sin-resultados');

    function aplicar() {
        const q = (buscar?.value || '').trim().toLowerCase();
        const f = filtro?.value || '';
        let visibles = 0;
        filas.forEach(tr => {
            const ok = (!q || tr.dataset.texto.includes(q)) && (!f || tr.dataset.estado.split(' ').includes(f));
            tr.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });
        sinRes.style.display = filas.length && !visibles ? '' : 'none';
        cards.forEach(c => c.classList.toggle('activa', c.dataset.filtroCard === f && f !== ''));
    }
    buscar?.addEventListener('input', aplicar);
    filtro?.addEventListener('change', aplicar);
    cards.forEach(c => c.addEventListener('click', () => {
        if (!filtro) return;
        cambiarVista('tabla'); // los filtros aplican a la tabla
        filtro.value = filtro.value === c.dataset.filtroCard ? '' : c.dataset.filtroCard;
        aplicar();
    }));

    // ── Vista tabla / calendario (se recuerda por usuario en este navegador) ──
    const EVENTOS = {!! json_encode($eventos->values(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const MESES_CORTOS = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const MAX_POR_DIA = 3;
    let calAnio = new Date().getFullYear(), calMes = new Date().getMonth();

    function cambiarVista(v) {
        document.getElementById('mt-vista-tabla').style.display = v === 'tabla' ? '' : 'none';
        document.getElementById('mt-vista-calendario').style.display = v === 'calendario' ? '' : 'none';
        document.querySelectorAll('.mt-vista-btn').forEach(b => b.classList.toggle('activa', b.dataset.vista === v));
        try { localStorage.setItem('sic-meta-vista', v); } catch (e) {}
        if (v === 'calendario') pintarCalendario();
    }
    document.querySelectorAll('.mt-vista-btn').forEach(b => b.addEventListener('click', () => cambiarVista(b.dataset.vista)));

    window.mtCalMes = d => {
        calMes += d;
        if (calMes < 0) { calMes = 11; calAnio--; }
        if (calMes > 11) { calMes = 0; calAnio++; }
        pintarCalendario();
    };
    window.mtCalHoy = () => { calAnio = new Date().getFullYear(); calMes = new Date().getMonth(); pintarCalendario(); };

    const pad = n => String(n).padStart(2, '0');
    const etiqueta = e => e.tipo === 'cap' ? 'Capacitación' : 'Aplicación de examen';
    const detalle = e => [etiqueta(e) + (e.hora ? ' · ' + e.hora + ' h' : ''), e.administrador, e.capacitacion && e.tipo === 'cap' ? e.capacitacion : null]
        .filter(Boolean).join(' · ');
    // Capacitaciones primero y por hora; luego aplicaciones
    const orden = (a, b) => (a.tipo === b.tipo ? (a.hora || '99').localeCompare(b.hora || '99') : (a.tipo === 'cap' ? -1 : 1));
    const editarColegio = id => document.querySelector('[data-colegio="' + id + '"]')?.click();

    function botonEvento(e, extra) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'mt-ev ' + e.tipo + (extra ? ' extra' : '');
        b.title = e.colegio + ' — ' + detalle(e);
        if (e.hora) { const h = document.createElement('span'); h.className = 'h'; h.textContent = e.hora; b.appendChild(h); }
        const n = document.createElement('span'); n.className = 'nom'; n.textContent = e.colegio; b.appendChild(n);
        b.addEventListener('click', () => editarColegio(e.id));
        return b;
    }

    function pintarCalendario() {
        document.getElementById('mt-cal-titulo').textContent = MESES[calMes] + ' ' + calAnio;
        const grid = document.getElementById('mt-cal-grid');
        grid.innerHTML = '';
        const hoy = new Date();
        const hoyStr = hoy.getFullYear() + '-' + pad(hoy.getMonth() + 1) + '-' + pad(hoy.getDate());
        const inicio = (new Date(calAnio, calMes, 1).getDay() + 6) % 7; // lunes = 0
        const diasMes = new Date(calAnio, calMes + 1, 0).getDate();
        const celdas = Math.ceil((inicio + diasMes) / 7) * 7;
        const prefijo = calAnio + '-' + pad(calMes + 1) + '-';

        for (let i = 0; i < celdas; i++) {
            const dia = i - inicio + 1;
            const celda = document.createElement('div');
            celda.className = 'mt-cal-celda';
            if (dia < 1 || dia > diasMes) { celda.classList.add('fuera'); grid.appendChild(celda); continue; }

            const fecha = prefijo + pad(dia);
            if (fecha === hoyStr) celda.classList.add('hoy');
            celda.innerHTML = '<div class="mt-cal-num"><span>' + dia + '</span></div>';
            const cont = document.createElement('div');
            cont.className = 'mt-cal-eventos';
            const delDia = EVENTOS.filter(e => e.fecha === fecha).sort(orden);
            delDia.forEach((e, idx) => cont.appendChild(botonEvento(e, idx >= MAX_POR_DIA)));
            if (delDia.length > MAX_POR_DIA) {
                const mas = document.createElement('button');
                mas.type = 'button';
                mas.className = 'mt-cal-mas';
                mas.textContent = '+' + (delDia.length - MAX_POR_DIA) + ' más';
                mas.addEventListener('click', () => {
                    const abierta = celda.classList.toggle('expandida');
                    mas.textContent = abierta ? 'Ver menos' : '+' + (delDia.length - MAX_POR_DIA) + ' más';
                });
                cont.appendChild(mas);
            }
            celda.appendChild(cont);
            grid.appendChild(celda);
        }

        // Lista del mes (visible en celular)
        const delMes = EVENTOS.filter(e => e.fecha.startsWith(prefijo))
            .sort((a, b) => a.fecha.localeCompare(b.fecha) || orden(a, b));
        document.getElementById('mt-cal-lista-titulo').textContent = '📅 ' + MESES[calMes] + ': ' + delMes.length + (delMes.length === 1 ? ' evento' : ' eventos');
        const lista = document.getElementById('mt-cal-lista');
        lista.innerHTML = '';
        if (!delMes.length) {
            lista.innerHTML = '<div style="padding:24px; text-align:center; color:var(--text-muted); font-size:13.5px">Sin capacitaciones ni aplicaciones este mes.</div>';
        }
        delMes.forEach(e => {
            const li = document.createElement('div');
            li.className = 'mt-li';
            const d = parseInt(e.fecha.slice(8), 10);
            li.innerHTML = '<div class="fecha ' + e.tipo + '"><b>' + d + '</b><small>' + MESES_CORTOS[calMes] + '</small></div>'
                + '<div class="info"><strong></strong><span></span></div>';
            li.querySelector('strong').textContent = e.colegio;
            li.querySelector('.info span').textContent = detalle(e);
            li.addEventListener('click', () => editarColegio(e.id));
            lista.appendChild(li);
        });
    }

    // ── Contacto responsable ──
    @if($contacto)
    const CONTACTO = {!! json_encode($contacto, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    const ctModal = document.getElementById('mt-contacto');
    const ctColegio = document.getElementById('mt-ct-colegio');

    function mensajeContacto() {
        const col = ctColegio.value;
        return 'Hola ' + CONTACTO.saludo + ', solicito de tu amable apoyo para poder revisar el estatus de Meta '
            + (col ? 'del colegio ' + col : 'de este colegio') + '.\nQuedo atento a tus comentarios.';
    }
    function actualizarContacto() {
        const msg = mensajeContacto();
        const asunto = 'Estatus de Meta' + (ctColegio.value ? ' — ' + ctColegio.value : '');
        document.getElementById('mt-ct-mensaje').textContent = msg;
        document.getElementById('mt-ct-correo').href = 'mailto:' + CONTACTO.correo
            + '?subject=' + encodeURIComponent(asunto) + '&body=' + encodeURIComponent(msg);
        document.getElementById('mt-ct-wa').href = 'https://wa.me/52' + CONTACTO.telefono.replace(/\D/g, '')
            + '?text=' + encodeURIComponent(msg);
    }
    ctColegio.addEventListener('change', actualizarContacto);
    window.mtContacto = abrir => {
        ctModal.classList.toggle('abierto', abrir);
        document.body.style.overflow = abrir ? 'hidden' : '';
        if (abrir) actualizarContacto();
    };
    ctModal.addEventListener('click', e => { if (e.target === ctModal) mtContacto(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && ctModal.classList.contains('abierto')) mtContacto(false); });
    @endif

    let vistaGuardada = null;
    try { vistaGuardada = localStorage.getItem('sic-meta-vista'); } catch (e) {}
    if (vistaGuardada === 'calendario') cambiarVista('calendario');

    @if($colegioConError)
        // Regresó con errores: reabrir con lo que se había escrito
        abrir(Object.assign(
            JSON.parse(document.querySelector('[data-colegio="{{ $colegioConError->id }}"]').dataset.meta),
            {!! json_encode((object) collect(old())->only(array_keys(\App\Models\SeguimientoMeta::CAMPOS))->all(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        ));
    @endif
})();
</script>
@endsection
