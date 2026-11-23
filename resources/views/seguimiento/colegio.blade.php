@extends('layouts.app')

@section('title', $nombre . ' — ' . $school->name)

@section('content')
<style>
    .seg-guardar-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 15px 24px;
        background: var(--surface2);
        border-top: 2px solid var(--border);
        border-radius: 0 0 12px 12px;
    }
    .seg-guardar-bar.is-standalone {
        margin-top: 16px;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(24, 19, 17, 0.05);
    }
    .seg-guardar-hint {
        font-size: 12.5px;
        color: var(--text-muted);
    }
    .seg-guardar-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
    }
</style>
@php
    $total = $totalAcciones ?? count($acciones);
    $porNivel = $porNivel ?? false;
    $pctGlobal = $total > 0 ? round($hechas / $total * 100) : 0;
@endphp

<div style="margin-bottom:20px; display:flex; gap:8px; flex-wrap:wrap">
    <a href="{{ route('seguimiento.show', $slug) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    @if($puedeEditar)
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.abrirCatalogoSeguimiento()" style="display:inline-flex; align-items:center; gap:7px;">
            ⚙️ Personalizar acciones
            <span data-btn-seg-count="{{ $slug }}" style="font-size:11px; font-weight:700; padding:1px 7px; border-radius:999px; background:rgba(24,19,17,.08); color:var(--text);">{{ count($acciones) }}</span>
        </button>
    @endif
    <a href="{{ route('schools.tickets.index', ['school' => $school, 'producto' => $nombre]) }}" class="btn btn-secondary btn-sm" style="margin-left:auto">🎫 Tickets</a>
    <a href="{{ route('schools.visits.index', ['school' => $school, 'producto' => $nombre]) }}" class="btn btn-secondary btn-sm">📅 Sesiones</a>
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

@if($porNivel)
@php
    $nivelesColegio = $nivelesColegio ?? $niveles;
    $nivelesMapa = \App\Helpers\Niveles::map();
    $accionKeys = array_keys($acciones);
    $totalPorNivel = count($acciones);
    $primerIncompletoId = null;
    foreach ($niveles as $nTmp) {
        $hechasTmp = ($guardadasPorNivel[$nTmp->id] ?? collect())->toBase()->only($accionKeys)->where('status', 'done')->count();
        if ($hechasTmp < $totalPorNivel) {
            $primerIncompletoId = $nTmp->id;
            break;
        }
    }
    if ($primerIncompletoId === null && $niveles->isNotEmpty()) {
        $primerIncompletoId = $niveles->first()->id;
    }
@endphp
<style>
    .seg-nivel-panel { margin-bottom:14px; overflow:hidden; }
    .seg-nivel-summary {
        list-style:none; cursor:pointer; user-select:none; gap:12px; flex-wrap:wrap;
        transition:background .15s;
    }
    .seg-nivel-summary::-webkit-details-marker { display:none; }
    .seg-nivel-summary:hover { background:var(--surface2); }
    .seg-chevron {
        display:inline-flex; align-items:center; justify-content:center;
        width:22px; height:22px; border-radius:6px; background:var(--surface2);
        color:var(--text-muted); font-size:13px; transition:transform .18s ease;
    }
    .seg-nivel-panel[open] > .seg-nivel-summary .seg-chevron { transform:rotate(90deg); }
    .seg-nivel-meta { display:flex; align-items:center; gap:10px; margin-left:auto; flex-wrap:wrap; }
    .seg-mini-bar { width:90px; height:6px; border-radius:20px; background:var(--surface2); overflow:hidden; }
    .seg-mini-bar > div { height:100%; border-radius:20px; transition:width .25s ease; }
    .seg-nivel-pill { border:none; cursor:pointer; font:inherit; }
    .seg-nivel-pill:hover { filter:brightness(.95); }
</style>

