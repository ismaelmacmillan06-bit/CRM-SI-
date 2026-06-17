@extends('layouts.app')

@section('title', 'Tareas SI')

@section('content')

{{-- Header --}}
<div style="display:flex; align-items:center; gap:12px; margin-bottom:24px; flex-wrap:wrap">
    <div>
        <h2 style="font-family:'Space Grotesk',sans-serif; font-size:22px; font-weight:700;
                   color:var(--text); margin:0">
            ✅ Tareas SI
        </h2>
        <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
            Seguimiento de acciones por colegio
        </p>
    </div>

    @if(auth()->user()->hasRole('admin'))
    <button onclick="document.getElementById('modal-nueva-tarea').style.display='flex'"
            style="margin-left:auto; display:inline-flex; align-items:center; gap:6px;
                   padding:10px 20px; background:var(--accent); color:#fff; border:none;
                   border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;
                   transition:background 0.2s"
            onmouseover="this.style.background='#d63651'"
            onmouseout="this.style.background='var(--accent)'">
        + Tarea SI
    </button>
    @endif
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

{{-- Layout principal --}}
<div style="display:grid; grid-template-columns:260px 1fr; gap:20px; align-items:start">

    {{-- Panel izquierdo: lista de tareas --}}
    <div class="card" style="position:sticky; top:80px">
        <div class="card-header">
            <span class="card-title" style="font-size:14px">📋 Tareas</span>
            <span style="font-size:12px; color:var(--text-muted)">{{ $tareas->count() }}</span>
        </div>
        <div style="padding:8px">
            @forelse($tareas as $t)
            @php
                $p       = $t->progreso();
                $pct     = $p['total'] > 0 ? round(($p['realizada'] / $p['total']) * 100) : 0;
                $isActive = $tarea && $tarea->id === $t->id;
            @endphp
            <a href="{{ route('tareas.index', ['tarea_id' => $t->id]) }}"
               style="display:block; padding:12px; border-radius:8px; text-decoration:none; margin-bottom:4px;
                      border:1px solid {{ $isActive ? 'var(--accent)' : 'transparent' }};
                      background:{{ $isActive ? '#fff5f5' : 'transparent' }};
                      transition:background 0.15s">
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
                No hay tareas aún.<br>
                @if(auth()->user()->hasRole('admin'))
                <span style="font-size:12px">Usa "+ Tarea SI" para crear una.</span>
                @endif
            </div>
            @endforelse
        </div>
    </div>

    {{-- Panel derecho: tabla de colegios (siempre visible) --}}
    <div class="card">
        <div class="card-header" style="flex-wrap:wrap; gap:8px">
            <div>
                @if($tarea)
                    <span class="card-title">{{ $tarea->titulo }}</span>
                    @if($tarea->descripcion)
                    <div style="font-size:12px; color:var(--text-muted); margin-top:2px">
                        {{ $tarea->descripcion }}
                    </div>
                    @endif
                @else
                    <span class="card-title">Colegios</span>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:2px">
                        Selecciona una tarea del panel izquierdo para gestionar estados
                    </div>
                @endif
            </div>

            @if($tarea)
            @php $p = $tarea->progreso(); @endphp
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap">
                <span class="badge badge-warning">{{ $p['pendiente'] }} pend.</span>
                <span class="badge badge-info">{{ $p['en_proceso'] }} proceso</span>
                <span class="badge badge-success">{{ $p['realizada'] }} listas</span>
                @if(auth()->user()->hasRole('admin'))
                <form method="POST" action="{{ route('tareas.destroy', $tarea) }}"
                      onsubmit="return confirm('¿Eliminar esta tarea?')" style="margin:0">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm" style="padding:4px 10px">🗑 Eliminar</button>
                </form>
                @endif
            </div>
            @endif
        </div>

        {{-- Buscador rápido --}}
        <div style="padding:10px 20px; border-bottom:1px solid var(--border)">
            <input type="text" id="buscador-tareas" class="form-control"
                   placeholder="🔍 Buscar colegio..." style="max-width:300px">
        </div>

        <table class="table" id="tabla-tareas">
            <thead>
                <tr>
                    <th>Colegio</th>
                    <th>Consultor Digital</th>
                    <th>Ciudad</th>
                    @if($tarea)
                    <th style="text-align:center">Estado</th>
                    <th style="text-align:center; min-width:240px">Cambiar estado</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $school)
                @php $status = $statusMap[$school->id] ?? 'pendiente'; @endphp
                <tr class="tarea-row" data-nombre="{{ strtolower($school->name) }}">
                    <td><strong>{{ $school->name }}</strong></td>
                    <td style="font-size:13px; color:var(--text-muted)">
                        {{ $school->schoolConsultants->where('role','digital')->first()?->consultant->user->name ?? '—' }}
                    </td>
                    <td style="font-size:13px; color:var(--text-muted)">{{ $school->city ?? '—' }}</td>

                    @if($tarea)
                    <td style="text-align:center">
                        <span id="badge-{{ $school->id }}"
                              class="badge {{ $status === 'realizada' ? 'badge-success' : ($status === 'en_proceso' ? 'badge-info' : 'badge-warning') }}">
                            {{ $status === 'realizada' ? '✓ Realizada' : ($status === 'en_proceso' ? '⟳ En proceso' : '○ Pendiente') }}
                        </span>
                    </td>
                    <td style="text-align:center">
                        <div style="display:inline-flex; gap:4px">
                            <button onclick="cambiarEstado({{ $school->id }}, 'pendiente', this)"
                                    data-valor="pendiente"
                                    style="padding:5px 10px; font-size:12px; border-radius:6px;
                                           border:1px solid #f59e0b; cursor:pointer; transition:all 0.15s; font-weight:500;
                                           background:{{ $status === 'pendiente' ? '#f59e0b' : 'transparent' }};
                                           color:{{ $status === 'pendiente' ? '#fff' : '#b45309' }}">
                                ○ Pendiente
                            </button>
                            <button onclick="cambiarEstado({{ $school->id }}, 'en_proceso', this)"
                                    data-valor="en_proceso"
                                    style="padding:5px 10px; font-size:12px; border-radius:6px;
                                           border:1px solid #3b82f6; cursor:pointer; transition:all 0.15s; font-weight:500;
                                           background:{{ $status === 'en_proceso' ? '#3b82f6' : 'transparent' }};
                                           color:{{ $status === 'en_proceso' ? '#fff' : '#1d4ed8' }}">
                                ⟳ En proceso
                            </button>
                            <button onclick="cambiarEstado({{ $school->id }}, 'realizada', this)"
                                    data-valor="realizada"
                                    style="padding:5px 10px; font-size:12px; border-radius:6px;
                                           border:1px solid #10b981; cursor:pointer; transition:all 0.15s; font-weight:500;
                                           background:{{ $status === 'realizada' ? '#10b981' : 'transparent' }};
                                           color:{{ $status === 'realizada' ? '#fff' : '#065f46' }}">
                                ✓ Realizada
                            </button>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $tarea ? 5 : 3 }}"
                        style="text-align:center; color:var(--text-muted); padding:40px">
                        No hay colegios registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<script>
