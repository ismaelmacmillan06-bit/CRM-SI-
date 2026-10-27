@extends('layouts.app')

@section('title', 'Sesiones — ' . $school->name)

@section('content')
@if($school->status === 'inactivo')
<div style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:12px 16px;
            border-radius:8px; margin-bottom:16px; font-size:13.5px">
    ⚠️ Este colegio está <strong>inactivo</strong> — actívalo en
    <a href="{{ route('schools.edit', $school) }}" style="color:#92400e; text-decoration:underline">Editar colegio</a>
    para poder agregar sesiones aquí.
</div>
@endif
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    @hasanyrole('admin|consultor_digital')
    @if($school->status !== 'inactivo')
    <a href="{{ route('schools.visits.create', $school) }}" class="btn btn-primary">+ Nueva Sesión</a>
    @endif
    @endhasanyrole
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">📅 Sesiones — {{ $school->name }}</span>
        <div style="display:flex; align-items:center; gap:12px">
            <select id="filtro-producto" class="form-control" style="width:auto; padding:5px 10px; font-size:13px">
                <option value="">Todos los productos</option>
                @foreach(\App\Models\Visit::PRODUCTOS as $producto)
                    <option value="{{ $producto }}">{{ $producto }}</option>
                @endforeach
            </select>
            <span style="font-size:13px; color:var(--text-muted)"><span id="contador-sesiones">{{ $visits->count() }}</span> registradas</span>
        </div>
    </div>
<style>
    .tabla-sesiones { table-layout:auto; }
    .tabla-sesiones th, .tabla-sesiones td { padding:12px 10px; }
    .tabla-sesiones th:first-child, .tabla-sesiones td:first-child { padding-left:20px; }
    .tabla-sesiones th:last-child, .tabla-sesiones td:last-child { padding-right:20px; }
    .tabla-sesiones .stack { display:flex; flex-direction:column; align-items:flex-start; gap:5px; }
    .tabla-sesiones .fechas { font-size:12.5px; line-height:1.6; white-space:nowrap; }
    .tabla-sesiones .fechas span { color:var(--text-muted); display:inline-block; min-width:38px; font-size:11px; text-transform:uppercase; letter-spacing:.4px; }
    .tabla-sesiones .acciones { display:flex; gap:6px; flex-wrap:wrap; }
    .tabla-sesiones .acciones form { margin:0; }
</style>
    <table class="table tabla-sesiones">
        <thead>
            <tr>
                <th>Consultor</th>
                <th>Sesión</th>
                <th>Producto</th>
                <th>Prioridad / Status</th>
                <th>Fechas</th>
                <th>Evidencia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($visits as $visit)
            @php
                [$pBg, $pFg] = \App\Models\Visit::PRIORIDAD_COLORES[$visit->prioridad] ?? ['#f3f4f6', '#374151'];
                $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
            @endphp
            <tr class="fila-sesion" data-productos="{{ json_encode($visit->productos ?? []) }}">
                <td><strong>{{ $visit->consultant->user->name ?? '—' }}</strong></td>
                <td>
                    <div style="font-size:13px; font-weight:600">{{ \App\Models\Visit::TIPOS[$visit->tipo] ?? '—' }}</div>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:2px">{{ \App\Models\Visit::MODALIDADES[$visit->modalidad] ?? '—' }}</div>
                </td>
                <td>
                    @include('partials.producto-chips', ['productos' => $visit->productos, 'contenedor' => 'max-width:200px'])
                </td>
                <td>
                    <div class="stack">
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
                <td class="fechas">
                    <div><span>Prog.</span>{{ $visit->tipo === 'emergencia' ? 'Sin programar' : $fmt($visit->scheduled_date) }}</div>
                    <div><span>Real</span>{{ $fmt($visit->visit_date) }}</div>
                    <div><span>Próx.</span>{{ $fmt($visit->next_visit_date) }}</div>
                </td>
                <td>
                    @if($visit->evidence)
                        <a href="{{ asset('storage/' . $visit->evidence) }}" target="_blank">
                            <img src="{{ asset('storage/' . $visit->evidence) }}"
                                 style="width:40px; height:40px; object-fit:cover; border-radius:6px;">
                        </a>
                    @else
                        <span style="color:var(--text-muted)">—</span>
                    @endif
                </td>
                <td>
                    @hasanyrole('admin|consultor_digital')
                    <div class="acciones">
                        <a href="{{ route('visits.edit', $visit) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <form method="POST" action="{{ route('visits.destroy', $visit) }}"
                              onsubmit="return confirm('¿Eliminar esta sesión?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                    @endhasanyrole
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay sesiones registradas para este colegio.
                    <a href="{{ route('schools.visits.create', $school) }}">Registra la primera</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<script>
    // Filtro por producto (la tabla ya está completa en la página)
    const filtroProducto = document.getElementById('filtro-producto');
    filtroProducto.addEventListener('change', () => {
        let visibles = 0;
        document.querySelectorAll('.fila-sesion').forEach(fila => {
            const productos = JSON.parse(fila.dataset.productos || '[]');
            const mostrar = !filtroProducto.value || productos.includes(filtroProducto.value);
            fila.style.display = mostrar ? '' : 'none';
            if (mostrar) visibles++;
        });
        document.getElementById('contador-sesiones').textContent = visibles;
    });
</script>
@endsection