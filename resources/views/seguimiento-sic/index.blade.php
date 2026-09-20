@extends('layouts.app')

@section('title', 'Seguimiento SIC')

@section('content')

<div style="margin-bottom:24px">
    <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
        📈 Seguimiento SIC
    </h2>
    <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
        Qué le falta cargar a cada consultor digital por colegio: Docentes, Alumnos y Bundles.
    </p>
</div>

{{-- Stats --}}
<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px">
    <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:160px; flex:1; max-width:220px">
        <span style="font-size:26px; line-height:1">🏫</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#0d1117; line-height:1">{{ $totalColegios }}</div>
            <div style="font-size:12px; font-weight:600; color:#64748b; margin-top:2px">Colegios</div>
        </div>
    </div>
    <div style="background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:160px; flex:1; max-width:220px">
        <span style="font-size:26px; line-height:1">✅</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#16a34a; line-height:1">{{ $completos }}</div>
            <div style="font-size:12px; font-weight:600; color:#16a34a; opacity:.85; margin-top:2px">Completos</div>
        </div>
    </div>
    <div style="background:#fff5f5; border:1.5px solid #fecaca; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:160px; flex:1; max-width:220px">
        <span style="font-size:26px; line-height:1">⚠️</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#dc2626; line-height:1">{{ $incompletos }}</div>
            <div style="font-size:12px; font-weight:600; color:#dc2626; opacity:.85; margin-top:2px">Incompletos</div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap">
            <input type="text" id="buscador" class="form-control"
                   placeholder="🔍 Buscar colegio..." style="max-width:260px">

            <label style="font-size:13px; color:var(--text-muted); white-space:nowrap">Consultor Digital</label>
            <select name="consultor" class="form-control" style="max-width:220px" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach($consultores as $c)
                    <option value="{{ $c->id }}" @selected($consultorId == $c->id)>{{ $c->user->name }}</option>
                @endforeach
            </select>

            <label style="display:flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); cursor:pointer">
                <input type="checkbox" name="incompletos" value="1" @checked($soloIncompletos) onchange="this.form.submit()">
                Solo incompletos
            </label>

            <span id="contador" style="font-size:13px; color:var(--text-muted); margin-left:auto"></span>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">📋 Estado de carga por colegio</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $schools->count() }} registrados</span>
    </div>
    <table class="table" id="tabla-seguimiento">
        <thead>
            <tr>
                <th>Colegio</th>
                <th>Consultor Digital</th>
                <th style="text-align:center">Docentes</th>
                <th style="text-align:center">Alumnos</th>
                <th style="text-align:center">Bundles</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schools as $school)
            @php
                $faltan = [];
                if (!$school->docentes_ok) $faltan[] = 'Docentes';
                if (!$school->alumnos_ok)  $faltan[] = 'Alumnos';
                if (!$school->bundles_ok)  $faltan[] = 'Bundles';
            @endphp
            <tr class="seguimiento-row" data-nombre="{{ strtolower($school->name) }}">
                <td><strong>{{ $school->name }}</strong></td>
                <td style="font-size:13px; color:var(--text-muted)">
                    {{ $school->consultorDigital?->user?->name ?? '—' }}
                </td>
                <td style="text-align:center">
                    <span class="badge {{ $school->docentes_ok ? 'badge-success' : 'badge-danger' }}">
                        {{ $school->teachers_count }}
                    </span>
                </td>
                <td style="text-align:center">
                    <span class="badge {{ $school->alumnos_ok ? 'badge-success' : 'badge-danger' }}">
                        {{ $school->students_count }}
                    </span>
                </td>
                <td style="text-align:center">
                    <span class="badge {{ $school->bundles_ok ? 'badge-success' : 'badge-danger' }}">
                        {{ $school->bundles_count }}
                    </span>
                </td>
                <td>
                    @if($school->completo)
                        <span class="badge badge-success">✅ Completo</span>
                    @else
                        <span class="badge badge-warning">⚠️ Falta: {{ implode(', ', $faltan) }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay colegios que coincidan con el filtro.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="xl-note" style="font-size:12.5px; color:var(--text-muted); margin-top:14px; display:flex; gap:8px; align-items:flex-start">
    <span>💡</span>
    <span>
        Un colegio se marca <strong>Completo</strong> cuando tiene más de {{ \App\Http\Controllers\SeguimientoSicController::MIN_DOCENTES }} docentes,
        más de {{ \App\Http\Controllers\SeguimientoSicController::MIN_ALUMNOS }} alumnos
        y más de {{ \App\Http\Controllers\SeguimientoSicController::MIN_BUNDLES }} bundles cargados en el SIC.
    </span>
</div>

<script>
const buscadorSeg = document.getElementById('buscador');
const contadorSeg = document.getElementById('contador');
const filasSeg    = document.querySelectorAll('.seguimiento-row');

buscadorSeg?.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    let visibles = 0;
    filasSeg.forEach(fila => {
        if (fila.dataset.nombre.includes(q)) {
            fila.style.display = '';
            visibles++;
        } else {
            fila.style.display = 'none';
        }
    });
    contadorSeg.textContent = q ? `${visibles} resultado(s)` : '';
});
</script>
@endsection
