@extends('layouts.app')

@section('title', 'Bitácora de Acciones')

@section('content')

@php
$tiposConfig = [
    'proceso'  => ['label' => 'Proceso',       'badge' => 'badge-info',    'icon' => '🔄'],
    'arranque' => ['label' => 'Arranque 100%',  'badge' => 'badge-success', 'icon' => '🎉'],
    'visita'   => ['label' => 'Visita',         'badge' => 'badge-warning', 'icon' => '📅'],
    'ticket'   => ['label' => 'Ticket',         'badge' => 'badge-danger',  'icon' => '🎫'],
    'bundle'   => ['label' => 'Bundles',        'badge' => 'badge-gray',    'icon' => '📚'],
    'docente'  => ['label' => 'Docentes',       'badge' => 'badge-gray',    'icon' => '👨‍🏫'],
];
@endphp

{{-- Header --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px">
    <div>
        <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
            📋 Bitácora de Acciones
        </h2>
        <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
            Registro automático de cambios en el sistema
        </p>
    </div>
    <div style="font-size:12px; color:var(--text-muted)">
        {{ $logs->total() }} registro(s) · Mostrando {{ $logs->count() }} de {{ $logs->total() }}
    </div>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" action="{{ route('bitacora.index') }}"
              style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">

            <div style="flex:1; min-width:160px">
                <label style="font-size:12px; font-weight:600; color:var(--text-muted);
                               text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:4px">
                    Tipo
                </label>
                <select name="tipo" class="form-control">
                    <option value="">Todos</option>
                    @foreach($tiposConfig as $key => $cfg)
                    <option value="{{ $key }}" {{ request('tipo') === $key ? 'selected' : '' }}>
                        {{ $cfg['icon'] }} {{ $cfg['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div style="flex:2; min-width:180px">
                <label style="font-size:12px; font-weight:600; color:var(--text-muted);
                               text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:4px">
                    Colegio
                </label>
                <select name="school_id" class="form-control">
                    <option value="">Todos los colegios</option>
                    @foreach($schools as $s)
                    <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width:130px">
                <label style="font-size:12px; font-weight:600; color:var(--text-muted);
                               text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:4px">
                    Desde
                </label>
                <input type="date" name="desde" class="form-control" value="{{ request('desde') }}">
            </div>

            <div style="min-width:130px">
                <label style="font-size:12px; font-weight:600; color:var(--text-muted);
                               text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:4px">
                    Hasta
                </label>
                <input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}">
            </div>

            <div style="display:flex; gap:8px">
                <button type="submit" class="btn btn-primary" style="padding:9px 18px">
                    🔍 Filtrar
                </button>
                <a href="{{ route('bitacora.index') }}" class="btn btn-secondary" style="padding:9px 14px">
                    ✕
                </a>
            </div>
        </form>

        @if(!request('desde') && !request('hasta'))
        <div style="margin-top:10px; font-size:12px; color:var(--text-muted)">
            📅 Mostrando los últimos 30 días. Usa los filtros de fecha para ver más historial.
        </div>
        @endif
    </div>
</div>

{{-- Tabla --}}
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th style="width:140px">Fecha</th>
                <th style="width:110px">Tipo</th>
                <th>Acción</th>
                <th style="width:180px">Colegio</th>
                <th style="width:160px">Usuario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            @php
                $cfg = $tiposConfig[$log->tipo] ?? ['label' => $log->tipo, 'badge' => 'badge-gray', 'icon' => '📝'];
                $displayIcon = $log->icono ?: $cfg['icon'];
                // Para procesos, ajustar color del badge según el ícono almacenado
                if ($log->tipo === 'proceso') {
                    if ($log->icono === '✅') $cfg['badge'] = 'badge-success';
                    elseif ($log->icono === '↩️') $cfg['badge'] = 'badge-warning';
                }
            @endphp
            <tr>
                <td style="font-size:12px; color:var(--text-muted); white-space:nowrap">
                    <div style="font-weight:600; color:var(--text)">
                        {{ $log->created_at->format('d/m/Y') }}
                    </div>
                    <div>{{ $log->created_at->format('H:i') }}</div>
                </td>
                <td>
                    <span class="badge {{ $cfg['badge'] }}" style="font-size:11px">
                        {{ $displayIcon }} {{ $cfg['label'] }}
                    </span>
                </td>
                <td style="font-size:13px; line-height:1.5">
                    {{ $log->descripcion }}
                </td>
                <td style="font-size:13px">
                    @if($log->school)
                        <a href="{{ route('schools.show', $log->school) }}"
                           style="color:var(--accent); text-decoration:none; font-weight:500">
                            {{ $log->school->name }}
                        </a>
                        <div style="font-size:11px; color:var(--text-muted)">{{ $log->school->state ?? $log->school->city }}</div>
                    @else
                        <span style="color:var(--text-muted)">—</span>
                    @endif
                </td>
                <td style="font-size:13px">
                    @if($log->user)
                        <div style="font-weight:500">{{ $log->user->name }}</div>
                        <div style="font-size:11px; color:var(--text-muted)">
                            {{ $log->user->getRoleNames()->first() ?? '' }}
                        </div>
                    @else
                        <span style="color:var(--text-muted)">Sistema</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; padding:60px; color:var(--text-muted)">
                    <div style="font-size:32px; margin-bottom:12px">📋</div>
                    <div style="font-weight:600; margin-bottom:4px">Sin registros en este período</div>
                    <div style="font-size:12px">Las acciones del equipo aparecerán aquí automáticamente.</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($logs->hasPages())
    <div style="padding:16px 20px; border-top:1px solid var(--border); display:flex;
                justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px">
        <div style="font-size:13px; color:var(--text-muted)">
            Página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}
            · {{ $logs->total() }} registros totales
        </div>
        <div style="display:flex; gap:4px">
            @if($logs->onFirstPage())
                <span style="padding:6px 12px; border:1px solid var(--border); border-radius:6px;
                             font-size:13px; color:var(--text-muted)">← Anterior</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}"
                   style="padding:6px 12px; border:1px solid var(--border); border-radius:6px;
                          font-size:13px; text-decoration:none; color:var(--text); transition:background 0.15s"
                   onmouseover="this.style.background='var(--surface2)'"
                   onmouseout="this.style.background=''">← Anterior</a>
            @endif

            @foreach($logs->getUrlRange(max(1,$logs->currentPage()-2), min($logs->lastPage(),$logs->currentPage()+2)) as $page => $url)
                @if($page == $logs->currentPage())
                    <span style="padding:6px 12px; background:var(--accent); color:#fff;
                                 border-radius:6px; font-size:13px; font-weight:600">{{ $page }}</span>
                @else
                    <a href="{{ $url }}"
                       style="padding:6px 12px; border:1px solid var(--border); border-radius:6px;
                              font-size:13px; text-decoration:none; color:var(--text)"
                       onmouseover="this.style.background='var(--surface2)'"
                       onmouseout="this.style.background=''">{{ $page }}</a>
                @endif
            @endforeach

            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}"
                   style="padding:6px 12px; border:1px solid var(--border); border-radius:6px;
                          font-size:13px; text-decoration:none; color:var(--text)"
                   onmouseover="this.style.background='var(--surface2)'"
                   onmouseout="this.style.background=''">Siguiente →</a>
            @else
                <span style="padding:6px 12px; border:1px solid var(--border); border-radius:6px;
                             font-size:13px; color:var(--text-muted)">Siguiente →</span>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
