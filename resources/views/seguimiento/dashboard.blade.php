@extends('layouts.app')

@section('title', 'Seguimiento · ' . $nombre)

@section('content')
@php
    $tieneAcciones = $tieneAcciones ?? (($totalAcciones ?? 0) > 0);
    $esPanelConActividad = in_array($slug, ['frances', 'red-roo-reads', 'equipamiento'], true);
    $tieneFormatos = in_array('formatos', $categoriasRecursos ?? [], true);
    $tieneManuales = in_array('manuales', $categoriasRecursos ?? [], true);
@endphp
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
    <div @if($esPanelConActividad) data-seg-card-vista="tabla" role="button" tabindex="0" title="Ver tabla de colegios" @endif
         style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px; {{ $esPanelConActividad ? 'cursor:pointer; transition:box-shadow .15s, transform .15s;' : '' }}">
        <span style="font-size:26px; line-height:1">🏫</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#0d1117; line-height:1">{{ $filas->count() }}</div>
            <div style="font-size:12px; font-weight:600; color:#64748b; margin-top:2px">Colegios</div>
        </div>
    </div>
    @if($tieneAcciones)
    <div style="background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px">
        <span style="font-size:26px; line-height:1">✅</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#16a34a; line-height:1">{{ $completos }}</div>
            <div style="font-size:12px; font-weight:600; color:#16a34a; opacity:.85; margin-top:2px">Implementación completa</div>
        </div>
    </div>
    @endif
    {{-- Tickets y sesiones registrados con el producto (Tickets/Sesiones → Producto) --}}
    <div @if($esPanelConActividad) data-seg-card-vista="tickets" role="button" tabindex="0" @endif
         title="{{ $esPanelConActividad ? 'Ver tickets con producto ' . $nombre : 'Tickets de estos colegios con el producto ' . $nombre }}"
         style="background:#f5f3ff; border:1.5px solid #ddd6fe; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px; {{ $esPanelConActividad ? 'cursor:pointer; transition:box-shadow .15s, transform .15s;' : '' }}">
        <span style="font-size:26px; line-height:1">🎫</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#7c3aed; line-height:1">{{ $tickets['total'] }}</div>
            <div style="font-size:12px; font-weight:600; color:#7c3aed; opacity:.85; margin-top:2px">Tickets {{ $nombre }}</div>
            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px">{{ $tickets['abiertos'] }} abiertos · {{ $tickets['cerrados'] }} cerrados</div>
        </div>
    </div>
    <div @if($esPanelConActividad) data-seg-card-vista="sesiones" role="button" tabindex="0" @endif
         title="{{ $esPanelConActividad ? 'Ver sesiones con producto ' . $nombre : 'Sesiones de estos colegios con el producto ' . $nombre }}"
         style="background:#ecfeff; border:1.5px solid #a5f3fc; border-radius:12px; padding:14px 20px;
                display:flex; align-items:center; gap:12px; min-width:200px; flex:1; max-width:280px; {{ $esPanelConActividad ? 'cursor:pointer; transition:box-shadow .15s, transform .15s;' : '' }}">
        <span style="font-size:26px; line-height:1">📅</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#0891b2; line-height:1">{{ $sesiones['total'] }}</div>
            <div style="font-size:12px; font-weight:600; color:#0891b2; opacity:.85; margin-top:2px">Sesiones {{ $nombre }}</div>
            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px">{{ $sesiones['realizadas'] }} realizadas · {{ $sesiones['pendientes'] }} pendientes</div>
        </div>
    </div>
</div>

