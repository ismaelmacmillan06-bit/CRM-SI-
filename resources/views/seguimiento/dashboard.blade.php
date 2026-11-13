@extends('layouts.app')

@section('title', 'Seguimiento · ' . $nombre)

@section('content')
<div style="margin-bottom:24px">
    <h1 style="font-family:'Bricolage Grotesque',sans-serif; font-size:24px; font-weight:700; margin:0">
        {{ $icono }} Seguimiento {{ $nombre }}
    </h1>
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

{{-- Cards --}}
<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px">
    <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px">
        <span style="font-size:26px; line-height:1">🏫</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#0d1117; line-height:1">{{ $filas->count() }}</div>
            <div style="font-size:12px; font-weight:600; color:#64748b; margin-top:2px">Colegios</div>
        </div>
    </div>
    <div style="background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px">
        <span style="font-size:26px; line-height:1">✅</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#16a34a; line-height:1">{{ $completos }}</div>
            <div style="font-size:12px; font-weight:600; color:#16a34a; opacity:.85; margin-top:2px">Implementación completa</div>
        </div>
    </div>
    {{-- Tickets y sesiones registrados con el producto (Tickets/Sesiones → Producto) --}}
    <div title="Tickets de estos colegios con el producto {{ $nombre }}"
         style="background:#f5f3ff; border:1.5px solid #ddd6fe; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px">
        <span style="font-size:26px; line-height:1">🎫</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#7c3aed; line-height:1">{{ $tickets['total'] }}</div>
            <div style="font-size:12px; font-weight:600; color:#7c3aed; opacity:.85; margin-top:2px">Tickets {{ $nombre }}</div>
            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px">{{ $tickets['abiertos'] }} abiertos · {{ $tickets['cerrados'] }} cerrados</div>
        </div>
    </div>
    <div title="Sesiones de estos colegios con el producto {{ $nombre }}"
         style="background:#ecfeff; border:1.5px solid #a5f3fc; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px">
        <span style="font-size:26px; line-height:1">📅</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#0891b2; line-height:1">{{ $sesiones['total'] }}</div>
            <div style="font-size:12px; font-weight:600; color:#0891b2; opacity:.85; margin-top:2px">Sesiones {{ $nombre }}</div>
            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px">{{ $sesiones['realizadas'] }} realizadas · {{ $sesiones['pendientes'] }} pendientes</div>
        </div>
    </div>
</div>

