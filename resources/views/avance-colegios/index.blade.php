@extends('layouts.app')

@section('title', 'Avance Colegios')

@section('content')

<div style="margin-bottom:24px">
    <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
        🚀 Avance Colegios
    </h2>
    <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
        Progreso general de cada colegio, credenciales de Administrador MEE y bundles adoptados.
    </p>
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

        <div style="display:flex; align-items:center; gap:8px">
            <span style="font-size:13px; color:var(--text-muted); white-space:nowrap">↕ Ordenar por avance:</span>
            <select id="orden-avance" class="form-control" style="max-width:220px" onchange="ordenarCards()">
                <option value="">Sin ordenar</option>
                <option value="desc">Mayor a menor</option>
                <option value="asc">Menor a mayor</option>
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
<style>
    .avance-card { transition: transform .18s ease, box-shadow .18s ease; }
    .avance-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px rgba(0,0,0,.08); }
    .avance-copy-btn { background:none; border:none; cursor:pointer; padding:4px; border-radius:6px;
        line-height:1; flex-shrink:0; color:var(--text-muted); transition:all .15s; }
    .avance-copy-btn:hover { background:rgba(0,0,0,.06); color:var(--text); }
    .avance-ir-btn { display:flex; align-items:center; justify-content:center; gap:6px; padding:10px;
        background:linear-gradient(135deg, var(--accent), #ff5b4d); color:#fff; border-radius:10px;
        text-decoration:none; font-size:13px; font-weight:700; margin-top:auto;
        box-shadow:0 4px 10px rgba(226,35,26,.25); transition:all .18s; }
    .avance-ir-btn:hover { box-shadow:0 6px 16px rgba(226,35,26,.4); filter:brightness(1.05); }
    .avance-ir-btn svg { transition:transform .18s; }
    .avance-ir-btn:hover svg { transform:translateX(3px); }
</style>
<div id="colegios-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:18px">
    @php
        $nivelColors = [
            'Maternal'      => '#f59e0b',
            'Preescolar'    => '#8b5cf6',
            'Primaria'      => '#2563eb',
            'Secundaria'    => '#059669',
            'Preparatoria'  => '#dc2626',
            'Licenciatura'  => '#0d1117',
        ];
        $estadoColors = [
            'activo'    => ['bg' => '#f0fdf4', 'text' => '#16a34a', 'bar' => '#22c55e'],
            'prospecto' => ['bg' => '#fffbeb', 'text' => '#b45309', 'bar' => '#f59e0b'],
        ];
    @endphp
    @forelse($schools as $school)
    @php
        $schoolSeries  = $school->bundles->pluck('serie')->filter()->unique()->map(fn($s) => strtolower($s))->values()->toJson();
        $totalProcesos = 0;
        $totalDone     = 0;
        foreach($school->schoolLevels as $sl) {
            $totalProcesos += $sl->processes->count();
            $totalDone += $sl->processes->where('status', 'done')->count();
        }
        $pct = $totalProcesos > 0 ? round(($totalDone / $totalProcesos) * 100) : 0;

        $pctColor = $pct >= 100 ? '#10b981' : ($pct >= 70 ? '#3b82f6' : ($pct >= 40 ? '#f59e0b' : '#ef4444'));
        $estado   = $estadoColors[$school->status] ?? ['bg' => '#f8fafc', 'text' => '#64748b', 'bar' => '#94a3b8'];
        $consultorDigital = $school->schoolConsultants->where('role','digital')->first()?->consultant->user->name;
    @endphp
    <div class="school-card avance-card card" data-nombre="{{ strtolower($school->name) }}"
         data-consultor="{{ strtolower($consultorDigital ?? '') }}"
         data-estado="{{ strtolower($school->state ?? $school->city ?? '') }}"
         data-series="{{ $schoolSeries }}"
         data-pct="{{ $pct }}"
         style="display:flex; flex-direction:column; min-height:280px; overflow:hidden; border-radius:14px;">

        <div style="height:4px; background:{{ $estado['bar'] }}"></div>

        <div style="padding:18px 20px 14px">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px">
                <div style="font-family:'Bricolage Grotesque',sans-serif; font-weight:700; font-size:16px;
                            color:var(--text); line-height:1.3">
                    {{ $school->name }}
                </div>
                <span style="flex-shrink:0; font-size:11px; font-weight:700; padding:4px 10px; border-radius:999px;
                             background:{{ $estado['bg'] }}; color:{{ $estado['text'] }}; white-space:nowrap">
                    {{ ucfirst($school->status ?? 'inactivo') }}
                </span>
            </div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:4px; display:flex; align-items:center; gap:4px">
                📍 {{ $school->state ?? $school->city ?? 'Sin estado' }}
            </div>
        </div>

        <div style="padding:0 20px 18px; flex:1; display:flex; flex-direction:column; gap:14px">

            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;
                        padding:10px 12px; background:var(--surface2); border-radius:10px">
                <span style="font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:6px">
                    👤 Consultor Digital
                </span>
                <span style="font-size:12.5px; font-weight:700; color:var(--text); text-align:right">
                    {{ $consultorDigital ?? '—' }}
                </span>
            </div>

            @if($school->meeAdmins->count())
            <div style="border:1px solid var(--border); border-radius:10px; overflow:hidden">
                <div style="padding:7px 12px; background:var(--surface2); font-size:10.5px; font-weight:700;
                            text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted)">
                    🔐 Administrador MEE
                </div>
                @foreach($school->meeAdmins as $admin)
                <div style="padding:9px 12px; font-size:12px; {{ !$loop->last ? 'border-bottom:1px dashed var(--border)' : '' }}">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:5px">
                        <span style="color:var(--text-muted); white-space:nowrap">Usuario</span>
                        <div style="display:flex; align-items:center; gap:2px; min-width:0">
                            <span style="font-family:monospace; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap">{{ $admin->username }}</span>
                            <button type="button" class="avance-copy-btn" onclick="copiarTexto(this, {{ json_encode($admin->username) }})" title="Copiar usuario">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px">
                        <span style="color:var(--text-muted); white-space:nowrap">Contraseña</span>
                        <div style="display:flex; align-items:center; gap:2px; min-width:0">
                            <span style="font-family:monospace; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap">{{ $admin->password_plain }}</span>
                            <button type="button" class="avance-copy-btn" onclick="copiarTexto(this, {{ json_encode($admin->password_plain) }})" title="Copiar contraseña">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <div>
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:6px">
                    <span style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted)">
                        Progreso general
                    </span>
                    <span style="font-family:'Bricolage Grotesque',sans-serif; font-weight:800; font-size:17px; color:{{ $pctColor }}">
                        {{ $pct }}%
                    </span>
                </div>
                <div style="background:var(--surface2); border-radius:20px; height:8px; overflow:hidden">
                    <div style="height:100%; width:{{ $pct }}%; background:{{ $pctColor }};
                                border-radius:20px; transition:width .3s"></div>
                </div>
            </div>

            @if($school->schoolLevels->isNotEmpty())
            <div style="display:flex; gap:5px; flex-wrap:wrap">
                @foreach($school->schoolLevels as $sl)
                    @php $nc = $nivelColors[$sl->level->name ?? ''] ?? '#6b7280'; @endphp
                    <span style="font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:999px;
                                 background:{{ $nc }}1a; color:{{ $nc }}">
                        {{ $sl->level->name ?? '' }}
                    </span>
                @endforeach
            </div>
            @endif

            <a href="{{ route('schools.show', $school) }}" class="avance-ir-btn">
                IR
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
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

