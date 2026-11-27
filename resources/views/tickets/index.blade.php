@extends('layouts.app')

@section('title', 'Tickets — ' . $school->name)

@section('content')
@if($school->status === 'inactivo')
<div style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:12px 16px;
            border-radius:8px; margin-bottom:16px; font-size:13.5px">
    ⚠️ Este colegio está <strong>inactivo</strong> — actívalo en
    <a href="{{ route('schools.edit', $school) }}" style="color:#92400e; text-decoration:underline">Editar colegio</a>
    para poder agregar tickets aquí.
</div>
@endif
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    @puedeGestionar($school)
    @if($school->status !== 'inactivo')
    <a href="{{ route('schools.tickets.create', $school) }}" class="btn btn-primary">+ Nuevo Ticket</a>
    @endif
    @endpuedeGestionar
    <button type="button"
            onclick="abrirModalReporteTickets()"
            class="btn btn-secondary">
        📊 Generar Reporte
    </button>
</div>

{{-- Modal: Generar Reporte de Tickets (estilo Global Educa) --}}
<div id="modal-reporte-tickets" class="formato-modal-overlay" aria-hidden="true"
     onclick="if(event.target===this)cerrarModalReporteTickets()">
    <section class="formato-modal-panel" role="dialog" aria-modal="true" aria-labelledby="reporte-tickets-titulo">
        <header class="formato-modal-header">
            <div>
                <span class="formato-modal-kicker">{{ $school->name }} @if($school->nexus_id)· Nexus {{ $school->nexus_id }}@endif</span>
                <h2 id="reporte-tickets-titulo">Generar reporte de tickets</h2>
                <span class="formato-modal-badge">🎫 {{ $tickets->count() }} {{ $tickets->count() === 1 ? 'ticket registrado' : 'tickets registrados' }}</span>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="cerrarModalReporteTickets()" aria-label="Cerrar">✕</button>
        </header>

        <fieldset class="formato-opciones">
            <legend>¿En qué formato deseas el reporte?</legend>
            <label class="formato-opcion is-selected">
                <input type="radio" name="reporte-ticket-formato" value="excel" checked onchange="actualizarFormatoReporteTickets(this)">
                <span>
                    <strong>📊 Excel (.xlsx)</strong>
                    <small>Incluye datos del colegio, Nexus ID, gráficos nativos y detalle completo.</small>
                </span>
            </label>
            <label class="formato-opcion">
                <input type="radio" name="reporte-ticket-formato" value="pdf" onchange="actualizarFormatoReporteTickets(this)">
                <span>
                    <strong>📄 PDF visual con gráficos</strong>
                    <small>Abre la vista ejecutiva con gráficas vectoriales lista para guardar en PDF.</small>
                </span>
            </label>
            <label class="formato-opcion">
                <input type="radio" name="reporte-ticket-formato" value="ambos" onchange="actualizarFormatoReporteTickets(this)">
                <span>
                    <strong>📦 Ambos (Excel + PDF)</strong>
                    <small>Descarga el Excel con gráficos y abre la vista PDF del mismo periodo.</small>
                </span>
            </label>
        </fieldset>

        <div style="margin-top:18px; padding-top:16px; border-top:1px solid var(--border)">
            <div style="font-size:13px; font-weight:600; margin-bottom:10px">Periodo de tiempo</div>
            <div class="reporte-presets" style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px">
                <button type="button" class="reporte-preset is-active" data-preset="todo" onclick="aplicarPresetReporte('todo', this)">Todo</button>
                <button type="button" class="reporte-preset" data-preset="hoy" onclick="aplicarPresetReporte('hoy', this)">Hoy</button>
                <button type="button" class="reporte-preset" data-preset="semana_actual" onclick="aplicarPresetReporte('semana_actual', this)">Semana en curso</button>
                <button type="button" class="reporte-preset" data-preset="semana_pasada" onclick="aplicarPresetReporte('semana_pasada', this)">Semana pasada</button>
                <button type="button" class="reporte-preset" data-preset="mes_actual" onclick="aplicarPresetReporte('mes_actual', this)">Mes en curso</button>
                <button type="button" class="reporte-preset" data-preset="mes_pasado" onclick="aplicarPresetReporte('mes_pasado', this)">Mes pasado</button>
            </div>
            <div class="grid-2" style="gap:10px">
                <div>
                    <label for="reporte-desde" style="display:block; font-size:11.5px; font-weight:600; color:var(--text-muted); margin-bottom:4px">Desde</label>
                    <input type="date" id="reporte-desde" class="form-control" onchange="alCambiarFechaManual()">
                </div>
                <div>
                    <label for="reporte-hasta" style="display:block; font-size:11.5px; font-weight:600; color:var(--text-muted); margin-bottom:4px">Hasta</label>
                    <input type="date" id="reporte-hasta" class="form-control" onchange="alCambiarFechaManual()">
                </div>
            </div>
        </div>

        <footer class="formato-modal-actions">
            <button type="button" class="btn btn-secondary btn-sm" onclick="cerrarModalReporteTickets()">Cancelar</button>
            <a id="btn-generar-reporte-tickets"
               href="{{ route('schools.tickets.report.excel', $school) }}"
               class="btn btn-primary btn-sm"
               onclick="return ejecutarReporteTickets(event)">
                Generar reporte
            </a>
        </footer>
    </section>