@if($slug === 'global-educa' || $esPanelConActividad)
<style>
    .ge-vistas { display:inline-flex; background:var(--surface2); border-radius:10px; padding:4px; gap:4px; margin-bottom:16px; flex-wrap:wrap; }
    .ge-vista-btn { border:none; background:none; font:inherit; font-size:13.5px; font-weight:600; color:var(--text-muted);
                    padding:7px 16px; border-radius:7px; cursor:pointer; display:inline-flex; align-items:center; gap:7px; }
    .ge-vista-btn.activa { background:#fff; color:var(--text); box-shadow:0 1px 3px rgba(0,0,0,.1); }
    .ge-tab-count { font-size:11px; font-weight:700; padding:1px 7px; border-radius:99px; background:rgba(24,19,17,.08); color:var(--text-muted); }
    .ge-vista-btn.activa .ge-tab-count { background:var(--accent); color:#fff; }
    [data-seg-card-vista]:hover { box-shadow:0 4px 14px rgba(0,0,0,.07); transform:translateY(-1px); }
</style>
@endif

@if($slug === 'global-educa')
<div class="ge-vistas" role="tablist">
    <button type="button" class="ge-vista-btn activa" data-ge-vista="tabla" role="tab">📋 Tabla</button>
    <button type="button" class="ge-vista-btn" data-ge-vista="formatos" role="tab">
        📄 Formatos <span class="ge-tab-count">{{ ($formatos ?? collect())->count() }}</span>
        @if(!empty($sectionNovedades['highlight_tabs']['formatos']))<span class="nav-dot" style="margin-left:2px"></span>@endif
    </button>
    <button type="button" class="ge-vista-btn" data-ge-vista="manuales" role="tab">
        📘 Manuales <span class="ge-tab-count">{{ ($manuales ?? collect())->count() }}</span>
        @if(!empty($sectionNovedades['highlight_tabs']['manuales']))<span class="nav-dot" style="margin-left:2px"></span>@endif
    </button>
</div>
@elseif($esPanelConActividad)
<div class="ge-vistas" role="tablist">
    <button type="button" class="ge-vista-btn activa" data-seg-vista="tabla" role="tab">📋 Tabla</button>
    <button type="button" class="ge-vista-btn" data-seg-vista="tickets" role="tab">
        🎫 Tickets <span class="ge-tab-count">{{ ($ticketsLista ?? collect())->count() }}</span>
    </button>
    <button type="button" class="ge-vista-btn" data-seg-vista="sesiones" role="tab">
        📅 Sesiones <span class="ge-tab-count">{{ ($sesionesLista ?? collect())->count() }}</span>
    </button>
    @if($tieneFormatos)
    <button type="button" class="ge-vista-btn" data-seg-vista="formatos" role="tab">
        📄 Formatos <span class="ge-tab-count">{{ ($formatos ?? collect())->count() }}</span>
        @if(!empty($sectionNovedades['highlight_tabs']['formatos']))<span class="nav-dot" style="margin-left:2px"></span>@endif
    </button>
    @endif
    @if($tieneManuales)
    <button type="button" class="ge-vista-btn" data-seg-vista="manuales" role="tab">
        📘 Manuales <span class="ge-tab-count">{{ ($manuales ?? collect())->count() }}</span>
        @if(!empty($sectionNovedades['highlight_tabs']['manuales']))<span class="nav-dot" style="margin-left:2px"></span>@endif
    </button>
    @endif
</div>
@endif

<div id="ge-vista-tabla">
@unless($tieneAcciones)
<div style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:12px 16px;
            border-radius:10px; margin-bottom:14px; font-size:13.5px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
    <span>
        ℹ️ Las acciones de arranque de <strong>{{ $nombre }}</strong> aún no están disponibles. Por ahora puedes consultar los colegios, tickets, sesiones, formatos y manuales.
    </span>
    @if($puedeConfigurar)
        <button type="button" class="btn btn-primary btn-sm" onclick="window.abrirCatalogoSeguimiento('{{ $slug }}')">
            ⚙️ Configurar acciones de arranque
        </button>
    @endif
</div>
@endunless
<div class="card">
    <div class="card-header" style="gap:12px; flex-wrap:wrap">
        <span class="card-title">🏫 Colegios con {{ $nombre }}</span>
        <div style="display:flex; gap:8px; flex-wrap:wrap; flex:1; justify-content:flex-end">
            @if($filas->isNotEmpty())
                <input type="search" id="buscador-colegios" class="form-control" autocomplete="off"
                       placeholder="🔍 Buscar colegio..." style="flex:1 1 200px; max-width:260px">
            @endif
            @if($puedeConfigurar)
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.abrirCatalogoSeguimiento()" style="display:inline-flex; align-items:center; gap:7px;">
            ⚙️ Personalizar acciones
            <span data-btn-seg-count="{{ $slug }}" style="font-size:11px; font-weight:700; padding:1px 7px; border-radius:999px; background:rgba(24,19,17,.08); color:var(--text);">{{ $totalAcciones ?? 0 }}</span>
        </button>
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
                @if($tieneAcciones)
                    <th style="min-width:180px">Avance</th>
                    <th>Siguiente acción</th>
                @endif
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
                    <div style="display:flex; flex-wrap:wrap; gap:4px; max-width:240px">
                        @if(!empty($fila['niveles']))
                            @forelse($fila['niveles'] as $nr)
                                <span class="badge {{ $nr['completo'] ? 'badge-success' : 'badge-info' }}">
                                    {{ $nr['nivel']->name }} {{ $nr['hechas'] }}/{{ $nr['total'] }}
                                </span>
                            @empty
                                <span style="color:var(--text-muted)">Sin niveles</span>
                            @endforelse
                        @else
                            @forelse($fila['colegio']->levels as $nivel)
                                <span class="badge badge-info">{{ $nivel->name }}</span>
                            @empty
                                <span style="color:var(--text-muted)">—</span>
                            @endforelse
                        @endif
                    </div>
                </td>
                @if($tieneAcciones)
                <td>
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--text-muted); margin-bottom:4px">
                        <span>{{ $fila['hechas'] }}/{{ $fila['total'] ?? $totalAcciones }}</span>
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
                @endif
                <td style="text-align:right">
                    <div style="display:inline-flex; gap:6px; flex-wrap:nowrap; justify-content:flex-end">
                        @if($slug === 'global-educa')
                        @php $resFormato = $resumenFormato[$fila['colegio']->id] ?? []; @endphp
                        <a href="{{ route('schools.formato-entrega', $fila['colegio']) }}"
                           class="btn btn-secondary btn-sm"
                           style="white-space:nowrap"
                           data-formato-entrega
                           data-school-name="{{ $fila['colegio']->name }}"
                           data-modal-title="Formato de entrega Global Educa"
                           data-modal-badge="🌐 Exclusivo para colegios Global Educa"
                           data-logo-url="{{ asset('images/logo-global-educa.png') }}"
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
                        @elseif($slug === 'frances')
                        @php $resFormato = $resumenFormato[$fila['colegio']->id] ?? []; @endphp
                        <a href="{{ route('schools.formato-entrega', ['school' => $fila['colegio'], 'plataforma' => 'frances']) }}"
                           class="btn btn-secondary btn-sm"
                           style="white-space:nowrap"
                           data-formato-entrega
                           data-school-name="{{ $fila['colegio']->name }}"
                           data-modal-title="Formato de entrega Espace Virtuel"
                           data-modal-badge="🗼 Exclusivo para colegios con Francés"
                           data-logo-url="{{ asset('images/logo-espace-virtuel.png') }}"
                           data-accesos-url="{{ route('schools.formato-entrega.accesos', ['school' => $fila['colegio'], 'plataforma' => 'frances']) }}"
                           data-pdf-suffix=" Espace Virtuel"
                           data-sin-genero="1"
                           data-student-review-count="{{ $resFormato['studentReviewCount'] ?? 0 }}"
                           data-student-class-count="{{ $resFormato['studentClassCount'] ?? 0 }}"
                           data-teacher-review-count="{{ $resFormato['teacherReviewCount'] ?? 0 }}"
                           data-teacher-class-count="{{ $resFormato['teacherClassCount'] ?? 0 }}"
                           data-student-count="{{ $resFormato['studentCount'] ?? 0 }}"
                           data-teacher-count="{{ $resFormato['teacherCount'] ?? 0 }}"
                           title="Formato de entrega Espace Virtuel de {{ $fila['colegio']->name }}">
                            🗼 Formato entrega
                        </a>
                        @endif
                        <a href="{{ route('schools.tickets.index', ['school' => $fila['colegio'], 'producto' => $nombre]) }}" class="btn btn-secondary btn-sm"
                           style="white-space:nowrap" title="Tickets de {{ $fila['colegio']->name }}">🎫 Tickets</a>
                        <a href="{{ route('schools.visits.index', ['school' => $fila['colegio'], 'producto' => $nombre]) }}" class="btn btn-secondary btn-sm"
                           style="white-space:nowrap" title="Sesiones de {{ $fila['colegio']->name }}">📅 Sesiones</a>
                        @if($tieneAcciones)
                            <a href="{{ route('seguimiento.colegio', [$slug, $fila['colegio']]) }}" class="btn btn-secondary btn-sm"
                               style="white-space:nowrap">{{ $puedeEditar ? 'Gestionar' : 'Ver' }} →</a>
                        @else
                            <a href="{{ route('schools.show', $fila['colegio']) }}" class="btn btn-secondary btn-sm"
                               style="white-space:nowrap">Ver colegio →</a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $tieneAcciones ? 6 : 4 }}" style="text-align:center; color:var(--text-muted); padding:40px">
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
    @include('seguimiento.partials._recursos_section', [
        'recursos' => $manuales ?? collect(),
        'categoria' => 'manuales',
        'iconoCategoria' => '📘',
        'nombre' => $nombre,
    ])
