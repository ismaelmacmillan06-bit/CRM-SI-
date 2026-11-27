@extends('layouts.app')

@section('title', 'Detalle del Ticket — ' . $ticket->title)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.tickets.index', $ticket->school) }}" class="btn btn-secondary btn-sm">← Regresar a Tickets</a>
    @puedeGestionar($ticket->school)
    <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-primary btn-sm">✏️ Editar ticket</a>
    @endpuedeGestionar
</div>

<div class="card" style="max-width:860px">
    <div class="card-header" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
        <span class="card-title">🎫 {{ $ticket->title }}</span>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
            @if($ticket->priority === 'high')
                <span class="badge badge-danger">🔴 Alta</span>
            @elseif($ticket->priority === 'medium')
                <span class="badge badge-warning">🟡 Media</span>
            @else
                <span class="badge badge-gray">🟢 Baja</span>
            @endif

            @if($ticket->status === 'open')
                <span class="badge badge-danger">Abierto</span>
            @elseif($ticket->status === 'in_progress')
                <span class="badge badge-warning">En progreso</span>
            @else
                <span class="badge badge-success">Cerrado</span>
            @endif
        </div>
    </div>

    <div class="card-body" style="display:flex; flex-direction:column; gap:20px">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;
                    padding:16px; border-radius:10px; background:var(--surface2); border:1px solid var(--border)">
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:4px">Colegio</div>
                <div style="font-size:14px; font-weight:600; color:var(--text)">{{ $ticket->school->name ?? '—' }}</div>
                @if($ticket->school?->nexus_id)
                    <div style="font-size:12px; color:var(--text-muted)">Nexus ID: {{ $ticket->school->nexus_id }}</div>
                @endif
            </div>

            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:4px">Consultor</div>
                <div style="font-size:14px; font-weight:600; color:var(--text)">{{ $ticket->consultant->user->name ?? 'Sin consultor' }}</div>
            </div>

            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:4px">Medio</div>
                <div>
                    @if($ticket->medium == 'salesforce')
                        <span class="badge badge-info">Sales Force</span>
                    @elseif($ticket->medium == 'whaticket')
                        <span class="badge badge-warning">Whaticket</span>
                    @else
                        <span class="badge badge-success">WhatsApp</span>
                    @endif
                </div>
            </div>

            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:4px">Fechas</div>
                <div style="font-size:13px; color:var(--text)">Creado: <strong>{{ $ticket->created_at?->format('d/m/Y H:i') ?? '—' }}</strong></div>
                <div style="font-size:13px; color:var(--text-muted)">
                    Resuelto: <strong>{{ $ticket->resolved_at ? \Carbon\Carbon::parse($ticket->resolved_at)->format('d/m/Y H:i') : 'Pendiente' }}</strong>
                </div>
            </div>
        </div>

        <div>
            <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px">Productos</div>
            <div style="display:flex; gap:6px; flex-wrap:wrap">
                @forelse($ticket->productos ?? [] as $producto)
                    @php [$ico, $color] = \App\Models\Visit::PRODUCTO_ESTILOS[$producto] ?? ['📦', '#64748b']; @endphp
                    <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:999px;
                                 font-size:12.5px; font-weight:600; color:{{ $color }};
                                 background:color-mix(in srgb, {{ $color }} 12%, transparent)">{{ $ico }} {{ $producto }}</span>
                @empty
                    <span style="color:var(--text-muted); font-size:13px">Sin productos asignados</span>
                @endforelse
            </div>
        </div>

        <div>
            <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px">Descripción</div>
            <div style="padding:14px 16px; border:1px solid var(--border); border-radius:10px; background:#fff;
                        font-size:14px; line-height:1.6; color:var(--text); white-space:pre-line">{{ $ticket->description }}</div>
        </div>

        @if($ticket->evidence)
        <div>
            <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px">Evidencia</div>
            <a href="{{ Storage::url($ticket->evidence) }}" target="_blank" rel="noopener" style="display:inline-block">
                <img src="{{ Storage::url($ticket->evidence) }}" alt="Evidencia del ticket"
                     style="max-width:420px; width:100%; border-radius:10px; border:1px solid var(--border); box-shadow:0 4px 14px rgba(0,0,0,0.08)">
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