<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">{{ $icono }} {{ $nombre }} — {{ $school->name }}</span>
        <span style="font-size:13px; color:var(--text-muted)">
            {{ $hechas }}/{{ $total }} completadas ({{ $pctGlobal }}%)
        </span>
    </div>

    <div style="padding:16px 24px 0">
        <div style="background:var(--surface2); border-radius:20px; height:7px; overflow:hidden">
            <div style="height:100%; width:{{ $pctGlobal }}%; border-radius:20px;
                        background:{{ $total > 0 && $hechas === $total ? 'var(--success)' : 'var(--accent)' }}"></div>
        </div>
    </div>

    @if($nivelesColegio->isEmpty())
        <div style="padding:18px 24px 22px">
            <div style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:12px 16px; border-radius:8px; font-size:13.5px">
                ⚠️ Este colegio no tiene niveles registrados.
                @if($puedeEditar)
                    Agrega sus niveles en
                    <a href="{{ route('schools.edit', $school) }}" style="color:#92400e; text-decoration:underline">Editar colegio</a>
                    para habilitar el seguimiento por nivel.
                @endif
            </div>
        </div>
    @else
        @if($niveles->isNotEmpty())
            <div style="padding:16px 24px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px">
                    <span style="font-size:13px; color:var(--text-muted); margin-right:4px">Niveles:</span>
                    @foreach($niveles as $nivel)
                        @php
                            $canonico = \App\Helpers\Niveles::canonico($nivel->name);
                            $nivelIcono = $canonico ? ($nivelesMapa[$canonico]['icono'] ?? '🎓') : '🎓';
                            $guardadasNivel = $guardadasPorNivel[$nivel->id] ?? collect();
                            $hechasNivel = $guardadasNivel->toBase()->only($accionKeys)->where('status', 'done')->count();
                            $nivelCompleto = $totalPorNivel > 0 && $hechasNivel === $totalPorNivel;
                        @endphp
                        <button type="button"
                                class="badge {{ $nivelCompleto ? 'badge-success' : 'badge-info' }} seg-nivel-pill"
                                data-ir-nivel="{{ $nivel->id }}">
                            {{ $nivelIcono }} {{ $nivel->name }} ({{ $hechasNivel }}/{{ $totalPorNivel }})
                        </button>
                    @endforeach
                </div>
                <div style="display:flex; gap:6px; flex-wrap:wrap">
                    @if($puedeEditar)
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-toggle-niveles">⚙️ Elegir niveles</button>
                    @endif
                    <button type="button" class="btn btn-secondary btn-sm" data-seg-expand="all">Expandir todos</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-seg-expand="none">Contraer todos</button>
                </div>
            </div>
        @else
            <div style="padding:18px 24px {{ $puedeEditar ? '12px' : '22px' }}">
                <div style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:12px 16px; border-radius:8px; font-size:13.5px">
                    ⚠️
                    @if($puedeEditar)
                        No hay niveles seleccionados para {{ $nombre }}. Marca abajo qué niveles de este colegio llevan {{ $nombre }}.
                    @else
                        No hay niveles seleccionados para {{ $nombre }} en este colegio.
                    @endif
                </div>
            </div>
        @endif

        @if($puedeEditar)
        <div id="seg-config-niveles"
             style="display:{{ $niveles->isEmpty() ? 'block' : 'none' }}; padding:14px 24px 20px; border-top:1px solid var(--border); background:var(--surface2)">
            <form method="POST" action="{{ route('seguimiento.niveles', [$slug, $school]) }}">
                @csrf
                <div style="font-size:13px; font-weight:700; margin-bottom:4px">
                    Selecciona los niveles que llevan {{ $nombre }} en este colegio:
                </div>
                <div style="font-size:12px; color:var(--text-muted); margin-bottom:10px">
                    Solo aparecerán los paneles y acciones de arranque de los niveles marcados.
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px">
                    @foreach($nivelesColegio as $nivelCol)
                        @php
                            $canonicoCol = \App\Helpers\Niveles::canonico($nivelCol->name);
                            $nivelIconoCol = $canonicoCol ? ($nivelesMapa[$canonicoCol]['icono'] ?? '🎓') : '🎓';
                            $activoCol = $niveles->contains('id', $nivelCol->id);
                        @endphp
                        <label style="display:inline-flex; align-items:center; gap:7px; cursor:pointer;
                                      padding:7px 12px; border:1px solid var(--border); border-radius:8px; background:#fff; font-size:13px">
                            <input type="checkbox" name="niveles[]" value="{{ $nivelCol->id }}"
                                   {{ $activoCol ? 'checked' : '' }}
                                   style="accent-color:var(--accent)">
                            {{ $nivelIconoCol }} {{ $nivelCol->name }}
                        </label>
                    @endforeach
                </div>
                <div style="display:flex; gap:8px; justify-content:flex-end">
                    @if($niveles->isNotEmpty())
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-cancelar-niveles">Cancelar</button>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">Guardar niveles</button>
                </div>
            </form>
        </div>
        @endif
    @endif
</div>