<script>
function copiarTexto(btn, texto) {
    const restaurar = (ok) => {
        const original = btn.innerHTML;
        btn.innerHTML = ok
            ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
            : original;
        setTimeout(() => { btn.innerHTML = original; }, 1200);
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto).then(() => restaurar(true)).catch(() => restaurar(false));
    } else {
        const tmp = document.createElement('textarea');
        tmp.value = texto;
        tmp.style.position = 'fixed';
        tmp.style.opacity = '0';
        document.body.appendChild(tmp);
        tmp.focus();
        tmp.select();
        try {
            document.execCommand('copy');
            restaurar(true);
        } catch (e) {
            restaurar(false);
        }
        document.body.removeChild(tmp);
    }
}

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
    document.getElementById('orden-avance').value = '';
    aplicarFiltros();
    ordenarCards();
}

document.getElementById('buscador-colegios').addEventListener('input', aplicarFiltros);
document.getElementById('filtro-series').addEventListener('change', aplicarFiltros);

// Orden por % de avance (Progreso general de cada card)
const ordenOriginalCards = Array.from(document.querySelectorAll('#colegios-grid .school-card'));

function ordenarCards() {
    const grid  = document.getElementById('colegios-grid');
    const orden = document.getElementById('orden-avance').value;

    let cards = ordenOriginalCards.slice();
    if (orden === 'desc') {
        cards.sort((a, b) => parseFloat(b.dataset.pct) - parseFloat(a.dataset.pct));
    } else if (orden === 'asc') {
        cards.sort((a, b) => parseFloat(a.dataset.pct) - parseFloat(b.dataset.pct));
    }
    cards.forEach(card => grid.appendChild(card));
}

// Si venimos del mapa del Dashboard con ?q=estado, precargar el buscador
(function() {
    const params = new URLSearchParams(window.location.search);
    const q = params.get('q');
    if (q) {
        document.getElementById('buscador-colegios').value = q;
        aplicarFiltros();
    }
})();
</script>
@endsection