</div>
@endif

@if($slug === 'global-educa')
@include('seguimiento.partials._recursos_modals', ['slug' => $slug, 'categoriaDefault' => 'formatos'])
<script>
(function () {
    const validas = ['tabla', 'formatos', 'manuales'];
    function cambiarGeVista(v) {
        if (!validas.includes(v)) v = 'tabla';
        document.getElementById('ge-vista-tabla').style.display = v === 'tabla' ? '' : 'none';
        document.getElementById('ge-vista-formatos').style.display = v === 'formatos' ? '' : 'none';
        document.getElementById('ge-vista-manuales').style.display = v === 'manuales' ? '' : 'none';
        document.querySelectorAll('.ge-vista-btn').forEach(b => b.classList.toggle('activa', b.dataset.geVista === v));
    }
    document.querySelectorAll('.ge-vista-btn').forEach(b => b.addEventListener('click', () => cambiarGeVista(b.dataset.geVista)));

    const tabFlash = @json(session('tab'));
    const tieneErrorRecurso = @json($errors->has('titulo') || $errors->has('archivo') || $errors->has('audiencia'));
    const oldCat = @json(old('categoria', 'formatos'));
    if (tabFlash && validas.includes(tabFlash)) {
        cambiarGeVista(tabFlash);
    } else if (tieneErrorRecurso && validas.includes(oldCat)) {
        cambiarGeVista(oldCat);
    }
})();
</script>
@include('partials.formato-entrega-modal', ['schoolName' => ''])
<script src="{{ asset('js/accesos-pdf.js') }}" defer></script>
<script src="{{ asset('js/formato-entrega.js') }}" defer
    data-logo-url="{{ asset('images/logo-global-educa.png') }}"></script>