@if($niveles->isNotEmpty() && $totalPorNivel > 0)
<form method="POST" action="{{ route('seguimiento.guardar', [$slug, $school]) }}">
    @csrf
    @foreach($niveles as $nivel)
        @php
            $canonico = \App\Helpers\Niveles::canonico($nivel->name);
            $nivelIcono = $canonico ? ($nivelesMapa[$canonico]['icono'] ?? '🎓') : '🎓';
            $guardadasNivel = $guardadasPorNivel[$nivel->id] ?? collect();
            $hechasNivel = $guardadasNivel->toBase()->only($accionKeys)->where('status', 'done')->count();
            $pctNivel = $totalPorNivel > 0 ? round($hechasNivel / $totalPorNivel * 100) : 0;
            $nivelCompleto = $totalPorNivel > 0 && $hechasNivel === $totalPorNivel;
            $abiertoInicial = $nivel->id === $primerIncompletoId;
        @endphp
        <details class="card seg-nivel-panel" id="panel-nivel-{{ $nivel->id }}" data-nivel-id="{{ $nivel->id }}" @if($abiertoInicial) open @endif>
            <summary class="card-header seg-nivel-summary">
                <div style="display:flex; align-items:center; gap:10px">
                    <span class="seg-chevron" aria-hidden="true">▸</span>
                    <span class="card-title">{{ $nivelIcono }} {{ $nivel->name }}</span>
                </div>
                <div class="seg-nivel-meta">
                    <div class="seg-mini-bar">
                        <div style="width:{{ $pctNivel }}%; background:{{ $nivelCompleto ? 'var(--success)' : 'var(--accent)' }}"></div>
                    </div>
                    <span style="font-size:13px; color:var(--text-muted)">{{ $hechasNivel }}/{{ $totalPorNivel }} completadas</span>
                    @if($nivelCompleto)
                        <span class="badge badge-success">✅ Completo</span>
                    @endif
                </div>
            </summary>

            <fieldset @disabled(!$puedeEditar) style="border:0; padding:0; margin:0; min-width:0">
                <div style="overflow-x:auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Acción</th>
                                <th style="width:170px">Estatus</th>
                                <th style="width:170px">Completado</th>
                                <th style="min-width:220px">Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($acciones as $clave => $etiqueta)
                            @php $guardada = $guardadasNivel[$clave] ?? null; @endphp
                            <tr>
                                <td style="color:var(--text-muted); font-size:13px">{{ $loop->iteration }}</td>
                                <td><strong style="font-size:13.5px">{{ $etiqueta }}</strong></td>
                                <td>
                                    <select name="acciones[{{ $nivel->id }}][{{ $clave }}][status]" class="form-control" style="padding:6px 8px; font-size:13px">
                                        @foreach(\App\Models\SeguimientoAccion::STATUS as $valor => $texto)
                                            <option value="{{ $valor }}" @selected(($guardada->status ?? 'pending') === $valor)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="font-size:12px; color:var(--text-muted)">
                                    @if($guardada?->completed_at)
                                        {{ $guardada->completed_at->format('d/m/Y') }}
                                        <div>{{ $guardada->completedBy->name ?? '—' }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <textarea name="acciones[{{ $nivel->id }}][{{ $clave }}][notes]" rows="2" maxlength="2000" placeholder="Sin notas..."
                                              class="form-control" style="font-size:12.5px; min-height:38px; resize:vertical">{{ $guardada->notes ?? '' }}</textarea>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </fieldset>
        </details>
    @endforeach

    @if($puedeEditar)
    <div class="seg-guardar-bar is-standalone">
        <span class="seg-guardar-hint">💾 Guarda los cambios al terminar de actualizar estatus o notas por nivel.</span>
        <div class="seg-guardar-actions">
            <a href="{{ route('seguimiento.show', $slug) }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </div>
    @endif
</form>
@elseif($niveles->isNotEmpty())
<div style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:14px 18px; border-radius:10px; font-size:13.5px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
    <span>ℹ️ Las acciones de arranque de <strong>{{ $nombre }}</strong> aún no están disponibles para capturar.</span>
    <div style="display:flex; gap:8px; flex-wrap:wrap">
        <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">Ver colegio →</a>
        @if($puedeEditar)
            <button type="button" class="btn btn-primary btn-sm" onclick="window.abrirCatalogoSeguimiento('{{ $slug }}')">⚙️ Configurar acciones</button>
        @endif
    </div>
</div>
@endif

<script>
(function () {
    const storageKey = @json("seg-niveles-abiertos-{$slug}-{$school->id}");
    const panels = Array.from(document.querySelectorAll('.seg-nivel-panel'));
    if (!panels.length) return;

    function saveOpenState() {
        try {
            const abiertos = panels.filter(p => p.open).map(p => p.dataset.nivelId);
            sessionStorage.setItem(storageKey, JSON.stringify(abiertos));
        } catch (e) {}
    }

    try {
        const raw = sessionStorage.getItem(storageKey);
        if (raw !== null) {
            const abiertos = JSON.parse(raw);
            if (Array.isArray(abiertos)) {
                panels.forEach(p => {
                    p.open = abiertos.includes(p.dataset.nivelId);
                });
            }
        }
    } catch (e) {}

    panels.forEach(p => p.addEventListener('toggle', saveOpenState));

    document.querySelectorAll('[data-seg-expand]').forEach(btn => {
        btn.addEventListener('click', () => {
            const openAll = btn.dataset.segExpand === 'all';
            panels.forEach(p => { p.open = openAll; });
            saveOpenState();
        });
    });

    document.querySelectorAll('[data-ir-nivel]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById('panel-nivel-' + btn.dataset.irNivel);
            if (!target) return;
            target.open = true;
            saveOpenState();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
})();
</script>

<script>
(function () {
    const configBox = document.getElementById('seg-config-niveles');
    const toggleBtn = document.getElementById('btn-toggle-niveles');
    const cancelBtn = document.getElementById('btn-cancelar-niveles');
    if (configBox && toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            configBox.style.display = configBox.style.display === 'none' ? 'block' : 'none';
        });
    }
    if (configBox && cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            configBox.style.display = 'none';
        });
    }
})();
</script>

