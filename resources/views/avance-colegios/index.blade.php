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
<div id="colegios-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:16px">
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
    @endphp
    <div class="school-card card" data-nombre="{{ strtolower($school->name) }}"
         data-consultor="{{ strtolower($school->schoolConsultants->where('role','digital')->first()?->consultant->user->name ?? '') }}"
         data-estado="{{ strtolower($school->state ?? $school->city ?? '') }}"
         data-series="{{ $schoolSeries }}"
         data-pct="{{ $pct }}"
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
            <div style="margin-bottom:12px; display:flex; flex-direction:column; gap:6px">
                @foreach($school->meeAdmins as $admin)
                <div style="background:var(--surface2); border-radius:8px; padding:8px 10px; font-size:12px">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:5px">
                        <span style="color:var(--text-muted); white-space:nowrap">Usuario</span>
                        <div style="display:flex; align-items:center; gap:5px; min-width:0">
                            <span style="font-family:monospace; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap">{{ $admin->username }}</span>
                            <button type="button" onclick="copiarTexto(this, {{ json_encode($admin->username) }})"
                                    style="background:none; border:none; cursor:pointer; padding:2px; line-height:1; flex-shrink:0; color:var(--text-muted)"
                                    title="Copiar usuario">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px">
                        <span style="color:var(--text-muted); white-space:nowrap">Contraseña</span>
                        <div style="display:flex; align-items:center; gap:5px; min-width:0">
                            <span style="font-family:monospace; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap">{{ $admin->password_plain }}</span>
                            <button type="button" onclick="copiarTexto(this, {{ json_encode($admin->password_plain) }})"
                                    style="background:none; border:none; cursor:pointer; padding:2px; line-height:1; flex-shrink:0; color:var(--text-muted)"
                                    title="Copiar contraseña">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

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