</div>

<style>
    .formato-modal-overlay {
        position:fixed; inset:0; z-index:1200; display:none; align-items:center; justify-content:center;
        padding:20px; background:rgba(24,19,17,.58); backdrop-filter:blur(3px);
    }
    .formato-modal-overlay.is-open { display:flex; }
    .formato-modal-panel {
        position:relative; width:min(480px, 100%); max-height:calc(100vh - 40px); overflow:auto;
        padding:28px; border:1px solid var(--border); border-radius:10px; background:var(--surface);
        color:var(--text); box-shadow:0 22px 64px rgba(24,19,17,.28);
    }
    .formato-modal-panel::before { content:""; position:absolute; inset:0 0 auto; height:4px; background:var(--accent); }
    .formato-modal-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:22px; }
    .formato-modal-kicker { display:block; margin-bottom:5px; color:var(--text-muted); font-size:12px; }
    .formato-modal-badge {
        display:inline-block; margin-top:8px; padding:3px 10px; border-radius:999px; font-size:11.5px;
        font-weight:600; background:#dcfce7; color:#16a34a;
    }
    .formato-modal-header h2 { margin:0; font-family:'Bricolage Grotesque',sans-serif; font-size:21px; }
    .formato-opciones { display:grid; grid-template-columns:1fr; gap:9px; border:0; padding:0; margin:0; }
    .formato-opciones legend { margin-bottom:10px; font-size:13px; font-weight:600; }
    .formato-opcion {
        display:flex; align-items:center; gap:12px; padding:12px 14px; border:1px solid var(--border);
        border-radius:8px; background:#fff; cursor:pointer; transition:border-color .15s, background .15s;
    }
    .formato-opcion:hover, .formato-opcion.is-selected { border-color:var(--accent); background:#fff8f6; }
    .formato-opcion input { width:16px; height:16px; margin:0; accent-color:var(--accent); flex:0 0 auto; }
    .formato-opcion span { display:grid; gap:3px; }
    .formato-opcion strong { font-size:14px; }
    .formato-opcion small { color:var(--text-muted); font-size:12px; }
    .formato-modal-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:24px; }
    .reporte-preset {
        padding:5px 10px; border:1px solid var(--border); border-radius:999px; background:#fff;
        color:var(--text-muted); font-size:12px; font-weight:600; cursor:pointer; transition:all .15s;
    }
    .reporte-preset:hover { border-color:var(--accent); color:var(--text); }
    .reporte-preset.is-active { background:var(--accent); border-color:var(--accent); color:#fff; }
</style>

<script>
const REPORTE_TICKETS_URLS = {
    excel: @json(route('schools.tickets.report.excel', $school)),
    pdf:   @json(route('schools.tickets.report.pdf', $school)),
};

function abrirModalReporteTickets() {
    const modal = document.getElementById('modal-reporte-tickets');
    if (modal) modal.classList.add('is-open');
    actualizarUrlReporteTickets();
}
function cerrarModalReporteTickets() {
    const modal = document.getElementById('modal-reporte-tickets');
    if (modal) modal.classList.remove('is-open');
}
function actualizarFormatoReporteTickets(radio) {
    document.querySelectorAll('#modal-reporte-tickets .formato-opcion').forEach(el => {
        el.classList.toggle('is-selected', el.contains(radio));
    });
    actualizarUrlReporteTickets();
}
function fmtFechaInput(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}
function aplicarPresetReporte(preset, btn) {
    document.querySelectorAll('.reporte-preset').forEach(el => el.classList.toggle('is-active', el === btn));
    const inputDesde = document.getElementById('reporte-desde');
    const inputHasta = document.getElementById('reporte-hasta');
    const hoy = new Date();
    let desde = '';
    let hasta = '';

    if (preset === 'hoy') {
        desde = fmtFechaInput(hoy);
        hasta = fmtFechaInput(hoy);
    } else if (preset === 'semana_actual') {
        const day = hoy.getDay() || 7; // lunes = 1 .. domingo = 7
        const lunes = new Date(hoy);
        lunes.setDate(hoy.getDate() - (day - 1));
        desde = fmtFechaInput(lunes);
        hasta = fmtFechaInput(hoy);
    } else if (preset === 'semana_pasada') {
        const day = hoy.getDay() || 7;
        const lunesPasado = new Date(hoy);
        lunesPasado.setDate(hoy.getDate() - (day - 1) - 7);
        const domingoPasado = new Date(lunesPasado);
        domingoPasado.setDate(lunesPasado.getDate() + 6);
        desde = fmtFechaInput(lunesPasado);
        hasta = fmtFechaInput(domingoPasado);
    } else if (preset === 'mes_actual') {
        const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        desde = fmtFechaInput(inicioMes);
        hasta = fmtFechaInput(hoy);
    } else if (preset === 'mes_pasado') {
        const inicioMesPasado = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
        const finMesPasado = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
        desde = fmtFechaInput(inicioMesPasado);
        hasta = fmtFechaInput(finMesPasado);
    }

    inputDesde.value = desde;
    inputHasta.value = hasta;
    actualizarUrlReporteTickets();
}
function alCambiarFechaManual() {
    document.querySelectorAll('.reporte-preset').forEach(el => el.classList.remove('is-active'));
    actualizarUrlReporteTickets();
}
function buildReporteUrl(tipo) {
    const url = new URL(REPORTE_TICKETS_URLS[tipo], window.location.origin);
    const desde = document.getElementById('reporte-desde')?.value || '';
    const hasta = document.getElementById('reporte-hasta')?.value || '';
    if (desde) url.searchParams.set('desde', desde);
    if (hasta) url.searchParams.set('hasta', hasta);
    return url.toString();
}
function actualizarUrlReporteTickets() {
    const btn = document.getElementById('btn-generar-reporte-tickets');
    if (!btn) return;
    const formato = document.querySelector('input[name="reporte-ticket-formato"]:checked')?.value || 'excel';
    if (formato === 'ambos') {
        btn.href = buildReporteUrl('excel');
        btn.removeAttribute('target');
        btn.removeAttribute('rel');
        return;
    }
    btn.href = buildReporteUrl(formato);
    if (formato === 'pdf') {
        btn.target = '_blank';
        btn.rel = 'noopener';
    } else {
        btn.removeAttribute('target');
        btn.removeAttribute('rel');
    }
}
function ejecutarReporteTickets(e) {
    const formato = document.querySelector('input[name="reporte-ticket-formato"]:checked')?.value || 'excel';
    cerrarModalReporteTickets();
    if (formato === 'ambos') {
        e.preventDefault();
        window.open(buildReporteUrl('pdf'), '_blank', 'noopener');
        const link = document.createElement('a');
        link.href = buildReporteUrl('excel');
        document.body.appendChild(link);
        link.click();
        link.remove();
        return false;
    }
    return true;
}
</script>

<div class="card">
    <div class="card-header">
        <span class="card-title">🎫 Tickets — {{ $school->name }}</span>
        <div style="display:flex; align-items:center; gap:12px">
            <select id="filtro-producto-tickets" class="form-control" style="width:auto; padding:5px 10px; font-size:13px">
                <option value="">Todos los productos</option>
                @foreach(\App\Models\Visit::PRODUCTOS as $producto)
                    <option value="{{ $producto }}">{{ $producto }}</option>
                @endforeach
            </select>
            <span style="font-size:13px; color:var(--text-muted)"><span id="contador-tickets">{{ $tickets->count() }}</span> registrados</span>
        </div>
    </div>
    <table class="table">
        <thead>
    <tr>
        <th>Título</th>
        <th>Medio</th>
        <th>Producto</th>
        <th>Prioridad</th>
        <th>Status</th>
        <th>Consultor</th>
        <th>Evidencia</th>
        <th>Creado</th>
        <th>Resuelto</th>
        <th>Acciones</th>
    </tr>
</thead>

        <tbody>
            @forelse($tickets as $ticket)
            <tr class="fila-ticket" data-productos="{{ json_encode($ticket->productos ?? []) }}">
                <td>
                    <strong>{{ $ticket->title }}</strong>
                    <br>
                    <small style="color:var(--text-muted)">{{ Str::limit($ticket->description, 60) }}</small>
                </td>

                <td>
                    @if($ticket->medium == 'salesforce')
                        <span class="badge badge-info">Sales Force</span>
                    @elseif($ticket->medium == 'whaticket')
                        <span class="badge badge-warning">Whaticket</span>
                    @else
                        <span class="badge badge-success">WhatsApp</span>
                    @endif
                </td>

                <td>
                    @forelse($ticket->productos ?? [] as $producto)
                        @php [$ico, $color] = \App\Models\Visit::PRODUCTO_ESTILOS[$producto] ?? ['📦', '#64748b']; @endphp
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; margin:1px 0; border-radius:999px;
                                     font-size:11.5px; font-weight:600; white-space:nowrap; color:{{ $color }};
                                     background:color-mix(in srgb, {{ $color }} 12%, transparent)">{{ $ico }} {{ $producto }}</span>
                    @empty
                        <span style="color:var(--text-muted); font-size:12px">—</span>
                    @endforelse
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
                <td>
                    @if($ticket->evidence)
                        <a href="{{ Storage::url($ticket->evidence) }}" target="_blank" title="Ver evidencia">
                            <img src="{{ Storage::url($ticket->evidence) }}" alt="evidencia"
                                 style="height:40px; width:40px; object-fit:cover; border-radius:4px; border:1px solid var(--border)">
                        </a>
                    @else
                        <span style="color:var(--text-muted); font-size:12px">—</span>
                    @endif
                </td>
                <td style="font-size:13px; color:var(--text-muted)">
                    {{ $ticket->created_at->format('d/m/Y') }}
                </td>
                <td style="font-size:13px; color:var(--text-muted)">
                    {{ $ticket->resolved_at ? \Carbon\Carbon::parse($ticket->resolved_at)->format('d/m/Y') : '—' }}
                </td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-secondary btn-sm">Ver</a>
                        @puedeGestionar($school)
                        <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-secondary btn-sm">Editar</a>
                        @puedeEliminar($school)
                        <form method="POST" action="{{ route('tickets.destroy', $ticket) }}"
                              id="form-ticket-{{ $ticket->id }}"
                              onsubmit="return confirm('¿Eliminar este ticket?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                        @endpuedeEliminar
                        @endpuedeGestionar
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay tickets registrados para este colegio.
                    <a href="{{ route('schools.tickets.create', $school) }}">Registra el primero</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<script>
    const filtroProductoTickets = document.getElementById('filtro-producto-tickets');
    function aplicarFiltroProductoTickets() {
        let visibles = 0;
        document.querySelectorAll('.fila-ticket').forEach(fila => {
            const productos = JSON.parse(fila.dataset.productos || '[]');
            const mostrar = !filtroProductoTickets.value || productos.includes(filtroProductoTickets.value);
            fila.style.display = mostrar ? '' : 'none';
            if (mostrar) visibles++;
        });
        document.getElementById('contador-tickets').textContent = visibles;
    }
    filtroProductoTickets.addEventListener('change', aplicarFiltroProductoTickets);

    const productoTicketParam = new URLSearchParams(window.location.search).get('producto');
    if (productoTicketParam && Array.from(filtroProductoTickets.options).some(o => o.value === productoTicketParam)) {
        filtroProductoTickets.value = productoTicketParam;
        aplicarFiltroProductoTickets();
    }
</script>
@endsection