@extends('layouts.app')

@section('title', 'Reporte Semanal')

@section('content')

<style>
    .rs-stats { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:20px; }
    @media (max-width:720px){ .rs-stats{ grid-template-columns:1fr; } }
    .rs-tile { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px 18px;
               display:flex; flex-direction:column; gap:6px; box-shadow:0 1px 4px rgba(0,0,0,.05); }
    .rs-tile-label { font-size:11.5px; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
    .rs-tile-value { font-family:'Bricolage Grotesque',sans-serif; font-size:24px; font-weight:800; letter-spacing:-.01em; }
    .rs-tile-sub { font-size:11.5px; color:var(--text-muted); }

    .rs-pending-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
    @media (max-width:820px){ .rs-pending-grid{ grid-template-columns:1fr; } }
    .rs-pending-count { font-family:'Bricolage Grotesque',sans-serif; font-size:26px; font-weight:800; }
    .rs-pending-list { max-height:180px; overflow-y:auto; margin-top:10px; border-top:1px solid var(--border); padding-top:8px; }
    .rs-pending-item { font-size:12.5px; padding:5px 2px; border-bottom:1px dashed var(--border); color:var(--text); }
    .rs-pending-item:last-child { border-bottom:none; }
    .rs-pending-empty { font-size:12.5px; color:var(--text-muted); padding:10px 2px; }

    .rs-table-wrap { overflow-x:auto; }
    table.rs-table { width:100%; border-collapse:collapse; font-size:13px; min-width:720px; }
    table.rs-table thead th { text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.03em;
        color:var(--text-muted); background:var(--surface2); padding:10px 12px; border-bottom:1px solid var(--border); white-space:nowrap; }
    table.rs-table td { padding:8px; vertical-align:top; border-bottom:1px solid var(--border); }
    table.rs-table tbody tr:last-child td { border-bottom:none; }
    .rs-cat-cell { white-space:nowrap; font-weight:600; padding-top:14px !important; }
    .rs-cat-icon { margin-right:6px; }
    .rs-textarea { width:100%; min-width:180px; min-height:64px; padding:8px 10px; border:1px solid var(--border);
        border-radius:8px; font-size:12.5px; font-family:inherit; color:var(--text); resize:vertical; background:var(--surface); }
    .rs-textarea:focus { outline:2px solid var(--accent); outline-offset:-1px; }

    .rs-save-status { font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:6px; }
    .rs-save-dot { width:7px; height:7px; border-radius:50%; background:#f59e0b; }
    .rs-save-status.is-saved .rs-save-dot { background:#16a34a; }

    .rs-week-nav { display:flex; align-items:center; gap:6px; }
    .rs-week-btn { width:30px; height:30px; border-radius:8px; border:1px solid var(--border); background:var(--surface);
        color:var(--text); font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; }
    .rs-week-btn:hover { background:var(--surface2); }
    .rs-week-select { height:34px; padding:0 10px; border-radius:8px; border:1px solid var(--border);
        background:var(--surface); color:var(--text); font-size:13px; font-family:inherit; font-weight:600; }
</style>

<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap">
    <div>
        <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
            🗓️ Reporte Semanal
        </h2>
        <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
            Semana del {{ $weekLabel }} · novedades del equipo digital para dirección.
        </p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap">
        <button type="button" onclick="document.getElementById('modal-vista-previa').style.display='flex'; renderVistaPrevia()"
                class="btn btn-secondary">👁️ Vista previa</button>
        <a href="{{ route('reporte-semanal.exportar', ['week' => $weekStart->toDateString()]) }}" class="btn btn-secondary">⬇️ Exportar CSV</a>
        @hasanyrole('admin|consultor_digital')
        <button type="submit" form="form-reporte-semanal" class="btn btn-primary">✅ Guardar reporte</button>
        @endhasanyrole
    </div>
</div>

<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:20px; flex-wrap:wrap">
    <div class="rs-week-nav">
        <a href="{{ route('reporte-semanal.index', ['week' => $weekPrev]) }}" class="rs-week-btn" title="Semana anterior">←</a>
        <select class="rs-week-select" onchange="if(this.value) location.href = '{{ route('reporte-semanal.index') }}?week=' + this.value">
            @foreach($weekOptions as $option)
                <option value="{{ $option['value'] }}" {{ $option['value'] === $weekStart->toDateString() ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
        <a href="{{ route('reporte-semanal.index', ['week' => $weekNext]) }}" class="rs-week-btn" title="Semana siguiente">→</a>
        @unless($esSemanaActual)
            <a href="{{ route('reporte-semanal.index') }}" class="btn btn-secondary" style="padding:6px 12px; font-size:12.5px">Semana actual</a>
        @endunless
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:16px">❌ {{ session('error') }}</div>
@endif

{{-- Listados de colegios pendientes --}}
<div class="rs-pending-grid">
    <div class="card">
        <div class="card-header">
            <span class="card-title">📋 Faltan formato docente</span>
        </div>
        <div class="card-body">
            <div class="rs-pending-count" style="color:#dc2626">{{ $colegiosSinDocente->count() }}</div>
            <div style="font-size:12px; color:var(--text-muted)">
                Colegios sin "Registrar Profesores" marcado como completo en ningún nivel.
            </div>
            <div class="rs-pending-list">
                @forelse($colegiosSinDocente as $colegio)
                    <div class="rs-pending-item">{{ $colegio->name }}</div>
                @empty
                    <div class="rs-pending-empty">Todos los colegios ya lo tienen completo. 🎉</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">📋 Faltan formato alumno</span>
        </div>
        <div class="card-body">
            <div class="rs-pending-count" style="color:#d97706">{{ $colegiosSinAlumno->count() }}</div>
            <div style="font-size:12px; color:var(--text-muted)">
                Colegios sin "Alta de Alumnos" marcado como completo en ningún nivel.
            </div>
            <div class="rs-pending-list">
                @forelse($colegiosSinAlumno as $colegio)
                    <div class="rs-pending-item">{{ $colegio->name }}</div>
                @empty
                    <div class="rs-pending-empty">Todos los colegios ya lo tienen completo. 🎉</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Estadísticas del reporte --}}
<div class="rs-stats">
    <div class="rs-tile">
        <div class="rs-tile-label">Novedades capturadas</div>
        <div class="rs-tile-value" id="rs-stat-novedades">{{ $novedadesCapturadas }}</div>
        <div class="rs-tile-sub">de {{ $totalCampos }} campos</div>
    </div>
    <div class="rs-tile">
        <div class="rs-tile-label">Avance del reporte</div>
        <div class="rs-tile-value" id="rs-stat-avance">{{ $avance }}%</div>
        <div class="rs-tile-sub">captura de esta semana</div>
    </div>
    <div class="rs-tile">
        <div class="rs-tile-label">Último guardado</div>
        <div class="rs-tile-value" style="font-size:15px">
            {{ $ultimoGuardado ? \Carbon\Carbon::parse($ultimoGuardado)->format('d/m/Y H:i') : '—' }}
        </div>
        <div class="rs-tile-sub">hora del servidor</div>
    </div>
</div>

@if($consultores->isEmpty())
    <div class="card">
        <div class="card-body" style="text-align:center; padding:40px; color:var(--text-muted)">
            No hay usuarios con rol de consultor digital registrados todavía.
        </div>
    </div>
@else
<form id="form-reporte-semanal" method="POST" action="{{ route('reporte-semanal.store') }}">
    @csrf
    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
    <input type="hidden" name="payload" id="rs-payload">
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px">
            <div>
                <span class="card-title">📝 Captura de novedades</span>
                <div style="font-size:12px; color:var(--text-muted); margin-top:2px">Una columna por consultor digital · una fila por seguimiento</div>
            </div>
            <div class="rs-save-status is-saved" id="rs-save-status">
                <span class="rs-save-dot"></span>
                <span id="rs-save-status-text">Sin cambios sin guardar</span>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="rs-table-wrap">
                <table class="rs-table">
                    <thead>
                        <tr>
                            <th style="width:170px">Seguimiento</th>
                            @foreach($consultores as $consultor)
                                <th>{{ $consultor->user->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorias as $slug => $meta)
                        <tr>
                            <td class="rs-cat-cell"><span class="rs-cat-icon">{{ $meta['icon'] }}</span>{{ $meta['label'] }}</td>
                            @foreach($consultores as $consultor)
                            <td>
                                <textarea class="rs-textarea"
                                          data-category="{{ $slug }}"
                                          data-consultant="{{ $consultor->id }}"
                                          placeholder="Escribe la novedad...">{{ $grid[$slug][$consultor->id] ?? '' }}</textarea>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
@endif

{{-- Modal Vista previa --}}
<div id="modal-vista-previa" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
     z-index:999; align-items:center; justify-content:center; padding:20px">
    <div style="background:#fff; border-radius:12px; padding:28px; width:820px; max-width:100%; max-height:85vh; overflow-y:auto">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px">
            <h3 style="font-family:'Bricolage Grotesque',sans-serif; font-size:17px; font-weight:600">
                👁️ Vista previa — Semana del {{ $weekLabel }}
            </h3>
            <button onclick="document.getElementById('modal-vista-previa').style.display='none'"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#666">✕</button>
        </div>
        <p style="font-size:12.5px; color:var(--text-muted); margin-bottom:16px">
            Solo lectura, basada en lo que hay escrito ahora mismo en los campos (aunque no lo hayas guardado todavía).
        </p>
        <div id="rs-vista-previa-contenido" style="font-size:13px"></div>
    </div>
</div>

@php
    $rsConsultoresJs = $consultores->map(function ($c) {
        return ['id' => $c->id, 'name' => $c->user->name];
    })->values();
    $rsCategoriasJs = collect($categorias)->map(function ($meta, $slug) {
        return ['slug' => $slug, 'label' => $meta['label'], 'icon' => $meta['icon']];
    })->values();
@endphp
<script>
const rsConsultores = @json($rsConsultoresJs);
const rsCategorias  = @json($rsCategoriasJs);

const rsForm = document.getElementById('form-reporte-semanal');
const rsSaveStatus = document.getElementById('rs-save-status');
const rsSaveStatusText = document.getElementById('rs-save-status-text');

function rsRecolectarPayload() {
    const textareas = document.querySelectorAll('.rs-textarea');
    const payload = [];
    textareas.forEach(ta => {
        payload.push({
            category: ta.dataset.category,
            consultant_id: ta.dataset.consultant,
            content: ta.value,
        });
    });
    return payload;
}

function rsActualizarStats() {
    const textareas = document.querySelectorAll('.rs-textarea');
    let llenos = 0;
    textareas.forEach(ta => { if (ta.value.trim() !== '') llenos++; });
    const total = textareas.length || 1;
    document.getElementById('rs-stat-novedades').textContent = llenos;
    document.getElementById('rs-stat-avance').textContent = Math.round(llenos / total * 100) + '%';
}

document.querySelectorAll('.rs-textarea').forEach(ta => {
    ta.addEventListener('input', () => {
        rsActualizarStats();
        rsSaveStatus.classList.remove('is-saved');
        rsSaveStatusText.textContent = 'Cambios sin guardar';
    });
});

if (rsForm) {
    rsForm.addEventListener('submit', () => {
        document.getElementById('rs-payload').value = JSON.stringify(rsRecolectarPayload());
    });
}

function rsEsc(s) {
    return (s + '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}

function renderVistaPrevia() {
    const textareas = document.querySelectorAll('.rs-textarea');
    const valores = {};
    textareas.forEach(ta => {
        valores[ta.dataset.category + '_' + ta.dataset.consultant] = ta.value.trim();
    });

    let html = '<table style="width:100%; border-collapse:collapse">';
    html += '<thead><tr><th style="text-align:left; padding:8px; border-bottom:2px solid #eee">Seguimiento</th>';
    rsConsultores.forEach(c => { html += `<th style="text-align:left; padding:8px; border-bottom:2px solid #eee">${rsEsc(c.name)}</th>`; });
    html += '</tr></thead><tbody>';

    rsCategorias.forEach(cat => {
        html += `<tr><td style="padding:8px; border-bottom:1px solid #f1f1f1; font-weight:600; white-space:nowrap">${rsEsc(cat.icon)} ${rsEsc(cat.label)}</td>`;
        rsConsultores.forEach(c => {
            const raw = valores[cat.slug + '_' + c.id];
            const val = raw ? rsEsc(raw).replace(/\n/g, '<br>') : '<span style="color:#aaa">— sin novedades —</span>';
            html += `<td style="padding:8px; border-bottom:1px solid #f1f1f1; font-size:12.5px">${val}</td>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';

    document.getElementById('rs-vista-previa-contenido').innerHTML = html;
}
</script>

@endsection
