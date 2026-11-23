@extends('layouts.app')

@section('title', $nombre . ' — ' . $school->name)

@section('content')
@php
    $total = count($acciones);
    $pct = $total > 0 ? round($hechas / $total * 100) : 0;
    $val = fn ($campo) => old($campo, $campo === 'hora_capacitacion'
        ? $meta->horaCorta()
        : ($meta->{$campo} instanceof \Carbon\Carbon ? $meta->{$campo}->format('Y-m-d') : $meta->{$campo}));
@endphp

<style>
    .mg-top { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
    .mg-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:18px 22px; }
    .mg-head h1 { font-family:'Bricolage Grotesque',sans-serif; font-size:21px; font-weight:700; margin:0; line-height:1.2; }
    .mg-sub { font-size:13px; color:var(--text-muted); margin-top:4px; display:flex; gap:6px 14px; flex-wrap:wrap; align-items:center; }
    .mg-avance { min-width:220px; flex:0 1 280px; }
    .mg-avance .num { font-size:13px; font-weight:600; display:flex; justify-content:space-between; margin-bottom:6px; }
    .mg-barra { background:var(--surface2); border-radius:20px; height:8px; overflow:hidden; }
    .mg-barra div { height:100%; border-radius:20px; }

    .mg-grid { display:grid; grid-template-columns:minmax(0, 5fr) minmax(0, 6fr); gap:18px; align-items:start; margin-top:18px; }
    .mg-card-body { padding:18px 22px; }
    .mg-campos { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .mg-campos .full { grid-column:1 / -1; }
    .mg-campos label { display:block; font-size:12.5px; font-weight:600; color:var(--text-muted); margin-bottom:5px; }
    .mg-sec { grid-column:1 / -1; font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
              color:var(--text-muted); border-bottom:1px solid var(--border); padding-bottom:6px; margin-top:4px; }
    .mg-dot { display:inline-block; width:10px; height:10px; border-radius:3px; margin-right:6px; vertical-align:-1px; }

    /* Izquierda: número, título y estatus · derecha: todo el espacio para notas */
    .mg-accion { display:grid; grid-template-columns:minmax(200px, 5fr) minmax(0, 6fr); gap:14px;
                 padding:14px 22px; border-bottom:1px solid var(--border); }
    .mg-accion .izq { display:flex; flex-direction:column; gap:8px; min-width:0; }
    .mg-accion .cab { display:flex; align-items:flex-start; gap:10px; }
    .mg-accion:last-child { border-bottom:none; }
    .mg-accion .n { flex:none; width:26px; height:26px; border-radius:50%; background:var(--surface2); color:var(--text-muted);
                    display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; }
    .mg-accion.done .n { background:#dcfce7; color:#15803d; }
    .mg-accion.in_progress .n { background:#fef3c7; color:#b45309; }
    .mg-accion .titulo { font-weight:600; font-size:14px; }
    .mg-accion .quien { font-size:11.5px; color:var(--text-muted); margin-top:2px; }
    .mg-accion .notas { display:flex; }
    .mg-accion .notas textarea { flex:1; height:100%; min-height:76px; }
    .mg-accion select, .mg-accion textarea { font-size:13px; }
    .mg-accion select { padding:6px 8px; }
    .mg-accion textarea { resize:vertical; font-size:12.5px; }

    .mg-guardar { position:sticky; bottom:0; z-index:5; display:flex; justify-content:flex-end; align-items:center; gap:12px;
                  margin-top:18px; padding:12px 16px; background:var(--surface); border:1px solid var(--border);
                  border-radius:12px; box-shadow:0 -4px 16px rgba(0,0,0,.06); }
    .mg-guardar small { color:var(--text-muted); font-size:12px; margin-right:auto; }

    @media (max-width: 1100px) { .mg-grid { grid-template-columns:1fr; } }
    @media (max-width: 600px) {
        .mg-campos { grid-template-columns:1fr; }
        .mg-accion { grid-template-columns:1fr; gap:10px; padding:12px 16px; }
        .mg-accion .notas textarea { min-height:56px; }
        .mg-card-body, .mg-head { padding:16px; }
        .mg-guardar .btn { flex:1; justify-content:center; }
        .mg-guardar small { display:none; }
    }
</style>

<div class="mg-top">
    @if($puedeEditar)
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.abrirCatalogoSeguimiento()" style="margin-bottom:8px; display:inline-flex; align-items:center; gap:7px;">
            ⚙️ Personalizar acciones
            <span data-btn-seg-count="{{ $slug }}" style="font-size:11px; font-weight:700; padding:1px 7px; border-radius:999px; background:rgba(24,19,17,.08); color:var(--text);">{{ $total }}</span>
        </button>
    @endif
    <a href="{{ route('seguimiento.show', $slug) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <a href="{{ route('schools.tickets.index', $school) }}" class="btn btn-secondary btn-sm" style="margin-left:auto">🎫 Tickets</a>
    <a href="{{ route('schools.visits.index', $school) }}" class="btn btn-secondary btn-sm">📅 Sesiones</a>
</div>

@unless($puedeEditar)
<div style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:10px 16px; border-radius:8px; margin-bottom:16px; font-size:13.5px">
    👁️ <strong>Modo consulta:</strong> puedes ver la información, pero no editarla.
</div>
<style>
    fieldset:disabled .form-control { background:var(--surface2); color:var(--text); opacity:1; cursor:default; resize:none; }
    fieldset:disabled .form-control::placeholder { color:transparent; }
</style>
@endunless
<form method="POST" action="{{ route('seguimiento.guardar', [$slug, $school]) }}">
    @csrf
    {{-- Solo consulta: todos los campos quedan deshabilitados y sin botón de guardar --}}
    <fieldset @disabled(!$puedeEditar) style="border:0; padding:0; margin:0; min-width:0">

    <div class="card" style="padding:0">
        <div class="mg-head">
            <div style="min-width:0">
                <h1>{{ $icono }} {{ $puedeEditar ? 'Gestionar' : 'Consultar' }} {{ $nombre }} — {{ $school->name }}</h1>
                <div class="mg-sub">
                    @if($school->nexus_id)<span><strong>ID:</strong> {{ $school->nexus_id }}</span>@endif
                    @if($school->state)<span>📍 {{ $school->state }}</span>@endif
                    @foreach($school->levels as $nivel)
                        <span class="badge badge-info">{{ $nivel->name }}</span>
                    @endforeach
                </div>
            </div>
            <div class="mg-avance">
                <div class="num"><span>Acciones de arranque</span><span>{{ $hechas }}/{{ $total }}</span></div>
                <div class="mg-barra">
                    <div style="width:{{ $pct }}%; background:{{ $hechas === $total ? 'var(--success)' : 'var(--accent)' }}"></div>
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div style="background:#fee2e2; color:#991b1b; border-radius:10px; padding:12px 16px; font-size:13.5px; margin-top:16px">
            @foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="mg-grid">
        {{-- Datos de Meta (lo que se ve en la tabla y el calendario) --}}
        <div class="card" style="padding:0">
            <div class="card-header">
                <span class="card-title">📋 Datos de {{ $nombre }}</span>
                @if($meta->updated_at)
                    <span style="font-size:12px; color:var(--text-muted)">
                        Última edición: {{ $meta->updated_at->format('d/m/Y H:i') }}{{ $meta->updatedBy ? ' · ' . $meta->updatedBy->name : '' }}
                    </span>
                @endif
            </div>
            <div class="mg-card-body">
                <div class="mg-campos">
                    <div class="mg-sec">🔐 Acceso a la plataforma</div>
                    <div class="full">
                        <label for="administrador">Administrador</label>
                        <input type="text" name="administrador" id="administrador" class="form-control" maxlength="255"
                               value="{{ $val('administrador') }}" placeholder="Nombre de quien administra Meta en el colegio">
                    </div>
                    <div>
                        <label for="usuario">Usuario</label>
                        <input type="text" name="usuario" id="usuario" class="form-control" maxlength="255" autocomplete="off" value="{{ $val('usuario') }}">
                    </div>
                    <div>
                        <label for="contrasena">Contraseña</label>
                        <input type="text" name="contrasena" id="contrasena" class="form-control" maxlength="255" autocomplete="off" value="{{ $val('contrasena') }}">
                    </div>

                    <div class="mg-sec">📅 Capacitación y examen</div>
                    <div class="full">
                        <label for="capacitacion">Capacitación</label>
                        <input type="text" name="capacitacion" id="capacitacion" class="form-control" maxlength="255"
                               value="{{ $val('capacitacion') }}" placeholder="Ej. Capacitación a docentes de inglés">
                    </div>
                    <div>
                        <label for="fecha_capacitacion"><span class="mg-dot" style="background:#2563eb"></span>Fecha de capacitación</label>
                        <input type="date" name="fecha_capacitacion" id="fecha_capacitacion" class="form-control" value="{{ $val('fecha_capacitacion') }}">
                    </div>
                    <div>
                        <label for="hora_capacitacion">🕒 Horario de capacitación</label>
                        <input type="time" name="hora_capacitacion" id="hora_capacitacion" class="form-control" value="{{ $val('hora_capacitacion') }}">
                    </div>
                    <div class="full">
                        <label for="fecha_aplicacion"><span class="mg-dot" style="background:#ea580c"></span>Fecha de aplicación del examen</label>
                        <input type="date" name="fecha_aplicacion" id="fecha_aplicacion" class="form-control" value="{{ $val('fecha_aplicacion') }}">
                    </div>
                    <div class="full">
                        <label for="notas">Notas</label>
                        <textarea name="notas" id="notas" class="form-control" rows="3" maxlength="2000">{{ $val('notas') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Acciones de arranque --}}
        <div class="card" style="padding:0">
            <div class="card-header">
                <span class="card-title">🚀 Acciones de arranque</span>
                <span style="font-size:13px; color:var(--text-muted)">{{ $hechas }}/{{ $total }} completadas</span>
            </div>
            @if($total === 0)
                <div style="padding:20px 22px;">
                    <div style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:12px 15px; border-radius:10px; font-size:13.5px; display:flex; flex-direction:column; gap:10px;">
                        <span>ℹ️ Las acciones de arranque de <strong>{{ $nombre }}</strong> aún no están disponibles. Mientras tanto puedes consultar o editar los datos y fechas de este colegio.</span>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">Ver colegio →</a>
                            @if($puedeEditar)
                                <button type="button" class="btn btn-primary btn-sm" onclick="window.abrirCatalogoSeguimiento('{{ $slug }}')">⚙️ Configurar acciones</button>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                @foreach($acciones as $clave => $etiqueta)
                @php
                    $guardada = $guardadas[$clave] ?? null;
                    $status = old("acciones.$clave.status", $guardada->status ?? 'pending');
                @endphp
                <div class="mg-accion {{ $status }}">
                    <div class="izq">
                        <div class="cab">
                            <div class="n">{{ $status === 'done' ? '✓' : $loop->iteration }}</div>
                            <div style="min-width:0">
                                <div class="titulo">{{ $etiqueta }}</div>
                                @if($guardada?->completed_at)
                                    <div class="quien">Completado el {{ $guardada->completed_at->format('d/m/Y') }} · {{ $guardada->completedBy->name ?? '—' }}</div>
                                @endif
                            </div>
                        </div>
                        <select name="acciones[{{ $clave }}][status]" class="form-control" aria-label="Estatus de {{ $etiqueta }}"
                                onchange="this.closest('.mg-accion').className = 'mg-accion ' + this.value">
                            @foreach(\App\Models\SeguimientoAccion::STATUS as $valor => $texto)
                                <option value="{{ $valor }}" @selected($status === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="notas">
                        <textarea name="acciones[{{ $clave }}][notes]" rows="3" maxlength="2000" placeholder="Notas (opcional)..."
                                  class="form-control" aria-label="Notas de {{ $etiqueta }}">{{ old("acciones.$clave.notes", $guardada->notes ?? '') }}</textarea>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>

    </fieldset>

    @if($puedeEditar)
    <div class="mg-guardar">
        <small>Se guardan los datos de {{ $nombre }} y las acciones de arranque juntos.</small>
        <a href="{{ route('seguimiento.show', $slug) }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </div>
    @endif
</form>

@if($puedeEditar)
    @include('seguimiento.partials._catalogo_acciones_modal')
@endif
@endsection
