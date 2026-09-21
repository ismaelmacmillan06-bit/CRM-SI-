@extends('layouts.app')

@section('title', 'Producción')

@section('content')

<style>
    .pd-stats { display:grid; grid-template-columns:repeat(4, 1fr) 1.3fr; gap:12px; margin-bottom:20px; }
    @media (max-width:1080px){ .pd-stats{ grid-template-columns:repeat(2,1fr); } }
    @media (max-width:640px){ .pd-stats{ grid-template-columns:1fr 1fr; } }
    .pd-tile { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px 18px;
               display:flex; flex-direction:column; gap:6px; box-shadow:0 1px 4px rgba(0,0,0,.05); }
    .pd-tile--accent { background:#fdecea; border-color:transparent; }
    .pd-tile-label { font-size:12px; color:var(--text-muted); font-weight:600; }
    .pd-tile-value { font-family:'Bricolage Grotesque',sans-serif; font-size:26px; font-weight:800; letter-spacing:-.01em; }
    .pd-tile--accent .pd-tile-value { color:var(--accent2); }

    .pd-charts { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
    @media (max-width:1080px){ .pd-charts{ grid-template-columns:1fr; } }
    .pd-chart { display:flex; flex-direction:column; gap:8px; }
    .pd-chart-row { display:grid; grid-template-columns:150px 1fr 46px; align-items:center; gap:10px;
                    cursor:pointer; border-radius:6px; padding:3px 4px; }
    .pd-chart-row:hover { background:var(--surface2); }
    .pd-chart-row-label { font-size:12.5px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .pd-chart-row.is-active .pd-chart-row-label { color:var(--text); font-weight:700; }
    .pd-chart-track { position:relative; height:14px; background:var(--surface2); border-radius:4px; overflow:hidden; }
    .pd-chart-fill { position:absolute; inset:0 auto 0 0; height:100%; border-radius:4px; background:var(--accent); transition:width .25s ease; }
    .pd-chart-row-value { font-size:12px; color:var(--text-muted); text-align:right; font-variant-numeric:tabular-nums; }

    .pd-filters-grid { display:grid; grid-template-columns:1.4fr 1.4fr .8fr .8fr .8fr auto; gap:14px; align-items:start; }
    @media (max-width:1080px){ .pd-filters-grid{ grid-template-columns:1fr 1fr; } }
    @media (max-width:640px){ .pd-filters-grid{ grid-template-columns:1fr; } }
    .pd-field { display:flex; flex-direction:column; gap:6px; min-width:0; }
    .pd-field label { font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.03em; }
    .pd-field select, .pd-field input[type=text] { width:100%; height:36px; padding:0 10px; border-radius:8px;
        border:1px solid var(--border); background:var(--surface); color:var(--text); font-size:13px; font-family:inherit; }
    .pd-field select:focus, .pd-field input[type=text]:focus { outline:2px solid var(--accent); outline-offset:-1px; }

    .pd-combo { position:relative; }
    .pd-combo-list { position:absolute; top:calc(100% + 4px); left:0; right:0; max-height:260px; overflow-y:auto;
        background:var(--surface); border:1px solid var(--border); border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,.12); z-index:30; padding:6px; }
    .pd-combo-option { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px;
        border-radius:8px; cursor:pointer; font-size:13px; }
    .pd-combo-option:hover, .pd-combo-option.is-highlighted { background:#fdecea; }
    .pd-combo-option-count { font-size:11.5px; color:var(--text-muted); flex-shrink:0; }
    .pd-combo-empty { padding:10px; font-size:12.5px; color:var(--text-muted); }

    .pd-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:4px; }
    .pd-chip { display:inline-flex; align-items:center; gap:6px; background:#fdecea; color:var(--accent2);
        border-radius:999px; padding:4px 6px 4px 12px; font-size:12.5px; font-weight:600; }
    .pd-chip button { width:16px; height:16px; border:none; background:transparent; color:inherit; cursor:pointer;
        border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; line-height:1; opacity:.75; }
    .pd-chip button:hover { opacity:1; background:rgba(0,0,0,.08); }

    .pd-results-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:14px; }
    .pd-toggle { display:flex; background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:2px; }
    .pd-toggle-btn { border:none; background:transparent; color:var(--text-muted); font-size:12.5px; font-weight:700;
        padding:6px 12px; border-radius:6px; cursor:pointer; }
    .pd-toggle-btn.is-active { background:var(--surface); color:var(--text); box-shadow:0 1px 3px rgba(0,0,0,.08); }

    .pd-table-wrap { overflow-x:auto; border:1px solid var(--border); border-radius:10px; }
    table.pd-table { width:100%; border-collapse:collapse; font-size:13px; }
    table.pd-table thead th { text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.03em;
        color:var(--text-muted); background:var(--surface2); padding:10px 12px; border-bottom:1px solid var(--border); white-space:nowrap; }
    table.pd-table tbody tr { border-bottom:1px solid var(--border); }
    table.pd-table tbody tr:last-child { border-bottom:none; }
    table.pd-table tbody tr.pd-group-row:hover { background:var(--surface2); }
    table.pd-table td { padding:10px 12px; vertical-align:top; color:var(--text); }
    .pd-cell-num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .pd-col-id { color:var(--text-muted); font-size:11.5px; }
    .pd-tag-row { display:flex; flex-wrap:wrap; gap:4px; }
    .pd-tag { display:inline-block; padding:2px 8px; border-radius:999px; background:var(--surface2); border:1px solid var(--border);
        font-size:11px; color:var(--text-muted); white-space:nowrap; }
    .pd-expand-btn { border:none; background:transparent; color:var(--accent); font-size:12px; font-weight:700; cursor:pointer; padding:0; }
    .pd-expand-btn:hover { text-decoration:underline; }
    .pd-detail-row td { background:var(--surface2); padding:0; }
    .pd-detail-inner { padding:10px 14px 14px 34px; }
    table.pd-detail-table { width:100%; border-collapse:collapse; font-size:12.5px; }
    table.pd-detail-table th { text-align:left; color:var(--text-muted); font-weight:700; padding:4px 8px; font-size:10.5px;
        text-transform:uppercase; letter-spacing:.02em; }
    table.pd-detail-table td { padding:4px 8px; color:var(--text-muted); }
    .pd-empty-state { padding:40px 20px; text-align:center; color:var(--text-muted); font-size:13.5px; }
    .pd-pagination { display:flex; align-items:center; justify-content:center; gap:6px; margin-top:14px; flex-wrap:wrap; }
    .pd-page-btn { min-width:30px; height:30px; padding:0 8px; border-radius:8px; border:1px solid var(--border);
        background:var(--surface); color:var(--text-muted); font-size:12.5px; cursor:pointer; }
    .pd-page-btn.is-active { background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700; }
    .pd-page-btn:disabled { opacity:.4; cursor:default; }
</style>

<div style="display:flex; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap">
    <div>
        <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
            📦 Producción
        </h2>
        <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
            Dashboard de series por colegio, generado a partir del Excel de producción.
        </p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:16px">❌ {{ session('error') }}</div>
@endif

{{-- Modal cargar / reemplazar Excel --}}
<div id="modal-cargar-produccion" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
     z-index:999; align-items:center; justify-content:center; padding:20px">
    <div style="background:#fff; border-radius:12px; padding:32px; width:520px; max-width:100%">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
            <h3 style="font-family:'Bricolage Grotesque',sans-serif; margin:0">📦 Cargar Excel de producción</h3>
            <button onclick="document.getElementById('modal-cargar-produccion').style.display='none'"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#888">✕</button>
        </div>
        <p style="margin:0 0 16px; font-size:13.5px; color:var(--text-muted); line-height:1.5">
            El archivo debe tener las columnas: <strong>Nivel, Grado, Colegio ID, Colegio, Tipo Pago, Serie, Empresa,
            Titulo, Codigo ISBN, Codigo GS1, Cantidad</strong>.
        </p>
        @if($meta)
        <div style="background:#fffbeb; border:1px solid #fcd34d; color:#92400e; border-radius:8px; padding:10px 14px;
                    margin-bottom:16px; font-size:13px">
            ⚠️ Esto <strong>reemplazará por completo</strong> los datos actuales del dashboard de producción.
        </div>
        @endif
        <form method="POST" action="{{ route('produccion.upload') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Archivo Excel (.xlsx / .xls) o CSV *</label>
                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                @error('excel_file')
                    <p style="color:#e74c3c; font-size:12px; margin:4px 0 0">{{ $message }}</p>
                @enderror
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:10px">
                <button type="button" onclick="document.getElementById('modal-cargar-produccion').style.display='none'"
                        class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">📤 Cargar</button>
            </div>
        </form>
    </div>
</div>

@if(!$meta)
    {{-- ESTADO VACÍO --}}
    <div class="card" style="padding:56px 24px; text-align:center">
        <div style="font-size:44px; margin-bottom:12px">📦</div>
        <h3 style="font-family:'Bricolage Grotesque',sans-serif; margin:0 0 8px">
            Aún no has cargado ningún archivo de producción
        </h3>
        <p style="font-size:13.5px; color:var(--text-muted); max-width:480px; margin:0 auto 20px">
            Sube el Excel de producción (Nivel, Grado, Colegio ID, Colegio, Tipo Pago, Serie, Empresa, Titulo,
            Codigo ISBN, Codigo GS1, Cantidad) para generar el dashboard de series por colegio.
        </p>
        <button onclick="document.getElementById('modal-cargar-produccion').style.display='flex'" class="btn btn-primary">
            📤 Cargar Excel
        </button>
    </div>
@else
    {{-- BARRA DE INFO + ACCIONES --}}
    <div class="card" style="padding:16px 20px; margin-bottom:20px; display:flex; align-items:center;
                justify-content:space-between; gap:16px; flex-wrap:wrap">
        <div>
            <div style="font-size:13.5px; font-weight:700; color:var(--text)">
                @if($meta->corte)Corte: {{ $meta->corte }} &nbsp;·&nbsp; @endif
                Fuente: {{ $meta->archivo_nombre }}
            </div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:2px">
                Cargado el {{ $meta->cargado_en?->format('d/m/Y H:i') }}
                @if($meta->cargadoPor) por {{ $meta->cargadoPor->name }} @endif
                &nbsp;·&nbsp; {{ number_format($meta->total_filas) }} filas
            </div>
        </div>
        <div style="display:flex; gap:10px">
            <button onclick="document.getElementById('modal-cargar-produccion').style.display='flex'" class="btn btn-secondary">
                🔄 Cargar nuevo Excel
            </button>
            <form method="POST" action="{{ route('produccion.destroy') }}" id="form-borrar-produccion">
                @csrf @method('DELETE')
                <button type="button" class="btn btn-danger"
                        onclick="confirmarEliminar('Borrar dashboard de producción', '¿Deseas borrar todo el dashboard de producción? Esta acción no se puede deshacer.', 'form-borrar-produccion')">
                    🗑 Borrar Dashboard
                </button>
            </form>
        </div>
    </div>

    {{-- STAT TILES --}}
    <section class="pd-stats" aria-label="Resumen general">
        <div class="pd-tile">
            <span class="pd-tile-label">Colegios</span>
            <span class="pd-tile-value" id="statColegios">–</span>
        </div>
        <div class="pd-tile">
            <span class="pd-tile-label">Series</span>
            <span class="pd-tile-value" id="statSeries">–</span>
        </div>
        <div class="pd-tile">
            <span class="pd-tile-label">Títulos distintos</span>
            <span class="pd-tile-value" id="statTitulos">–</span>
        </div>
        <div class="pd-tile">
            <span class="pd-tile-label">Piezas totales</span>
            <span class="pd-tile-value" id="statPiezas">–</span>
        </div>
        <div class="pd-tile pd-tile--accent">
            <span class="pd-tile-label">Piezas en la vista actual</span>
            <span class="pd-tile-value" id="statFiltrado">–</span>
        </div>
    </section>

    {{-- CHARTS --}}
    <section class="pd-charts">
        <div class="card" style="padding:18px 20px">
            <div style="margin-bottom:12px">
                <h2 style="font-size:14.5px; font-weight:700; margin:0">Series con más colegios</h2>
                <span style="font-size:12px; color:var(--text-muted)">Top 10 · clic en una barra para filtrar</span>
            </div>
            <div id="chartSeries" class="pd-chart"></div>
        </div>
        <div class="card" style="padding:18px 20px">
            <div style="margin-bottom:12px">
                <h2 style="font-size:14.5px; font-weight:700; margin:0">Colegios con mayor volumen</h2>
                <span style="font-size:12px; color:var(--text-muted)">Top 10 por piezas · clic para filtrar</span>
            </div>
            <div id="chartColegios" class="pd-chart"></div>
        </div>
    </section>

    {{-- FILTROS --}}
    <section class="card" style="padding:18px 20px; margin-bottom:20px">
        <div style="margin-bottom:14px">
            <h2 style="font-size:14.5px; font-weight:700; margin:0">Filtrar información</h2>
            <span style="font-size:12px; color:var(--text-muted)">Combina series, colegio, nivel, empresa y tipo de pago</span>
        </div>
        <div class="pd-filters-grid">
            <div class="pd-field">
                <label for="serieInput">Series</label>
                <div class="pd-combo" id="serieCombo">
                    <input type="text" id="serieInput" placeholder="Buscar serie… (ej. Imagina, Doodle Town)" autocomplete="off">
                    <div class="pd-combo-list" id="serieList" hidden></div>
                </div>
                <div class="pd-chips" id="serieChips"></div>
            </div>
            <div class="pd-field">
                <label for="colegioInput">Colegio</label>
                <div class="pd-combo" id="colegioCombo">
                    <input type="text" id="colegioInput" placeholder="Buscar colegio por nombre o ID…" autocomplete="off">
                    <div class="pd-combo-list" id="colegioList" hidden></div>
                </div>
                <div class="pd-chips" id="colegioChips"></div>
            </div>
            <div class="pd-field">
                <label for="nivelSelect">Nivel</label>
                <select id="nivelSelect"><option value="">Todos los niveles</option></select>
            </div>
            <div class="pd-field">
                <label for="empresaSelect">Empresa</label>
                <select id="empresaSelect"><option value="">Todas las empresas</option></select>
            </div>
            <div class="pd-field">
                <label for="pagoSelect">Tipo de pago</label>
                <select id="pagoSelect"><option value="">Todos</option></select>
            </div>
            <div class="pd-field">
                <label>&nbsp;</label>
                <button id="clearFilters" class="btn btn-secondary" type="button">Limpiar filtros</button>
            </div>
        </div>
    </section>

    {{-- RESULTADOS --}}
    <section class="card" style="padding:18px 20px">
        <div class="pd-results-head">
            <div>
                <h2 id="resultsTitle" style="font-size:15px; font-weight:700; margin:0">Todos los colegios y series</h2>
                <span class="panel-sub" id="resultsSub" style="font-size:12px; color:var(--text-muted)">
                    Selecciona una serie arriba para ver qué colegios la llevan
                </span>
            </div>
            <div style="display:flex; align-items:center; gap:10px">
                <div class="pd-toggle" role="tablist">
                    <button class="pd-toggle-btn is-active" data-group="serie" type="button" role="tab" aria-selected="true">Por serie</button>
                    <button class="pd-toggle-btn" data-group="colegio" type="button" role="tab" aria-selected="false">Por colegio</button>
                </div>
                <button id="exportCsv" class="btn btn-secondary" type="button">Exportar CSV</button>
            </div>
        </div>

        <div class="pd-table-wrap">
            <table class="pd-table" id="resultsTable">
                <thead id="resultsHead"></thead>
                <tbody id="resultsBody"></tbody>
            </table>
            <p class="pd-empty-state" id="emptyState" hidden>No hay resultados para esta combinación de filtros.</p>
        </div>

        <div class="pd-pagination" id="pagination"></div>
    </section>

    <script>
    const DATA = @json($data);
    (function () {
        'use strict';

        const PAGE_SIZE = 20;

        const seriesIndex = new Map();
        const colegioIndex = new Map();
        const nivelesSet = new Set();
        const empresasSet = new Set();
        const pagosSet = new Set();
        const titulosSet = new Set();

        let totalPiezas = 0;

        DATA.forEach((d) => {
            if (!seriesIndex.has(d.s)) seriesIndex.set(d.s, new Set());
            seriesIndex.get(d.s).add(d.ci);

            if (!colegioIndex.has(d.ci)) {
                colegioIndex.set(d.ci, { nombre: d.co, piezas: 0, series: new Set() });
            }
            const cinfo = colegioIndex.get(d.ci);
            cinfo.piezas += d.c;
            cinfo.series.add(d.s);

            nivelesSet.add(d.n);
            empresasSet.add(d.e);
            pagosSet.add(d.tp);
            titulosSet.add(d.t + '|' + d.isbn);

            totalPiezas += d.c;
        });

        const NIVEL_ORDER = ['Maternal', 'Preescolar', 'Primaria', 'Secundaria', 'Preparatoria', 'Bachillerato', 'Licenciatura'];
        function sortNiveles(a, b) {
            const ia = NIVEL_ORDER.indexOf(a), ib = NIVEL_ORDER.indexOf(b);
            if (ia === -1 && ib === -1) return a.localeCompare(b);
            if (ia === -1) return 1;
            if (ib === -1) return -1;
            return ia - ib;
        }

        const state = {
            series: new Set(),
            colegios: new Set(),
            nivel: '',
            empresa: '',
            pago: '',
            groupBy: 'serie',
            page: 1,
            expanded: new Set(),
        };

        function normalize(str) {
            return String(str || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        }

        function fmt(n) { return Number(n || 0).toLocaleString('es-MX'); }

        function el(tag, attrs, children) {
            const node = document.createElement(tag);
            if (attrs) {
                Object.keys(attrs).forEach((k) => {
                    if (k === 'class') node.className = attrs[k];
                    else if (k === 'html') node.innerHTML = attrs[k];
                    else if (k === 'text') node.textContent = attrs[k];
                    else if (k.startsWith('on') && typeof attrs[k] === 'function') {
                        node.addEventListener(k.slice(2), attrs[k]);
                    } else node.setAttribute(k, attrs[k]);
                });
            }
            (children || []).forEach((c) => c && node.appendChild(c));
            return node;
        }

        function csvEscape(v) {
            const s = String(v == null ? '' : v);
            if (/[",\n]/.test(s)) return '"' + s.replace(/"/g, '""') + '"';
            return s;
        }

        function downloadCsv(filename, rows) {
            const csv = rows.map((r) => r.map(csvEscape).join(',')).join('\r\n');
            const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        document.getElementById('statColegios').textContent = fmt(colegioIndex.size);
        document.getElementById('statSeries').textContent = fmt(seriesIndex.size);
        document.getElementById('statTitulos').textContent = fmt(titulosSet.size);
        document.getElementById('statPiezas').textContent = fmt(totalPiezas);

        function renderBarChart(container, items, opts) {
            container.innerHTML = '';
            const max = Math.max(1, ...items.map((i) => i.value));
            items.forEach((item) => {
                const pct = Math.max(2, Math.round((item.value / max) * 100));
                const isActive = opts.isActive ? opts.isActive(item) : false;
                const row = el('div', {
                    class: 'pd-chart-row' + (isActive ? ' is-active' : ''),
                    title: item.label + ': ' + fmt(item.value),
                    onclick: () => opts.onClick(item),
                }, [
                    el('span', { class: 'pd-chart-row-label', text: item.label }),
                    el('div', { class: 'pd-chart-track' }, [
                        el('div', { class: 'pd-chart-fill', style: 'width:' + pct + '%' }),
                    ]),
                    el('span', { class: 'pd-chart-row-value', text: fmt(item.value) }),
                ]);
                container.appendChild(row);
            });
        }

        function toggleSetValue(set, value) {
            if (set.has(value)) set.delete(value); else set.add(value);
        }

        function renderCharts() {
            const topSeries = Array.from(seriesIndex.entries())
                .map(([serie, set]) => ({ label: serie, value: set.size, key: serie }))
                .sort((a, b) => b.value - a.value).slice(0, 10);

            renderBarChart(document.getElementById('chartSeries'), topSeries, {
                isActive: (item) => state.series.has(item.key),
                onClick: (item) => { toggleSetValue(state.series, item.key); syncChipsAndInputs(); state.page = 1; renderAll(); },
            });

            const topColegios = Array.from(colegioIndex.entries())
                .map(([ci, info]) => ({ label: info.nombre, value: info.piezas, key: ci }))
                .sort((a, b) => b.value - a.value).slice(0, 10);

            renderBarChart(document.getElementById('chartColegios'), topColegios, {
                isActive: (item) => state.colegios.has(item.key),
                onClick: (item) => { toggleSetValue(state.colegios, item.key); syncChipsAndInputs(); state.page = 1; renderAll(); },
            });
        }

        function fillSelect(selectEl, values) {
            const sorted = Array.from(values).filter(v => v !== undefined && v !== null && v !== '').sort((a, b) => String(a).localeCompare(String(b)));
            sorted.forEach((v) => {
                const opt = document.createElement('option');
                opt.value = v; opt.textContent = v;
                selectEl.appendChild(opt);
            });
        }

        const nivelSelect = document.getElementById('nivelSelect');
        const empresaSelect = document.getElementById('empresaSelect');
        const pagoSelect = document.getElementById('pagoSelect');

        fillSelect(nivelSelect, Array.from(nivelesSet).sort(sortNiveles));
        fillSelect(empresaSelect, empresasSet);
        fillSelect(pagoSelect, pagosSet);

        nivelSelect.addEventListener('change', () => { state.nivel = nivelSelect.value; state.page = 1; renderAll(); });
        empresaSelect.addEventListener('change', () => { state.empresa = empresaSelect.value; state.page = 1; renderAll(); });
        pagoSelect.addEventListener('change', () => { state.pago = pagoSelect.value; state.page = 1; renderAll(); });

        function setupCombo({ inputEl, listEl, chipsEl, getOptions, formatOption, selectedSet, onChange }) {
            function renderList(query) {
                const q = normalize(query);
                const opts = getOptions()
                    .filter((o) => !q || normalize(o.label).includes(q) || (o.sub && normalize(o.sub).includes(q)))
                    .slice(0, 40);

                listEl.innerHTML = '';
                if (!opts.length) {
                    listEl.appendChild(el('div', { class: 'pd-combo-empty', text: 'Sin coincidencias' }));
                } else {
                    opts.forEach((o) => {
                        const optNode = el('div', {
                            class: 'pd-combo-option',
                            onclick: () => {
                                selectedSet.add(o.key);
                                inputEl.value = '';
                                listEl.hidden = true;
                                renderChips();
                                onChange();
                            },
                        }, [
                            el('span', { text: formatOption(o) }),
                            el('span', { class: 'pd-combo-option-count', text: o.countLabel || '' }),
                        ]);
                        listEl.appendChild(optNode);
                    });
                }
                listEl.hidden = false;
            }

            function renderChips() {
                chipsEl.innerHTML = '';
                Array.from(selectedSet).forEach((key) => {
                    const opt = getOptions().find((o) => o.key === key);
                    const label = opt ? opt.label : key;
                    const chip = el('span', { class: 'pd-chip' }, [
                        el('span', { text: label }),
                        el('button', {
                            type: 'button', 'aria-label': 'Quitar', text: '×',
                            onclick: (e) => { e.stopPropagation(); selectedSet.delete(key); renderChips(); onChange(); },
                        }),
                    ]);
                    chipsEl.appendChild(chip);
                });
            }

            inputEl.addEventListener('input', () => renderList(inputEl.value));
            inputEl.addEventListener('focus', () => renderList(inputEl.value));
            document.addEventListener('click', (e) => {
                if (!inputEl.parentElement.contains(e.target)) listEl.hidden = true;
            });
            inputEl.addEventListener('keydown', (e) => { if (e.key === 'Escape') listEl.hidden = true; });

            renderChips();
            return { renderChips };
        }

        const serieCombo = setupCombo({
            inputEl: document.getElementById('serieInput'),
            listEl: document.getElementById('serieList'),
            chipsEl: document.getElementById('serieChips'),
            getOptions: () => Array.from(seriesIndex.entries())
                .map(([serie, set]) => ({ key: serie, label: serie, countLabel: set.size + ' colegios' }))
                .sort((a, b) => a.label.localeCompare(b.label)),
            formatOption: (o) => o.label,
            selectedSet: state.series,
            onChange: () => { state.page = 1; renderAll(); },
        });

        const colegioCombo = setupCombo({
            inputEl: document.getElementById('colegioInput'),
            listEl: document.getElementById('colegioList'),
            chipsEl: document.getElementById('colegioChips'),
            getOptions: () => Array.from(colegioIndex.entries())
                .map(([ci, info]) => ({ key: ci, label: info.nombre, sub: ci, countLabel: ci }))
                .sort((a, b) => a.label.localeCompare(b.label)),
            formatOption: (o) => o.label,
            selectedSet: state.colegios,
            onChange: () => { state.page = 1; renderAll(); },
        });

        function syncChipsAndInputs() { serieCombo.renderChips(); colegioCombo.renderChips(); }

        document.getElementById('clearFilters').addEventListener('click', () => {
            state.series.clear(); state.colegios.clear();
            state.nivel = ''; state.empresa = ''; state.pago = '';
            nivelSelect.value = ''; empresaSelect.value = ''; pagoSelect.value = '';
            state.page = 1;
            syncChipsAndInputs();
            renderAll();
        });

        document.querySelectorAll('.pd-toggle-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.pd-toggle-btn').forEach((b) => {
                    b.classList.remove('is-active'); b.setAttribute('aria-selected', 'false');
                });
                btn.classList.add('is-active'); btn.setAttribute('aria-selected', 'true');
                state.groupBy = btn.dataset.group;
                state.page = 1; state.expanded.clear();
                renderAll();
            });
        });

        function getFilteredRows() {
            return DATA.filter((d) => {
                if (state.series.size && !state.series.has(d.s)) return false;
                if (state.colegios.size && !state.colegios.has(d.ci)) return false;
                if (state.nivel && d.n !== state.nivel) return false;
                if (state.empresa && d.e !== state.empresa) return false;
                if (state.pago && d.tp !== state.pago) return false;
                return true;
            });
        }

        function groupBySerieColegio(rows) {
            const map = new Map();
            rows.forEach((d) => {
                const key = d.s + '||' + d.ci;
                if (!map.has(key)) {
                    map.set(key, { key, serie: d.s, ci: d.ci, co: d.co, niveles: new Set(), grados: new Set(),
                        empresas: new Set(), pagos: new Set(), piezas: 0, detalle: [] });
                }
                const g = map.get(key);
                g.niveles.add(d.n); g.grados.add(d.g); g.empresas.add(d.e); g.pagos.add(d.tp);
                g.piezas += d.c; g.detalle.push(d);
            });
            return Array.from(map.values()).sort((a, b) => b.piezas - a.piezas || a.co.localeCompare(b.co));
        }

        function groupByColegio(rows) {
            const map = new Map();
            rows.forEach((d) => {
                if (!map.has(d.ci)) {
                    map.set(d.ci, { key: d.ci, ci: d.ci, co: d.co, niveles: new Set(), series: new Map(), piezas: 0, titulos: new Set() });
                }
                const g = map.get(d.ci);
                g.niveles.add(d.n); g.piezas += d.c; g.titulos.add(d.t + '|' + d.isbn);
                g.series.set(d.s, (g.series.get(d.s) || 0) + d.c);
            });
            return Array.from(map.values()).sort((a, b) => b.piezas - a.piezas || a.co.localeCompare(b.co));
        }

        const resultsHead = document.getElementById('resultsHead');
        const resultsBody = document.getElementById('resultsBody');
        const emptyState = document.getElementById('emptyState');
        const resultsTitle = document.getElementById('resultsTitle');
        const resultsSub = document.getElementById('resultsSub');
        const paginationEl = document.getElementById('pagination');
        const statFiltrado = document.getElementById('statFiltrado');

        function tagList(items, limit) {
            const arr = Array.from(items);
            const shown = limit ? arr.slice(0, limit) : arr;
            const wrap = el('div', { class: 'pd-tag-row' }, shown.map((v) => el('span', { class: 'pd-tag', text: String(v) })));
            if (limit && arr.length > limit) wrap.appendChild(el('span', { class: 'pd-tag', text: '+' + (arr.length - limit) }));
            return wrap;
        }

        function renderHeadTitle() {
            const parts = [];
            if (state.series.size === 1) parts.push('Serie: ' + Array.from(state.series)[0]);
            else if (state.series.size > 1) parts.push(state.series.size + ' series seleccionadas');
            if (state.colegios.size === 1) parts.push('Colegio: ' + colegioIndex.get(Array.from(state.colegios)[0]).nombre);
            else if (state.colegios.size > 1) parts.push(state.colegios.size + ' colegios seleccionados');
            if (state.nivel) parts.push(state.nivel);
            if (state.empresa) parts.push(state.empresa);
            if (state.pago) parts.push(state.pago);

            if (state.groupBy === 'serie') {
                resultsTitle.textContent = state.series.size ? 'Colegios que llevan la(s) serie(s) seleccionada(s)' : 'Todos los colegios y series';
            } else {
                resultsTitle.textContent = state.colegios.size ? 'Series del colegio seleccionado' : 'Todos los colegios (resumen)';
            }
            resultsSub.textContent = parts.length ? parts.join(' · ') : 'Sin filtros activos — mostrando todo el catálogo';
        }

        function renderTableSerieMode(pageItems) {
            resultsHead.innerHTML = '';
            resultsHead.appendChild(el('tr', {}, [
                el('th', { text: 'Serie' }), el('th', { text: 'Colegio' }), el('th', { text: 'ID Colegio' }),
                el('th', { text: 'Nivel(es)' }), el('th', { text: 'Empresa' }), el('th', { text: 'Tipo de pago' }),
                el('th', { class: 'pd-cell-num', text: 'Títulos' }), el('th', { class: 'pd-cell-num', text: 'Piezas' }),
                el('th', { text: '' }),
            ]));

            resultsBody.innerHTML = '';
            pageItems.forEach((g) => {
                const isExpanded = state.expanded.has(g.key);
                const row = el('tr', { class: 'pd-group-row' }, [
                    el('td', { text: g.serie }), el('td', { text: g.co }), el('td', { class: 'pd-col-id', text: g.ci }),
                    el('td', {}, [tagList(Array.from(g.niveles).sort(sortNiveles))]),
                    el('td', {}, [tagList(g.empresas)]), el('td', {}, [tagList(g.pagos)]),
                    el('td', { class: 'pd-cell-num', text: fmt(g.detalle.length) }),
                    el('td', { class: 'pd-cell-num', text: fmt(g.piezas) }),
                    el('td', {}, [el('button', {
                        class: 'pd-expand-btn', type: 'button', text: isExpanded ? 'Ocultar' : 'Ver títulos',
                        onclick: () => { if (isExpanded) state.expanded.delete(g.key); else state.expanded.add(g.key); renderResults(); },
                    })]),
                ]);
                resultsBody.appendChild(row);

                if (isExpanded) {
                    const detailTable = el('table', { class: 'pd-detail-table' }, [
                        el('thead', {}, [el('tr', {}, [
                            el('th', { text: 'Título' }), el('th', { text: 'ISBN' }), el('th', { text: 'Nivel' }),
                            el('th', { text: 'Grado' }), el('th', { class: 'pd-cell-num', text: 'Cantidad' }),
                        ])]),
                        el('tbody', {}, g.detalle.slice().sort((a, b) => b.c - a.c).map((d) => el('tr', {}, [
                            el('td', { text: d.t }), el('td', { text: d.isbn || '—' }), el('td', { text: d.n }),
                            el('td', { text: String(d.g) }), el('td', { class: 'pd-cell-num', text: fmt(d.c) }),
                        ]))),
                    ]);
                    resultsBody.appendChild(el('tr', { class: 'pd-detail-row' }, [
                        el('td', { colspan: '9' }, [el('div', { class: 'pd-detail-inner' }, [detailTable])]),
                    ]));
                }
            });
        }

        function renderTableColegioMode(pageItems) {
            resultsHead.innerHTML = '';
            resultsHead.appendChild(el('tr', {}, [
                el('th', { text: 'Colegio' }), el('th', { text: 'ID Colegio' }), el('th', { text: 'Nivel(es)' }),
                el('th', { class: 'pd-cell-num', text: '# Series' }), el('th', { text: 'Series' }),
                el('th', { class: 'pd-cell-num', text: 'Títulos' }), el('th', { class: 'pd-cell-num', text: 'Piezas' }),
                el('th', { text: '' }),
            ]));

            resultsBody.innerHTML = '';
            pageItems.forEach((g) => {
                const isExpanded = state.expanded.has(g.key);
                const seriesArr = Array.from(g.series.keys());
                const row = el('tr', { class: 'pd-group-row' }, [
                    el('td', { text: g.co }), el('td', { class: 'pd-col-id', text: g.ci }),
                    el('td', {}, [tagList(Array.from(g.niveles).sort(sortNiveles))]),
                    el('td', { class: 'pd-cell-num', text: fmt(seriesArr.length) }),
                    el('td', {}, [tagList(seriesArr, 4)]),
                    el('td', { class: 'pd-cell-num', text: fmt(g.titulos.size) }),
                    el('td', { class: 'pd-cell-num', text: fmt(g.piezas) }),
                    el('td', {}, [el('button', {
                        class: 'pd-expand-btn', type: 'button', text: isExpanded ? 'Ocultar' : 'Ver series',
                        onclick: () => { if (isExpanded) state.expanded.delete(g.key); else state.expanded.add(g.key); renderResults(); },
                    })]),
                ]);
                resultsBody.appendChild(row);

                if (isExpanded) {
                    const rows = Array.from(g.series.entries()).sort((a, b) => b[1] - a[1]);
                    const detailTable = el('table', { class: 'pd-detail-table' }, [
                        el('thead', {}, [el('tr', {}, [el('th', { text: 'Serie' }), el('th', { class: 'pd-cell-num', text: 'Piezas' })])]),
                        el('tbody', {}, rows.map(([serie, piezas]) => el('tr', {}, [
                            el('td', { text: serie }), el('td', { class: 'pd-cell-num', text: fmt(piezas) }),
                        ]))),
                    ]);
                    resultsBody.appendChild(el('tr', { class: 'pd-detail-row' }, [
                        el('td', { colspan: '8' }, [el('div', { class: 'pd-detail-inner' }, [detailTable])]),
                    ]));
                }
            });
        }

        function renderPagination(totalItems) {
            paginationEl.innerHTML = '';
            const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
            if (state.page > totalPages) state.page = totalPages;

            function pageBtn(label, page, opts) {
                opts = opts || {};
                const btn = el('button', {
                    class: 'pd-page-btn' + (opts.active ? ' is-active' : ''), type: 'button', text: label,
                    onclick: () => { state.page = page; renderResults(); },
                });
                if (opts.disabled) btn.disabled = true;
                return btn;
            }

            paginationEl.appendChild(pageBtn('‹', Math.max(1, state.page - 1), { disabled: state.page === 1 }));

            const windowSize = 2;
            const pages = new Set([1, totalPages]);
            for (let p = state.page - windowSize; p <= state.page + windowSize; p++) if (p >= 1 && p <= totalPages) pages.add(p);
            const sortedPages = Array.from(pages).sort((a, b) => a - b);
            let prev = 0;
            sortedPages.forEach((p) => {
                if (p - prev > 1) paginationEl.appendChild(el('span', { text: '…', style: 'padding:0 4px; color:var(--text-muted)' }));
                paginationEl.appendChild(pageBtn(String(p), p, { active: p === state.page }));
                prev = p;
            });

            paginationEl.appendChild(pageBtn('›', Math.min(totalPages, state.page + 1), { disabled: state.page === totalPages }));
        }

        let lastGroups = [];

        function renderResults() {
            const filtered = getFilteredRows();
            statFiltrado.textContent = fmt(filtered.reduce((sum, d) => sum + d.c, 0));

            const groups = state.groupBy === 'serie' ? groupBySerieColegio(filtered) : groupByColegio(filtered);
            lastGroups = groups;

            renderHeadTitle();

            const table = document.getElementById('resultsTable');
            if (!groups.length) {
                table.hidden = true; emptyState.hidden = false; paginationEl.innerHTML = '';
                return;
            }
            table.hidden = false; emptyState.hidden = true;

            const start = (state.page - 1) * PAGE_SIZE;
            const pageItems = groups.slice(start, start + PAGE_SIZE);

            if (state.groupBy === 'serie') renderTableSerieMode(pageItems); else renderTableColegioMode(pageItems);
            renderPagination(groups.length);
        }

        document.getElementById('exportCsv').addEventListener('click', () => {
            if (state.groupBy === 'serie') {
                const header = ['Serie', 'Colegio', 'ID Colegio', 'Niveles', 'Grados', 'Empresa', 'Tipo de pago', 'Titulos', 'Piezas'];
                const rows = lastGroups.map((g) => [
                    g.serie, g.co, g.ci,
                    Array.from(g.niveles).sort(sortNiveles).join(' / '),
                    Array.from(g.grados).sort((a, b) => a - b).join(' / '),
                    Array.from(g.empresas).join(' / '), Array.from(g.pagos).join(' / '),
                    g.detalle.length, g.piezas,
                ]);
                downloadCsv('series_por_colegio.csv', [header, ...rows]);
            } else {
                const header = ['Colegio', 'ID Colegio', 'Niveles', 'N Series', 'Series', 'Titulos', 'Piezas'];
                const rows = lastGroups.map((g) => [
                    g.co, g.ci, Array.from(g.niveles).sort(sortNiveles).join(' / '),
                    g.series.size, Array.from(g.series.keys()).join(' / '), g.titulos.size, g.piezas,
                ]);
                downloadCsv('colegios_resumen.csv', [header, ...rows]);
            }
        });

        function renderAll() { renderCharts(); renderResults(); }
        renderAll();
    })();
    </script>
@endif

@endsection