const TAREA_ID = {{ $tarea?->id ?? 'null' }};

const colores = {
    pendiente:  { bg:'#f59e0b', color:'#fff', off:'#b45309', border:'#f59e0b' },
    en_proceso: { bg:'#3b82f6', color:'#fff', off:'#1d4ed8', border:'#3b82f6' },
    realizada:  { bg:'#10b981', color:'#fff', off:'#065f46', border:'#10b981' },
};
const badgeMap = {
    pendiente:  { cls:'badge-warning', txt:'○ Pendiente' },
    en_proceso: { cls:'badge-info',    txt:'⟳ En proceso' },
    realizada:  { cls:'badge-success', txt:'✓ Realizada'  },
};

function aplicarEstadoUI(row, badge, nuevoStatus) {
    row.querySelectorAll('button[data-valor]').forEach(btn => {
        const val = btn.dataset.valor;
        const c   = colores[val];
        btn.style.background = val === nuevoStatus ? c.bg : 'transparent';
        btn.style.color      = val === nuevoStatus ? c.color : c.off;
        btn.disabled = false;
    });
    if (badge) {
        badge.className   = 'badge ' + badgeMap[nuevoStatus].cls;
        badge.textContent = badgeMap[nuevoStatus].txt;
    }
}

function mostrarToast(msg, ok = true) {
    const t = document.getElementById('toast-estado');
    t.textContent  = msg;
    t.style.background = ok ? '#10b981' : '#ef4444';
    t.style.opacity = '1';
    t.style.transform = 'translateY(0)';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => {
        t.style.opacity   = '0';
        t.style.transform = 'translateY(20px)';
    }, 2500);
}

async function cambiarEstado(schoolId, nuevoStatus, btnClicked) {
    if (!TAREA_ID) return;

    const row   = btnClicked.closest('tr');
    const badge = document.getElementById('badge-' + schoolId);

    // Deshabilitar mientras guarda
    row.querySelectorAll('button[data-valor]').forEach(b => b.disabled = true);
    btnClicked.textContent = '⏳';

    try {
        const resp = await fetch(`/tareas/${TAREA_ID}/colegios/${schoolId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ status: nuevoStatus }),
        });

        if (resp.ok) {
            // Restaurar texto del botón
            const textos = { pendiente:'○ Pendiente', en_proceso:'⟳ En proceso', realizada:'✓ Realizada' };
            btnClicked.textContent = textos[nuevoStatus];
            aplicarEstadoUI(row, badge, nuevoStatus);
            mostrarToast('✓ Guardado');
        } else {
            throw new Error('HTTP ' + resp.status);
        }
    } catch(e) {
        // Restaurar texto original
        const textos = { pendiente:'○ Pendiente', en_proceso:'⟳ En proceso', realizada:'✓ Realizada' };
        row.querySelectorAll('button[data-valor]').forEach(b => {
            b.disabled = false;
            b.textContent = textos[b.dataset.valor];
        });
        mostrarToast('✗ Error al guardar — intenta de nuevo', false);
        console.error(e);
    }
}

document.getElementById('buscador-tareas')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.tarea-row').forEach(row => {
        row.style.display = !q || row.dataset.nombre.includes(q) ? '' : 'none';
    });
});
</script>

{{-- Toast de confirmación --}}
<div id="toast-estado"
     style="position:fixed; bottom:24px; right:24px; padding:12px 20px; border-radius:10px;
            color:#fff; font-size:14px; font-weight:600; z-index:9999;
            opacity:0; transform:translateY(20px);
            transition:opacity 0.3s ease, transform 0.3s ease;
            pointer-events:none; box-shadow:0 4px 12px rgba(0,0,0,0.2)">
</div>
@endsection