@elseif($esPanelConActividad)
<div id="seg-vista-tickets" style="display:none">
    <div class="card">
        <div class="card-header" style="gap:12px; flex-wrap:wrap">
            <span class="card-title">🎫 Tickets con producto {{ $nombre }}</span>
            <div style="display:flex; gap:8px; flex-wrap:wrap; flex:1; justify-content:flex-end; align-items:center">
                @if(($ticketsLista ?? collect())->isNotEmpty())
                    <input type="search" id="buscador-tickets-seg" class="form-control" autocomplete="off"
                           placeholder="🔍 Buscar colegio, título o consultor..." style="flex:1 1 200px; max-width:260px">
                    <select id="filtro-status-tickets-seg" class="form-control" style="width:auto; font-size:13px">
                        <option value="">Todos los estatus</option>
                        <option value="open">Abierto</option>
                        <option value="in_progress">En progreso</option>
                        <option value="closed">Cerrado</option>
                    </select>
                @endif
                <span style="font-size:13px; color:var(--text-muted); white-space:nowrap">
                    <span id="contador-tickets-seg">{{ ($ticketsLista ?? collect())->count() }}</span> registrados
                </span>
            </div>
        </div>
        <div style="overflow-x:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Colegio</th>
                        <th>Título</th>
                        <th>Medio</th>
                        <th>Productos</th>
                        <th>Prioridad</th>
                        <th>Status</th>
                        <th>Consultor</th>
                        <th>Fechas</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tabla-tickets-seg">
                    @forelse($ticketsLista ?? collect() as $ticket)
                    @php
                        $ticketTexto = mb_strtolower(($ticket->school->name ?? '') . ' ' . $ticket->title . ' ' . ($ticket->description ?? '') . ' ' . ($ticket->consultant->user->name ?? ''));
                    @endphp
                    <tr class="fila-ticket-seg" data-texto="{{ $ticketTexto }}" data-status="{{ $ticket->status }}">
                        <td>
                            <strong>{{ $ticket->school->name ?? '—' }}</strong>
                            @if($ticket->school?->nexus_id)
                                <div style="font-size:12px; color:var(--text-muted)">{{ $ticket->school->nexus_id }}</div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $ticket->title }}</strong>
                            @if($ticket->description)
                                <div style="font-size:12px; color:var(--text-muted)">{{ Str::limit($ticket->description, 70) }}</div>
                            @endif
                        </td>
                        <td>
                            @if($ticket->medium === 'salesforce')
                                <span class="badge badge-info">Sales Force</span>
                            @elseif($ticket->medium === 'whaticket')
                                <span class="badge badge-warning">Whaticket</span>
                            @else
                                <span class="badge badge-success">WhatsApp</span>
                            @endif
                        </td>
                        <td>
                            @include('partials.producto-chips', ['productos' => $ticket->productos, 'compacto' => true, 'contenedor' => 'max-width:200px'])
                        </td>
                        <td>
                            @if($ticket->priority === 'high')
                                <span class="badge badge-danger">🔴 Alta</span>
                            @elseif($ticket->priority === 'medium')
                                <span class="badge badge-warning">🟡 Media</span>
                            @else
                                <span class="badge badge-gray">🟢 Baja</span>
                            @endif
                        </td>
                        <td>
                            @if($ticket->status === 'open')
                                <span class="badge badge-danger">Abierto</span>
                            @elseif($ticket->status === 'in_progress')
                                <span class="badge badge-warning">En progreso</span>
                            @else
                                <span class="badge badge-success">Cerrado</span>
                            @endif
                        </td>
                        <td style="font-size:13px">{{ $ticket->consultant->user->name ?? '—' }}</td>
                        <td style="font-size:12px; color:var(--text-muted); white-space:nowrap">
                            <div>Creado: {{ $ticket->created_at?->format('d/m/Y') ?? '—' }}</div>
                            <div>Resuelto: {{ $ticket->resolved_at ? \Carbon\Carbon::parse($ticket->resolved_at)->format('d/m/Y') : '—' }}</div>
                        </td>
                        <td style="text-align:right">
                            <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-secondary btn-sm" style="white-space:nowrap">Ver →</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center; color:var(--text-muted); padding:40px">
                            No hay tickets con producto {{ $nombre }} en estos colegios.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="seg-vista-sesiones" style="display:none">
    <div class="card">
        <div class="card-header" style="gap:12px; flex-wrap:wrap">
            <span class="card-title">📅 Sesiones con producto {{ $nombre }}</span>
            <div style="display:flex; gap:8px; flex-wrap:wrap; flex:1; justify-content:flex-end; align-items:center">
                @if(($sesionesLista ?? collect())->isNotEmpty())
                    <input type="search" id="buscador-sesiones-seg" class="form-control" autocomplete="off"
                           placeholder="🔍 Buscar colegio, consultor o motivo..." style="flex:1 1 200px; max-width:260px">
                    <select id="filtro-status-sesiones-seg" class="form-control" style="width:auto; font-size:13px">
                        <option value="">Todos los estatus</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_curso">En curso</option>
                        <option value="terminada">Terminada</option>
                    </select>
                @endif
                <span style="font-size:13px; color:var(--text-muted); white-space:nowrap">
                    <span id="contador-sesiones-seg">{{ ($sesionesLista ?? collect())->count() }}</span> registradas
                </span>
            </div>
        </div>
        <div style="overflow-x:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Colegio</th>
                        <th>Consultor</th>
                        <th>Sesión</th>
                        <th>Productos</th>
                        <th>Prioridad / Status</th>
                        <th>Fechas</th>
                        <th>Motivo / Resumen</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tabla-sesiones-seg">
                    @forelse($sesionesLista ?? collect() as $visit)
                    @php
                        [$pBg, $pFg] = \App\Models\Visit::PRIORIDAD_COLORES[$visit->prioridad] ?? ['#f3f4f6', '#374151'];
                        $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
                        $sesionTexto = mb_strtolower(($visit->school->name ?? '') . ' ' . ($visit->consultant->user->name ?? '') . ' ' . ($visit->motivo ?? '') . ' ' . ($visit->summary ?? ''));
                    @endphp
                    <tr class="fila-sesion-seg" data-texto="{{ $sesionTexto }}" data-status="{{ $visit->status }}">
                        <td>
                            <strong>{{ $visit->school->name ?? '—' }}</strong>
                            @if($visit->school?->nexus_id)
                                <div style="font-size:12px; color:var(--text-muted)">{{ $visit->school->nexus_id }}</div>
                            @endif
                        </td>
                        <td style="font-size:13px">{{ $visit->consultant->user->name ?? '—' }}</td>
                        <td>
                            <div style="font-size:13px; font-weight:600">{{ \App\Models\Visit::TIPOS[$visit->tipo] ?? '—' }}</div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px">{{ \App\Models\Visit::MODALIDADES[$visit->modalidad] ?? '—' }}</div>
                        </td>
                        <td>
                            @include('partials.producto-chips', ['productos' => $visit->productos, 'compacto' => true, 'contenedor' => 'max-width:200px'])
                        </td>
                        <td>
                            <div style="display:flex; flex-direction:column; align-items:flex-start; gap:5px">
                                <span class="badge" style="background:{{ $pBg }}; color:{{ $pFg }}">
                                    {{ \App\Models\Visit::PRIORIDADES[$visit->prioridad] ?? '—' }}
                                </span>
                                @if($visit->status === 'terminada')
                                    <span class="badge badge-success">✅ Terminada</span>
                                @elseif($visit->status === 'en_curso')
                                    <span class="badge badge-warning">🔄 En curso</span>
                                @else
                                    <span class="badge badge-gray">⏳ Pendiente</span>
                                @endif
                            </div>
                        </td>
                        <td style="font-size:12px; color:var(--text-muted); white-space:nowrap">
                            <div>Prog: {{ $visit->tipo === 'emergencia' ? 'Sin programar' : $fmt($visit->scheduled_date) }}</div>
                            <div>Real: {{ $fmt($visit->visit_date) }}</div>
                            <div>Próx: {{ $fmt($visit->next_visit_date) }}</div>
                        </td>
                        <td style="font-size:12.5px; color:var(--text-muted); max-width:260px">
                            @if($visit->motivo)
                                <div style="color:var(--text); font-weight:600">{{ Str::limit($visit->motivo, 60) }}</div>
                            @endif
                            @if($visit->summary)
                                <div>{{ Str::limit($visit->summary, 70) }}</div>
                            @elseif(!$visit->motivo)
                                —
                            @endif
                        </td>
                        <td style="text-align:right">
                            @if(auth()->user()?->hasAnyRole(['admin', 'consultor_digital']))
                                <a href="{{ route('visits.edit', $visit) }}" class="btn btn-secondary btn-sm" style="white-space:nowrap">Editar →</a>
                            @elseif($visit->school)
                                <a href="{{ route('schools.visits.index', ['school' => $visit->school, 'producto' => $nombre]) }}" class="btn btn-secondary btn-sm" style="white-space:nowrap">Ver sesiones →</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; color:var(--text-muted); padding:40px">
                            No hay sesiones con producto {{ $nombre }} en estos colegios.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($tieneFormatos)
