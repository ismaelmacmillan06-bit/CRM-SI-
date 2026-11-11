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
                   style="flex:1 1 220px; max-width:420px">
            <button type="button" id="btn-buscar-usuario" class="btn btn-primary">Buscar</button>
            <span id="buscar-status" style="font-size:13px; color:var(--text-muted)"></span>
        </form>
    </div>
</div>

{{-- Tabla de alumnos por nivel --}}
<div class="card">
    <div class="card-header" style="flex-wrap:wrap; gap:10px">
        <span class="card-title">📊 Alumnos por nivel — todos los colegios</span>
        <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap">
            <div style="display:flex; align-items:center; gap:8px">
                <label for="orden-total" style="font-size:13px; color:var(--text-muted); white-space:nowrap">↕ Ordenar por total de alumnos:</label>
                <select id="orden-total" class="form-control" style="max-width:200px" onchange="ordenarFilasAlumnos()">
                    <option value="">Sin ordenar</option>
                    <option value="desc">Mayor a menor</option>
                    <option value="asc">Menor a mayor</option>
                </select>
            </div>
            <span style="font-size:13px; color:var(--text-muted)">{{ $filas->count() }} colegios</span>
        </div>
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
            <tbody id="tbody-alumnos-nivel">
                @forelse($filas as $fila)
                <tr data-total="{{ $fila['total'] }}">
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
<style>
    .ru-overlay { display:none; position:fixed; inset:0; background:rgba(24,19,17,.55); z-index:999;
                  align-items:center; justify-content:center; padding:20px; }
    .ru-overlay.abierto { display:flex; }
    .ru-modal { background:var(--surface); border-radius:16px; width:580px; max-width:100%; max-height:85vh;
                display:flex; flex-direction:column; box-shadow:0 20px 50px rgba(0,0,0,.25); overflow:hidden; }
    .ru-head { display:flex; align-items:center; gap:10px; padding:18px 22px; border-bottom:1px solid var(--border); }
    .ru-head h3 { font-family:'Bricolage Grotesque',sans-serif; font-size:17px; font-weight:600; margin:0; }
    .ru-head .ru-count { font-size:12px; color:var(--text-muted); background:var(--surface2); border-radius:20px; padding:2px 10px; }
    .ru-close { margin-left:auto; background:none; border:none; width:34px; height:34px; border-radius:8px;
                font-size:18px; cursor:pointer; color:var(--text-muted); }
    .ru-close:hover { background:var(--surface2); color:var(--text); }
    .ru-body { padding:16px 22px 22px; overflow-y:auto; }
    .ru-card { border:1px solid var(--border); border-radius:12px; background:#fff; margin-bottom:12px; overflow:hidden; }
    .ru-card:last-child { margin-bottom:0; }
    .ru-top { display:flex; align-items:flex-start; gap:12px; padding:14px 16px 10px; }
    .ru-avatar { flex:none; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center;
                 font-size:20px; background:#E0ECFF; }
    .ru-avatar.docente { background:#FEF3C7; }
    .ru-name { font-weight:700; font-size:15px; line-height:1.3; color:var(--text); word-break:break-word; }
    .ru-sub { display:flex; flex-wrap:wrap; align-items:center; gap:6px; margin-top:4px; font-size:12px; color:var(--text-muted); }
    .ru-rows { padding:0 16px 12px; display:grid; gap:6px; }
    .ru-row { display:flex; align-items:center; gap:8px; background:var(--surface2); border-radius:8px; padding:7px 10px; min-width:0; }
    .ru-label { flex:none; width:86px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; color:var(--text-muted); }
    .ru-val { flex:1; min-width:0; font-size:13px; color:var(--text); overflow-wrap:anywhere; }
    .ru-val.mono { font-family:ui-monospace,Consolas,monospace; }
    .ru-icon { flex:none; background:none; border:none; cursor:pointer; padding:4px; border-radius:6px; color:var(--text-muted); line-height:0; }
    .ru-icon:hover { background:#fff; color:var(--text); }
    .ru-icon.ok { color:var(--success); }
    .ru-foot { border-top:1px solid var(--border); padding:10px 16px; display:flex; justify-content:flex-end; }
    .ru-empty { text-align:center; padding:28px 10px; color:var(--text-muted); font-size:14px; }
    .ru-empty .big { font-size:34px; display:block; margin-bottom:8px; }

    @media (max-width: 600px) {
        .ru-overlay { align-items:flex-end; padding:0; }
        .ru-modal { width:100%; max-height:88vh; border-radius:18px 18px 0 0; animation:ruSube .25s ease-out; }
        .ru-head { padding:14px 16px; }
        .ru-body { padding:14px 14px 18px; padding-bottom:calc(18px + env(safe-area-inset-bottom)); }
        .ru-label { width:82px; font-size:10.5px; letter-spacing:.2px; }
        .ru-foot .btn { width:100%; justify-content:center; text-align:center; }
    }
    @keyframes ruSube { from { transform:translateY(40px); opacity:.4; } to { transform:none; opacity:1; } }
</style>
<div id="modal-resultado-usuario" class="ru-overlay" role="dialog" aria-modal="true" aria-labelledby="ru-titulo">
    <div class="ru-modal">
        <div class="ru-head">
            <h3 id="ru-titulo">🔍 Resultados</h3>
            <span class="ru-count" id="ru-count"></span>
            <button type="button" class="ru-close" onclick="cerrarResultados()" aria-label="Cerrar">✕</button>
        </div>
        <div class="ru-body" id="resultado-usuario-body">
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

const ICONO_COPIAR = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
const ICONO_OK     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
const ICONO_OJO    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
let resultadosActuales = [];

function mostrarResultados(resultados, query) {
    resultadosActuales = resultados;
    document.getElementById('ru-count').textContent = resultados.length
        ? resultados.length + (resultados.length === 1 ? ' usuario' : ' usuarios')
        : '';

    if (!resultados.length) {
        resultadoBody.innerHTML = `<div class="ru-empty">
            <span class="big">🙈</span>
            No se encontró ningún alumno o docente con el usuario<br>"<strong>${esc(query)}</strong>".
        </div>`;
    } else {
        resultadoBody.innerHTML = resultados.map((r, i) => {
            const docente = r.tipo === 'Docente';
            return `
            <div class="ru-card">
                <div class="ru-top">
                    <div class="ru-avatar ${docente ? 'docente' : ''}">${docente ? '🧑‍🏫' : '🎓'}</div>
                    <div style="flex:1; min-width:0">
                        <div class="ru-name">${esc(r.nombre || '—')}</div>
                        <div class="ru-sub">
                            <span class="badge ${docente ? 'badge-warning' : 'badge-info'}">${esc(r.tipo)}</span>
                            ${r.detalle ? `<span>${esc(r.detalle)}</span>` : ''}
                        </div>
                    </div>
                </div>
                <div class="ru-rows">
                    <div class="ru-row">
                        <span class="ru-label">Colegio</span>
                        <span class="ru-val">🏫 ${esc(r.school_name)}</span>
                    </div>
                    <div class="ru-row">
                        <span class="ru-label">Usuario</span>
                        <span class="ru-val mono">${esc(r.usuario)}</span>
                        <button type="button" class="ru-icon" onclick="copiarDato(this, ${i}, 'usuario')" title="Copiar usuario">${ICONO_COPIAR}</button>
                    </div>
                    <div class="ru-row">
                        <span class="ru-label">Contraseña</span>
                        <span class="ru-val mono" id="pwd-val-${i}" data-oculta="1" style="letter-spacing:2px">••••••••</span>
                        ${r.contrasena ? `
                        <button type="button" class="ru-icon" id="pwd-icon-${i}" onclick="togglePwd(${i})" title="Mostrar / ocultar contraseña">${ICONO_OJO}</button>
                        <button type="button" class="ru-icon" onclick="copiarDato(this, ${i}, 'contrasena')" title="Copiar contraseña">${ICONO_COPIAR}</button>
                        ` : ''}
                    </div>
                </div>
                <div class="ru-foot">
                    <a href="${SCHOOL_URL_BASE}/${r.school_id}" class="btn btn-primary btn-sm">Ver colegio →</a>
                </div>
            </div>`;
        }).join('');
    }
    modalResultado.classList.add('abierto');
    document.body.style.overflow = 'hidden';
}

function cerrarResultados() {
    modalResultado.classList.remove('abierto');
    document.body.style.overflow = '';
}

modalResultado.addEventListener('click', e => { if (e.target === modalResultado) cerrarResultados(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape' && modalResultado.classList.contains('abierto')) cerrarResultados(); });

async function copiarDato(btn, i, campo) {
    const texto = (resultadosActuales[i] || {})[campo] || '';
    if (!texto) return;
    try {
        await navigator.clipboard.writeText(texto);
    } catch (e) {
        // Respaldo para navegadores sin Clipboard API (http o iOS viejo)
        const t = document.createElement('textarea');
        t.value = texto; t.style.position = 'fixed'; t.style.opacity = '0';
        document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove();
    }
    btn.innerHTML = ICONO_OK; btn.classList.add('ok');
    setTimeout(() => { btn.innerHTML = ICONO_COPIAR; btn.classList.remove('ok'); }, 1500);
}

function togglePwd(i) {
    const val  = document.getElementById('pwd-val-' + i);
    const icon = document.getElementById('pwd-icon-' + i);
    const eyeOff = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    if (val.dataset.oculta === '1') {
        val.textContent = resultadosActuales[i].contrasena;
        val.style.letterSpacing = '';
        val.dataset.oculta = '0';
        icon.innerHTML = eyeOff;
    } else {
        val.textContent = '••••••••';
        val.style.letterSpacing = '2px';
        val.dataset.oculta = '1';
        icon.innerHTML = ICONO_OJO;
    }
}

btnBuscar.addEventListener('click', buscarUsuario);
inputUsuario.addEventListener('keydown', e => {
    if (e.key === 'Enter') buscarUsuario();
});

// Orden por total de alumnos (mayor/menor colegio)
const ordenOriginalFilasAlumnos = Array.from(document.querySelectorAll('#tbody-alumnos-nivel tr[data-total]'));

function ordenarFilasAlumnos() {
    const tbody = document.getElementById('tbody-alumnos-nivel');
    const orden = document.getElementById('orden-total').value;

    let filas = ordenOriginalFilasAlumnos.slice();
    if (orden === 'desc') {
        filas.sort((a, b) => parseFloat(b.dataset.total) - parseFloat(a.dataset.total));
    } else if (orden === 'asc') {
        filas.sort((a, b) => parseFloat(a.dataset.total) - parseFloat(b.dataset.total));
    }
    filas.forEach(fila => tbody.appendChild(fila));
}
</script>
@endsection