@if($slug === 'global-educa')
<style>
    .ge-vistas { display:inline-flex; background:var(--surface2); border-radius:10px; padding:4px; gap:4px; margin-bottom:16px; flex-wrap:wrap; }
    .ge-vista-btn { border:none; background:none; font:inherit; font-size:13.5px; font-weight:600; color:var(--text-muted);
                    padding:7px 16px; border-radius:7px; cursor:pointer; display:inline-flex; align-items:center; gap:7px; }
    .ge-vista-btn.activa { background:#fff; color:var(--text); box-shadow:0 1px 3px rgba(0,0,0,.1); }
    .ge-tab-count { font-size:11px; font-weight:700; padding:1px 7px; border-radius:99px; background:rgba(24,19,17,.08); color:var(--text-muted); }
    .ge-vista-btn.activa .ge-tab-count { background:var(--accent); color:#fff; }
    .ge-badge-prox {
        font-size:10.5px; font-weight:700; letter-spacing:.2px;
        padding:2px 8px; border-radius:99px;
        background:#fef3c7; color:#b45309; border:1px solid #fde68a;
    }
</style>

<div class="ge-vistas" role="tablist">
    <button type="button" class="ge-vista-btn activa" data-ge-vista="tabla" role="tab">📋 Tabla</button>
    <button type="button" class="ge-vista-btn" data-ge-vista="formatos" role="tab">
        📄 Formatos <span class="ge-tab-count">{{ ($formatos ?? collect())->count() }}</span>
    </button>
    <button type="button" class="ge-vista-btn" data-ge-vista="manuales" role="tab">
        📘 Manuales <span class="ge-badge-prox">Próximamente</span>
    </button>
</div>
@endif

<div id="ge-vista-tabla">
<div class="card">
    <div class="card-header" style="gap:12px; flex-wrap:wrap">
        <span class="card-title">🏫 Colegios con {{ $nombre }}</span>
        <div style="display:flex; gap:8px; flex-wrap:wrap; flex:1; justify-content:flex-end">
            @if($filas->isNotEmpty())
                <input type="search" id="buscador-colegios" class="form-control" autocomplete="off"
                       placeholder="🔍 Buscar colegio..." style="flex:1 1 200px; max-width:260px">
            @endif
            <a href="{{ route('seguimiento.excel', $slug) }}" class="btn btn-secondary" style="white-space:nowrap">📥 Descargar Excel</a>
        </div>
    </div>
    <div style="overflow-x:auto">
    <table class="table">
        <thead>
            <tr>
                <th>Colegio</th>
                <th>Consultor digital</th>
                <th>Niveles</th>
                <th style="min-width:180px">Avance</th>
                <th>Siguiente acción</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="tabla-colegios">
            @forelse($filas as $fila)
            <tr data-nombre="{{ mb_strtolower($fila['colegio']->name . ' ' . $fila['colegio']->nexus_id) }}">
                <td>
                    <strong>{{ $fila['colegio']->name }}</strong>
                    <div style="font-size:12px; color:var(--text-muted)">{{ $fila['colegio']->state ?? $fila['colegio']->city ?? '—' }}</div>
                </td>
                <td style="font-size:13px">{{ $fila['consultor'] ?? 'Sin asignar' }}</td>
                <td>
                    <div style="display:flex; flex-wrap:wrap; gap:4px; max-width:220px">
                        @forelse($fila['colegio']->levels as $nivel)
                            <span class="badge badge-info">{{ $nivel->name }}</span>
                        @empty
                            <span style="color:var(--text-muted)">—</span>
                        @endforelse
                    </div>
                </td>
                <td>
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--text-muted); margin-bottom:4px">
                        <span>{{ $fila['hechas'] }}/{{ $totalAcciones }}</span>
                        <span>{{ $fila['pct'] }}%</span>
                    </div>
                    <div style="background:var(--surface2); border-radius:20px; height:7px; overflow:hidden">
                        <div style="height:100%; width:{{ $fila['pct'] }}%; border-radius:20px;
                                    background:{{ $fila['completo'] ? 'var(--success)' : 'var(--accent)' }}"></div>
                    </div>
                </td>
                <td style="font-size:13px">
                    @if($fila['completo'])
                        <span class="badge badge-success">Implementación completa</span>
                    @else
                        {{ $fila['siguiente'] }}
                    @endif
                </td>
                <td style="text-align:right">
                    <div style="display:inline-flex; gap:6px; flex-wrap:nowrap; justify-content:flex-end">
                        @if($slug === 'global-educa')
                        @php $resFormato = $resumenFormato[$fila['colegio']->id] ?? []; @endphp
                        <a href="{{ route('schools.formato-entrega', $fila['colegio']) }}"
                           class="btn btn-secondary btn-sm"
                           style="white-space:nowrap"
                           data-formato-entrega
                           data-school-name="{{ $fila['colegio']->name }}"
                           data-accesos-url="{{ route('schools.formato-entrega.accesos', $fila['colegio']) }}"
                           data-student-review-count="{{ $resFormato['studentReviewCount'] ?? 0 }}"
                           data-student-class-count="{{ $resFormato['studentClassCount'] ?? 0 }}"
                           data-teacher-review-count="{{ $resFormato['teacherReviewCount'] ?? 0 }}"
                           data-teacher-class-count="{{ $resFormato['teacherClassCount'] ?? 0 }}"
                           data-student-count="{{ $resFormato['studentCount'] ?? 0 }}"
                           data-teacher-count="{{ $resFormato['teacherCount'] ?? 0 }}"
                           title="Formato de entrega Global Educa de {{ $fila['colegio']->name }}">
                            🌐 Formato entrega
                        </a>
                        @endif
                        <a href="{{ route('schools.tickets.index', $fila['colegio']) }}" class="btn btn-secondary btn-sm"
                           style="white-space:nowrap" title="Tickets de {{ $fila['colegio']->name }}">🎫 Tickets</a>
                        <a href="{{ route('schools.visits.index', $fila['colegio']) }}" class="btn btn-secondary btn-sm"
                           style="white-space:nowrap" title="Sesiones de {{ $fila['colegio']->name }}">📅 Sesiones</a>
                        <a href="{{ route('seguimiento.colegio', [$slug, $fila['colegio']]) }}" class="btn btn-secondary btn-sm"
                           style="white-space:nowrap">{{ $puedeEditar ? 'Gestionar' : 'Ver' }} →</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay colegios con {{ $nombre }}. Márcalo en <strong>Servicios adicionales</strong> al editar un colegio.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
</div>

@if($slug === 'global-educa')
<div id="ge-vista-formatos" style="display:none">
    @include('seguimiento.partials._recursos_section', [
        'recursos' => $formatos ?? collect(),
        'categoria' => 'formatos',
        'iconoCategoria' => '📄',
        'nombre' => $nombre,
    ])
</div>

<div id="ge-vista-manuales" style="display:none">
    <div class="card" style="text-align:center; padding:54px 24px">
        <div style="font-size:44px; line-height:1; margin-bottom:12px">📘</div>
        <span class="ge-badge-prox" style="font-size:12px; padding:4px 12px">Próximamente</span>
        <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:20px; font-weight:700; margin:12px 0 6px; color:var(--text)">
            Manuales de {{ $nombre }}
        </h2>
        <p style="font-size:13.5px; color:var(--text-muted); max-width:440px; margin:0 auto">
            Esta sección estará disponible muy pronto con guías y manuales descargables para el equipo.
        </p>
    </div>
</div>
@endif

@if($slug === 'global-educa')
@include('seguimiento.partials._recursos_modals', ['slug' => $slug, 'categoriaDefault' => 'formatos'])
<script>
(function () {
    function cambiarGeVista(v) {
        if (!['tabla', 'formatos', 'manuales'].includes(v)) v = 'tabla';
        document.getElementById('ge-vista-tabla').style.display = v === 'tabla' ? '' : 'none';
        document.getElementById('ge-vista-formatos').style.display = v === 'formatos' ? '' : 'none';
        document.getElementById('ge-vista-manuales').style.display = v === 'manuales' ? '' : 'none';
        document.querySelectorAll('.ge-vista-btn').forEach(b => b.classList.toggle('activa', b.dataset.geVista === v));
        try { localStorage.setItem('sic-ge-vista', v); } catch (e) {}
    }
    document.querySelectorAll('.ge-vista-btn').forEach(b => b.addEventListener('click', () => cambiarGeVista(b.dataset.geVista)));

    const tabFlash = @json(session('tab'));
    const tieneErrorRecurso = @json($errors->has('titulo') || $errors->has('archivo') || $errors->has('audiencia'));
    let vistaGuardada = null;
    try { vistaGuardada = localStorage.getItem('sic-ge-vista'); } catch (e) {}
    if (tabFlash === 'formatos' || tieneErrorRecurso) {
        cambiarGeVista('formatos');
    } else if (vistaGuardada && ['tabla', 'formatos', 'manuales'].includes(vistaGuardada)) {
        cambiarGeVista(vistaGuardada);
    }
})();
</script>
@include('partials.formato-entrega-modal', ['schoolName' => ''])
<script src="{{ asset('js/accesos-pdf.js') }}" defer></script>
<script src="{{ asset('js/formato-entrega.js') }}" defer
    data-logo-url="{{ asset('images/logo-global-educa.png') }}"></script>
@endif

<script>
    document.getElementById('buscador-colegios')?.addEventListener('input', function () {
        const texto = this.value.toLowerCase().trim();
        document.querySelectorAll('#tabla-colegios tr[data-nombre]').forEach(function (fila) {
            fila.style.display = fila.dataset.nombre.includes(texto) ? '' : 'none';
        });
    });
</script>
@endsection
