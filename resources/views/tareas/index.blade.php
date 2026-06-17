@extends('layouts.app')

@section('title', 'Tareas SI')

@section('content')

@if(session('success'))
<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;
            padding:12px 18px; margin-bottom:20px; color:#166534; font-size:14px">
    ✅ {{ session('success') }}
</div>
@endif

{{-- Header --}}
<div style="display:flex; align-items:center; gap:12px; margin-bottom:24px; flex-wrap:wrap">
    <div>
        <h2 style="font-family:'Space Grotesk',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
            ✅ Tareas SI
        </h2>
        <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
            Seguimiento de acciones por colegio
        </p>
    </div>

    @can('admin')
    <button onclick="document.getElementById('modal-nueva-tarea').style.display='flex'"
            style="margin-left:auto; display:inline-flex; align-items:center; gap:6px;
                   padding:10px 20px; background:var(--accent); color:#fff; border:none;
                   border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;
                   transition:background 0.2s"
            onmouseover="this.style.background='#d63651'"
            onmouseout="this.style.background='var(--accent)'">
        + Tarea SI
    </button>
    @endcan
</div>

{{-- Modal Nueva Tarea --}}
<div id="modal-nueva-tarea" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
     z-index:999; align-items:center; justify-content:center; padding:20px">
    <div style="background:#fff; border-radius:12px; padding:32px; width:520px; max-width:100%">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h3 style="font-family:'Space Grotesk',sans-serif; font-size:18px; font-weight:600; margin:0">
                ✅ Nueva Tarea SI
            </h3>
            <button onclick="document.getElementById('modal-nueva-tarea').style.display='none'"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#666">✕</button>
        </div>
        <form method="POST" action="{{ route('tareas.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Título de la tarea *</label>
                <input type="text" name="titulo" class="form-control" required
                       placeholder="Ej: Mandar mensajes de bienvenida">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción (opcional)</label>
                <textarea name="descripcion" class="form-control" rows="3"
                          placeholder="Detalla la acción que debe realizarse..."></textarea>
            </div>
            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;
                        padding:10px 14px; font-size:13px; color:#1e40af; margin-bottom:20px">
                Esta tarea se creará como <strong>pendiente</strong> para todos los colegios registrados.
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end">
                <button type="button"
                        onclick="document.getElementById('modal-nueva-tarea').style.display='none'"
                        class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear tarea</button>
            </div>
        </form>
    </div>
</div>