<div id="seg-vista-formatos" style="display:none">
    @include('seguimiento.partials._recursos_section', [
        'recursos' => $formatos ?? collect(),
        'categoria' => 'formatos',
        'iconoCategoria' => '📄',
        'nombre' => $nombre,
    ])
</div>
@endif

@if($tieneManuales)
<div id="seg-vista-manuales" style="display:none">
    @include('seguimiento.partials._recursos_section', [
        'recursos' => $manuales ?? collect(),
        'categoria' => 'manuales',
        'iconoCategoria' => '📘',
        'nombre' => $nombre,
    ])
</div>
@endif

@if($tieneFormatos || $tieneManuales)
@include('seguimiento.partials._recursos_modals', ['slug' => $slug, 'categoriaDefault' => 'formatos'])
@endif

@if($slug === 'frances')
@include('partials.formato-entrega-modal', [
    'schoolName' => '',
    'modalTitle' => 'Formato de entrega Espace Virtuel',
    'modalBadge' => '🗼 Exclusivo para colegios con Francés',
])
<script src="{{ asset('js/accesos-pdf.js') }}" defer></script>
<script src="{{ asset('js/formato-entrega.js') }}" defer
    data-logo-url="{{ asset('images/logo-espace-virtuel.png') }}"></script>
@endif

