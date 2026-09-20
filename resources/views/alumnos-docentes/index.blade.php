@extends('layouts.app')

@section('title', 'Alumnos Docentes')

@section('content')

<div style="display:flex; align-items:center; gap:12px; margin-bottom:24px; flex-wrap:wrap">
    <div>
        <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
            🎓 Alumnos Docentes
        </h2>
        <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
            Alumnos por nivel en cada colegio, y buscador de usuarios (alumno o docente).
        </p>
    </div>
    <a href="{{ route('alumnos-docentes.exportar') }}" class="btn btn-secondary" style="margin-left:auto">
        ⬇ Descargar Excel
    </a>
</div>

{{-- Buscador de usuario --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form id="form-buscar-usuario" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap" onsubmit="return false">
            <input type="text" id="input-usuario" class="form-control"
                   placeholder="🔍 Buscar por usuario MEE (alumno o docente)..."
                   style="max-width:360px">
            <button type="button" id="btn-buscar-usuario" class="btn btn-primary">Buscar</button>
            <span id="buscar-status" style="font-size:13px; color:var(--text-muted)"></span>
        </form>
    </div>
</div>

{{-- Tabla de alumnos por nivel --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">📊 Alumnos por nivel — todos los colegios</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $filas->count() }} colegios</span>
    </div>
    <div style="overflow-x:auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Colegio</th>
                    @foreach($niveles as $nivel)
                        <th style="text-align:center; white-space:nowrap">{{ $nivel }}</th>
                    @endforeach
                    <th style="text-align:center; white-space:nowrap">Otros</th>
                    <th style="text-align:center; white-space:nowrap">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filas as $fila)
                <tr>
                    <td><strong>{{ $fila['school']->name }}</strong></td>
                    @foreach($niveles as $nivel)
                        <td style="text-align:center; {{ $fila['niveles'][$nivel] > 0 ? '' : 'color:var(--text-muted)' }}">
                            {{ $fila['niveles'][$nivel] }}
                        </td>
                    @endforeach
                    <td style="text-align:center; {{ $fila['otros'] > 0 ? '' : 'color:var(--text-muted)' }}">
                        {{ $fila['otros'] }}
                    </td>
                    <td style="text-align:center"><strong>{{ $fila['total'] }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($niveles) + 3 }}" style="text-align:center; color:var(--text-muted); padding:40px">
                        No hay colegios registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($filas->isNotEmpty())
            <tfoot>
                <tr style="background:var(--surface2); font-weight:700">
                    <td>Total general</td>
                    @foreach($niveles as $nivel)
                        <td style="text-align:center">{{ $filas->sum(fn($f) => $f['niveles'][$nivel]) }}</td>
                    @endforeach
                    <td style="text-align:center">{{ $filas->sum('otros') }}</td>
                    <td style="text-align:center">{{ $filas->sum('total') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Modal de resultados de búsqueda --}}
<div id="modal-resultado-usuario" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
     z-index:999; align-items:center; justify-content:center; padding:20px">
    <div style="background:#fff; border-radius:12px; padding:28px; width:560px; max-width:100%; max-height:80vh; overflow-y:auto">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px">
            <h3 style="font-family:'Bricolage Grotesque',sans-serif; font-size:17px; font-weight:600; margin:0">
                🔍 Resultados de búsqueda
            </h3>
            <button onclick="document.getElementById('modal-resultado-usuario').style.display='none'"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#666">✕</button>
        </div>
        <div id="resultado-usuario-body">
            {{-- Se llena vía JS --}}
        </div>
    </div>
</div>

<script>
const inputUsuario   = document.getElementById('input-usuario');
const btnBuscar      = document.getElementById('btn-buscar-usuario');
const buscarStatus   = document.getElementById('buscar-status');
const modalResultado = document.getElementById('modal-resultado-usuario');
const resultadoBody  = document.getElementById('resultado-usuario-body');

const SCHOOL_URL_BASE = "{{ url('schools') }}";

async function buscarUsuario() {
    const q = inputUsuario.value.trim();
    if (!q) return;

    buscarStatus.textContent = 'Buscando…';
    try {
        const resp = await fetch(`{{ route('alumnos-docentes.buscar') }}?usuario=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const resultados = await resp.json();
        buscarStatus.textContent = '';
        mostrarResultados(resultados, q);
    } catch (e) {
        buscarStatus.textContent = 'Error al buscar.';
        console.error(e);
    }
}

function esc(s) {
    return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

function mostrarResultados(resultados, query) {
    if (!resultados.length) {
        resultadoBody.innerHTML = `<div style="text-align:center; padding:24px; color:var(--text-muted)">
            No se encontró ningún alumno o docente con el usuario "<strong>${esc(query)}</strong>".
        </div>`;
    } else {
        resultadoBody.innerHTML = resultados.map(r => `
            <div style="display:flex; align-items:center; gap:12px; padding:12px 14px; border:1px solid var(--border);
                        border-radius:10px; margin-bottom:10px">
                <span class="badge ${r.tipo === 'Docente' ? 'badge-warning' : 'badge-info'}" style="flex:none">
                    ${r.tipo === 'Docente' ? '🧑‍🏫' : '🎓'} ${esc(r.tipo)}
                </span>
                <div style="flex:1; min-width:0">
                    <div style="font-weight:600; font-size:14px">${esc(r.nombre || '—')}</div>
                    <div style="font-size:12px; color:var(--text-muted)">
                        Usuario: <span style="font-family:monospace">${esc(r.usuario)}</span> · ${esc(r.school_name)}
                    </div>
                </div>
                <a href="${SCHOOL_URL_BASE}/${r.school_id}" class="btn btn-primary btn-sm" style="flex:none">Ir →</a>
            </div>
        `).join('');
    }
    modalResultado.style.display = 'flex';
}

btnBuscar.addEventListener('click', buscarUsuario);
inputUsuario.addEventListener('keydown', e => {
    if (e.key === 'Enter') buscarUsuario();
});
</script>
@endsection