{{-- Panel de tareas + tabla --}}
<div style="display:grid; grid-template-columns:260px 1fr; gap:20px; align-items:start">

    {{-- Lista de tareas --}}
    <div class="card" style="position:sticky; top:80px">
        <div class="card-header">
            <span class="card-title" style="font-size:14px">📋 Tareas creadas</span>
            <span style="font-size:12px; color:var(--text-muted)">{{ $tareas->count() }}</span>
        </div>
        <div style="padding:8px">
            @forelse($tareas as $t)
            @php
                $p = $t->progreso();
                $pct = $p['total'] > 0 ? round(($p['realizada'] / $p['total']) * 100) : 0;
                $isActive = $tarea && $tarea->id === $t->id;
            @endphp
            <a href="{{ route('tareas.index', ['tarea_id' => $t->id]) }}"
               style="display:block; padding:12px; border-radius:8px; text-decoration:none;
                      margin-bottom:4px; border:1px solid {{ $isActive ? 'var(--accent)' : 'transparent' }};
                      background:{{ $isActive ? '#fff5f5' : 'transparent' }};
                      transition:background 0.15s"
               onmouseover="if(!{{ $isActive ? 'true' : 'false' }}) this.style.background='var(--surface2)'"
               onmouseout="if(!{{ $isActive ? 'true' : 'false' }}) this.style.background='transparent'">
                <div style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px">
                    {{ $t->titulo }}
                </div>
                <div style="background:var(--surface2); border-radius:20px; height:4px; overflow:hidden; margin-bottom:4px">
                    <div style="height:100%; width:{{ $pct }}%; background:#10b981; border-radius:20px"></div>
                </div>
                <div style="font-size:11px; color:var(--text-muted)">
                    {{ $p['realizada'] }}/{{ $p['total'] }} realizadas · {{ $pct }}%
                </div>
            </a>
            @empty
            <div style="text-align:center; padding:30px; color:var(--text-muted); font-size:13px">
                No hay tareas aún.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Tabla de colegios para la tarea activa --}}
    <div>
        @if($tarea)
        <div class="card">
            <div class="card-header">
                <div>
                    <span class="card-title">{{ $tarea->titulo }}</span>
                    @if($tarea->descripcion)
                    <div style="font-size:12px; color:var(--text-muted); margin-top:2px">{{ $tarea->descripcion }}</div>
                    @endif
                </div>
                <div style="display:flex; align-items:center; gap:8px">
                    @php $p = $tarea->progreso(); @endphp
                    <span class="badge badge-warning">{{ $p['pendiente'] }} pend.</span>
                    <span class="badge badge-info">{{ $p['en_proceso'] }} proceso</span>
                    <span class="badge badge-success">{{ $p['realizada'] }} listas</span>
                    @can('admin')
                    <form method="POST" action="{{ route('tareas.destroy', $tarea) }}"
                          onsubmit="return confirm('¿Eliminar esta tarea?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">🗑</button>
                    </form>
                    @endcan
                </div>
            </div>

            {{-- Buscador rápido --}}
            <div style="padding:12px 20px; border-bottom:1px solid var(--border)">
                <input type="text" id="buscador-tareas" class="form-control"
                       placeholder="🔍 Buscar colegio..."
                       style="max-width:320px">
            </div>

            <table class="table" id="tabla-tareas">
                <thead>
                    <tr>
                        <th>Colegio</th>
                        <th>Ciudad</th>
                        <th>Estado actual</th>
                        <th style="text-align:center; min-width:230px">Cambiar estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schools as $school)
                    @php $status = $statusMap[$school->id] ?? 'pendiente'; @endphp
                    <tr class="tarea-row" data-nombre="{{ strtolower($school->name) }}">
                        <td><strong>{{ $school->name }}</strong></td>
                        <td style="font-size:13px; color:var(--text-muted)">{{ $school->city ?? '—' }}</td>
                        <td>
                            <span id="badge-{{ $school->id }}"
                                  class="badge {{ $status === 'realizada' ? 'badge-success' : ($status === 'en_proceso' ? 'badge-info' : 'badge-warning') }}">
                                {{ $status === 'realizada' ? '✓ Realizada' : ($status === 'en_proceso' ? '⟳ En proceso' : '○ Pendiente') }}
                            </span>
                        </td>
                        <td style="text-align:center">
                            <div style="display:inline-flex; gap:4px">
                                <button onclick="cambiarEstado({{ $school->id }}, 'pendiente', this)"
                                        class="btn-estado {{ $status === 'pendiente' ? 'activo' : '' }}"
                                        data-valor="pendiente"
                                        style="padding:5px 10px; font-size:12px; border-radius:6px; border:1px solid #f59e0b;
                                               background:{{ $status === 'pendiente' ? '#f59e0b' : 'transparent' }};
                                               color:{{ $status === 'pendiente' ? '#fff' : '#b45309' }};
                                               cursor:pointer; transition:all 0.15s; font-weight:500">
                                    ○ Pendiente
                                </button>
                                <button onclick="cambiarEstado({{ $school->id }}, 'en_proceso', this)"
                                        class="btn-estado {{ $status === 'en_proceso' ? 'activo' : '' }}"
                                        data-valor="en_proceso"
                                        style="padding:5px 10px; font-size:12px; border-radius:6px; border:1px solid #3b82f6;
                                               background:{{ $status === 'en_proceso' ? '#3b82f6' : 'transparent' }};
                                               color:{{ $status === 'en_proceso' ? '#fff' : '#1d4ed8' }};
                                               cursor:pointer; transition:all 0.15s; font-weight:500">
                                    ⟳ En proceso
                                </button>
                                <button onclick="cambiarEstado({{ $school->id }}, 'realizada', this)"
                                        class="btn-estado {{ $status === 'realizada' ? 'activo' : '' }}"
                                        data-valor="realizada"
                                        style="padding:5px 10px; font-size:12px; border-radius:6px; border:1px solid #10b981;
                                               background:{{ $status === 'realizada' ? '#10b981' : 'transparent' }};
                                               color:{{ $status === 'realizada' ? '#fff' : '#065f46' }};
                                               cursor:pointer; transition:all 0.15s; font-weight:500">
                                    ✓ Realizada
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="card">
            <div class="card-body" style="text-align:center; padding:60px; color:var(--text-muted)">
                <div style="font-size:48px; margin-bottom:16px">✅</div>
                <div style="font-size:16px; font-weight:600; margin-bottom:8px">No hay tareas creadas</div>
                <div style="font-size:13px">Crea la primera tarea con el botón "+ Tarea SI"</div>
            </div>
        </div>
        @endif
    </div>

</div>

<script>
const TAREA_ID = {{ $tarea?->id ?? 'null' }};

async function cambiarEstado(schoolId, nuevoStatus, btnClicked) {
    if (!TAREA_ID) return;

    const row = btnClicked.closest('tr');
    const botones = row.querySelectorAll('.btn-estado');
    const badge = document.getElementById('badge-' + schoolId);

    // Optimistic UI
    botones.forEach(btn => {
        const val = btn.dataset.valor;
        const colores = {
            pendiente:  { border:'#f59e0b', bg:'#f59e0b', color:'#fff', off:'#b45309' },
            en_proceso: { border:'#3b82f6', bg:'#3b82f6', color:'#fff', off:'#1d4ed8' },
            realizada:  { border:'#10b981', bg:'#10b981', color:'#fff', off:'#065f46' },
        };
        const c = colores[val];
        if (val === nuevoStatus) {
            btn.style.background = c.bg;
            btn.style.color = c.color;
        } else {
            btn.style.background = 'transparent';
            btn.style.color = c.off;
        }
    });

    const badgeMap = {
        pendiente:  { cls: 'badge-warning', txt: '○ Pendiente' },
        en_proceso: { cls: 'badge-info',    txt: '⟳ En proceso' },
        realizada:  { cls: 'badge-success', txt: '✓ Realizada' },
    };
    badge.className = 'badge ' + badgeMap[nuevoStatus].cls;
    badge.textContent = badgeMap[nuevoStatus].txt;

    try {
        const resp = await fetch(`/tareas/${TAREA_ID}/colegios/${schoolId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ status: nuevoStatus }),
        });
        if (!resp.ok) console.error('Error al guardar estado');
    } catch(e) {
        console.error(e);
    }
}

// Buscador
document.getElementById('buscador-tareas')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.tarea-row').forEach(row => {
        row.style.display = !q || row.dataset.nombre.includes(q) ? '' : 'none';
    });
});
</script>
@endsection