<script>
(function () {
    const validas = ['tabla', 'tickets', 'sesiones'];
    if (document.getElementById('seg-vista-formatos')) validas.push('formatos');
    if (document.getElementById('seg-vista-manuales')) validas.push('manuales');

    function cambiarSegVista(v) {
        if (!validas.includes(v)) v = 'tabla';
        document.getElementById('ge-vista-tabla').style.display = v === 'tabla' ? '' : 'none';
        document.getElementById('seg-vista-tickets').style.display = v === 'tickets' ? '' : 'none';
        document.getElementById('seg-vista-sesiones').style.display = v === 'sesiones' ? '' : 'none';
        const elFmt = document.getElementById('seg-vista-formatos');
        if (elFmt) elFmt.style.display = v === 'formatos' ? '' : 'none';
        const elMan = document.getElementById('seg-vista-manuales');
        if (elMan) elMan.style.display = v === 'manuales' ? '' : 'none';
        document.querySelectorAll('[data-seg-vista]').forEach(b => b.classList.toggle('activa', b.dataset.segVista === v));
    }

    document.querySelectorAll('[data-seg-vista]').forEach(b => b.addEventListener('click', () => cambiarSegVista(b.dataset.segVista)));
    document.querySelectorAll('[data-seg-card-vista]').forEach(c => {
        c.addEventListener('click', () => cambiarSegVista(c.dataset.segCardVista));
        c.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                cambiarSegVista(c.dataset.segCardVista);
            }
        });
    });

    const tabFlash = @json(session('tab'));
    const tieneErrorRecurso = @json($errors->has('titulo') || $errors->has('archivo') || $errors->has('audiencia'));
    const oldCat = @json(old('categoria', 'formatos'));
    if (tabFlash && validas.includes(tabFlash)) {
        cambiarSegVista(tabFlash);
    } else if (tieneErrorRecurso && validas.includes(oldCat)) {
        cambiarSegVista(oldCat);
    }

    function wireFilter(inputId, selectId, rowSelector, counterId) {
        const input = document.getElementById(inputId);
        const select = document.getElementById(selectId);
        const counter = document.getElementById(counterId);
        const rows = document.querySelectorAll(rowSelector);
        if (!rows.length) return;
        const apply = () => {
            const q = (input?.value || '').toLowerCase().trim();
            const st = select?.value || '';
            let visible = 0;
            rows.forEach(row => {
                const matchText = !q || (row.dataset.texto || '').includes(q);
                const matchStatus = !st || row.dataset.status === st;
                const show = matchText && matchStatus;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (counter) counter.textContent = visible;
        };
        input?.addEventListener('input', apply);
        select?.addEventListener('change', apply);
    }

    wireFilter('buscador-tickets-seg', 'filtro-status-tickets-seg', '.fila-ticket-seg', 'contador-tickets-seg');
    wireFilter('buscador-sesiones-seg', 'filtro-status-sesiones-seg', '.fila-sesion-seg', 'contador-sesiones-seg');
})();
</script>
@endif

<script>
    document.getElementById('buscador-colegios')?.addEventListener('input', function () {
        const texto = this.value.toLowerCase().trim();
        document.querySelectorAll('#tabla-colegios tr[data-nombre]').forEach(function (fila) {
            fila.style.display = fila.dataset.nombre.includes(texto) ? '' : 'none';
        });
    });
</script>

@if($puedeConfigurar)
    @include('seguimiento.partials._catalogo_acciones_modal')
@endif
@endsection