@else
<form method="POST" action="{{ route('seguimiento.guardar', [$slug, $school]) }}">
    @csrf
    <fieldset @disabled(!$puedeEditar) style="border:0; padding:0; margin:0; min-width:0">
    <div class="card">
        <div class="card-header">
            <span class="card-title">{{ $icono }} {{ $nombre }} — {{ $school->name }}</span>
            <span style="font-size:13px; color:var(--text-muted)">{{ $hechas }}/{{ $total }} completadas</span>
        </div>

        <div style="padding:16px 24px 0; display:flex; flex-wrap:wrap; align-items:center; gap:6px">
            <span style="font-size:13px; color:var(--text-muted); margin-right:4px">Niveles:</span>
            @forelse($school->levels as $nivel)
                <span class="badge badge-info">{{ $nivel->name }}</span>
            @empty
                <span style="font-size:13px; color:var(--text-muted)">Sin niveles registrados</span>
            @endforelse
        </div>

        <div style="padding:16px 24px 0">
            <div style="background:var(--surface2); border-radius:20px; height:7px; overflow:hidden">
                <div style="height:100%; width:{{ ($total > 0 ? round($hechas / $total * 100) : 0) }}%; border-radius:20px;
                            background:{{ ($total > 0 && $hechas === $total) ? 'var(--success)' : 'var(--accent)' }}"></div>
            </div>
        </div>

        @if(empty($acciones))
        <div style="padding:20px 24px">
            <div style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:14px 18px; border-radius:10px; font-size:13.5px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
                <span>ℹ️ Las acciones de arranque de <strong>{{ $nombre }}</strong> aún no están disponibles para capturar.</span>
                <div style="display:flex; gap:8px; flex-wrap:wrap">
                    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">Ver colegio →</a>
                    @if($puedeEditar)
                        <button type="button" class="btn btn-primary btn-sm" onclick="window.abrirCatalogoSeguimiento('{{ $slug }}')">⚙️ Configurar acciones</button>
                    @endif
                </div>
            </div>
        </div>
        @else
        <div style="overflow-x:auto">
        <table class="table" style="margin-top:8px">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Acción</th>
                    <th style="width:170px">Estatus</th>
                    <th style="width:170px">Completado</th>
                    <th style="min-width:220px">Notas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($acciones as $clave => $etiqueta)
                @php $guardada = $guardadas[$clave] ?? null; @endphp
                <tr>
                    <td style="color:var(--text-muted); font-size:13px">{{ $loop->iteration }}</td>
                    <td><strong style="font-size:13.5px">{{ $etiqueta }}</strong></td>
                    <td>
                        <select name="acciones[{{ $clave }}][status]" class="form-control" style="padding:6px 8px; font-size:13px">
                            @foreach(\App\Models\SeguimientoAccion::STATUS as $valor => $texto)
                                <option value="{{ $valor }}" @selected(($guardada->status ?? 'pending') === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td style="font-size:12px; color:var(--text-muted)">
                        @if($guardada?->completed_at)
                            {{ $guardada->completed_at->format('d/m/Y') }}
                            <div>{{ $guardada->completedBy->name ?? '—' }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <textarea name="acciones[{{ $clave }}][notes]" rows="2" maxlength="2000" placeholder="Sin notas..."
                                  class="form-control" style="font-size:12.5px; min-height:38px; resize:vertical">{{ $guardada->notes ?? '' }}</textarea>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        @if($puedeEditar)
        <div class="seg-guardar-bar">
            <span class="seg-guardar-hint">💾 Guarda los cambios al terminar de actualizar estatus o notas de arranque.</span>
            <div class="seg-guardar-actions">
                <a href="{{ route('seguimiento.show', $slug) }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
        @endif
        @endif
    </div>
    </fieldset>
</form>
@endif

@if($puedeEditar)
    @include('seguimiento.partials._catalogo_acciones_modal')
@endif
@endsection
