@extends('layouts.app')

@section('title', $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.index') }}" class="btn btn-secondary btn-sm">← Regresar</a>
    @hasanyrole('admin|consultor_digital')
    <a href="{{ route('schools.edit', $school) }}" class="btn btn-primary btn-sm">✏️ Editar</a>
    @endhasanyrole
    <a href="{{ route('schools.processes.index', $school) }}" class="btn btn-secondary btn-sm">⚙️ Acciones de Arranque</a>
    <a href="{{ route('schools.teachers.index', $school) }}" class="btn btn-secondary btn-sm">👨‍🏫 Docentes</a>
    <a href="{{ route('schools.tickets.index', $school) }}" class="btn btn-secondary btn-sm">🎫 Tickets</a>
    <a href="{{ route('schools.visits.index', $school) }}" class="btn btn-secondary btn-sm">📅 Visitas</a>
    <a href="{{ route('schools.students.index', $school) }}" class="btn btn-secondary btn-sm">👨‍🎓 Alumnos</a>
    <a href="{{ route('schools.formato-entrega', $school) }}" class="btn btn-secondary btn-sm" data-formato-entrega>📥 Formato de entrega</a>
    <a href="{{ route('schools.bundles.index', $school) }}" class="btn btn-secondary btn-sm">📚 Bundles</a>
    <a href="{{ route('schools.repositorio.index', $school) }}" class="btn btn-secondary btn-sm">📁 Repositorio</a>
    @hasanyrole('admin|consultor_digital|coordinador')
    <a href="{{ route('schools.reporte-master', $school) }}"
       class="btn btn-sm"
       style="background:#C0392B; color:#fff; border-color:#C0392B; font-weight:700; letter-spacing:.5px">
        📊 REPORT MASTER
    </a>
    @endhasanyrole
</div>
<div id="formato-entrega-resultado" class="alert alert-success" role="status" aria-live="polite"
    data-student-review-count="{{ $formatoResumen['studentReviewCount'] }}"
    data-student-class-count="{{ $formatoResumen['studentClassCount'] }}"
    data-teacher-review-count="{{ $formatoResumen['teacherReviewCount'] }}"
    data-teacher-class-count="{{ $formatoResumen['teacherClassCount'] }}"
    data-student-count="{{ $formatoResumen['studentCount'] }}"
    data-teacher-count="{{ $formatoResumen['teacherCount'] }}"
    data-persistent
    style="display:none; white-space:pre-line; margin-top:-12px; margin-bottom:20px">
</div>
<div id="formato-entrega-dialog" class="formato-modal-overlay" aria-hidden="true">
    <section class="formato-modal-panel" role="dialog" aria-modal="true" aria-labelledby="formato-entrega-titulo">
        <header class="formato-modal-header">
            <div>
                <span class="formato-modal-kicker">{{ $school->name }}</span>
                <h2 id="formato-entrega-titulo">Formato de entrega</h2>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" data-formato-cancel aria-label="Cerrar">✕</button>
        </header>
        <fieldset class="formato-opciones" data-formato-opciones>
            <legend>¿Qué cuentas necesitas?</legend>
            <label class="formato-opcion is-selected">
                <input type="radio" name="formato-tipo" value="alumnos" checked>
                <span><strong>Alumnos</strong><small>Un Excel con las hojas por clase.</small></span>
            </label>
            <label class="formato-opcion">
                <input type="radio" name="formato-tipo" value="docentes">
                <span><strong>Docentes</strong><small>Un Excel con las hojas por grado.</small></span>
            </label>
            <label class="formato-opcion">
                <input type="radio" name="formato-tipo" value="ambos">
                <span><strong>Ambos</strong><small>Descarga dos archivos independientes.</small></span>
            </label>
        </fieldset>
        <div class="formato-aviso" data-formato-aviso role="alert" aria-live="assertive" hidden>
            <strong data-formato-aviso-titulo></strong>
            <p data-formato-aviso-mensaje></p>
        </div>
        <footer class="formato-modal-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-formato-cancel>Cancelar</button>
            <button type="button" class="btn btn-primary btn-sm" data-formato-confirm hidden></button>
            <button type="button" class="btn btn-primary btn-sm" data-formato-generar>Generar formato</button>
        </footer>
    </section>
</div>
<div id="formato-entrega-overlay" class="formato-loading-overlay" aria-hidden="true" role="status" aria-live="polite">
    <div class="formato-loading-panel">
        <div class="formato-loading-spinner" aria-hidden="true"></div>
        <div class="formato-loading-title">Generando Reporte Completo</div>
        <div class="formato-loading-subtitle">Un momento por favor<span data-formato-dots></span></div>
        <div class="formato-loading-note">Este proceso puede tardar unos momentos<br>dependiendo de la cantidad de registros.</div>
    </div>
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
    .formato-modal-panel [hidden] { display:none !important; }
    .formato-modal-panel::before { content:""; position:absolute; inset:0 0 auto; height:4px; background:var(--accent); }
    .formato-modal-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:24px; }
    .formato-modal-kicker { display:block; margin-bottom:5px; color:var(--text-muted); font-size:12px; }
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
    .formato-aviso {
        padding:16px; border:1px solid var(--border); border-left:4px solid var(--accent);
        border-radius:8px; background:var(--surface2); animation:formatoFadeIn .2s ease;
    }
    .formato-aviso strong { display:block; margin-bottom:6px; font-size:15px; }
    .formato-aviso p { margin:0; color:var(--text-muted); font-size:13px; line-height:1.5; }
    .formato-loading-overlay {
        position:fixed; inset:0; z-index:1300; display:none; align-items:center; justify-content:center;
        padding:20px; background:rgba(0,0,0,.65); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);
    }
    .formato-loading-overlay.is-open { display:flex; }
    .formato-loading-panel {
        width:min(380px, 100%); padding:44px 52px; border-radius:20px; background:var(--surface);
        color:var(--text); text-align:center; box-shadow:0 30px 70px rgba(0,0,0,.4);
        animation:formatoFadeIn .25s ease;
    }
    .formato-loading-spinner {
        width:60px; height:60px; margin:0 auto 28px; border:5px solid var(--border);
        border-top-color:#1d4ed8; border-radius:50%; animation:formatoSpin .85s linear infinite;
    }
    .formato-loading-title {
        margin-bottom:10px; font-family:'Bricolage Grotesque',sans-serif; font-size:20px;
        font-weight:700; line-height:1.2;
    }
    .formato-loading-subtitle { margin-bottom:20px; color:var(--text-muted); font-size:14px; }
    .formato-loading-note {
        padding-top:16px; border-top:1px solid var(--border); color:var(--text-muted);
        font-size:12px; line-height:1.6; opacity:.6;
    }
    @keyframes formatoSpin { to { transform:rotate(360deg); } }
    @keyframes formatoFadeIn { from { opacity:0; transform:scale(.96); } to { opacity:1; transform:scale(1); } }
    @media (max-width:480px) { .formato-modal-panel { padding:24px 18px; } }
    @media (max-width:480px) { .formato-loading-panel { padding:40px 24px; } }
</style>
<script src="{{ route('schools.formato-entrega.script', $school) }}" defer></script>

<div class="grid-2" style="margin-bottom:24px">
    {{-- Info general --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">📋 Información general</span>
            @if($school->status === 'activo')
                <span class="badge badge-success">Activo</span>
            @elseif($school->status === 'prospecto')
                <span class="badge badge-warning">Prospecto</span>
            @else
                <span class="badge badge-gray">Inactivo</span>
            @endif
        </div>
        <div class="card-body">
            <table style="width:100%; font-size:14px; border-collapse:collapse">
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted); width:40%">Nexus ID</td>
                    <td style="padding:8px 0"><strong>{{ $school->nexus_id ?? '—' }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Ciudad</td>
                    <td style="padding:8px 0">{{ $school->state ?? $school->city ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Dirección</td>
                    <td style="padding:8px 0">{{ $school->address ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Teléfono</td>
                    <td style="padding:8px 0">{{ $school->phone ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Email</td>
                    <td style="padding:8px 0">{{ $school->email ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Consultor</td>
                    <td style="padding:8px 0">{{ $school->consultant->user->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Registro</td>
                    <td style="padding:8px 0">{{ $school->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Niveles</td>
                    <td style="padding:8px 0">
                        @foreach($school->levels as $level)
                            <span class="badge badge-info">{{ $level->name }}</span>
                        @endforeach
                    </td>
                </tr>
            </table>
            @if($school->notes)
                <div style="margin-top:16px; padding:12px; background:var(--surface2);
                            border-radius:8px; font-size:13px; color:var(--text-muted)">
                    {{ $school->notes }}
                </div>
            @endif
        </div>
    </div>

    {{-- Acciones de arranque --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">⚙️ Acciones de arranque</span>
            <a href="{{ route('schools.processes.index', $school) }}" class="btn btn-secondary btn-sm">Ver detalle</a>
        </div>
        <div class="card-body">
            @foreach($school->schoolLevels as $schoolLevel)
            <div style="margin-bottom:20px">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px">
                    <strong style="font-size:14px">{{ $schoolLevel->level->name }}</strong>
                    @php
                        $total = $schoolLevel->processes->count();
                        $done  = $schoolLevel->processes->where('status', 'done')->count();
                        $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
                    @endphp
                    <span style="font-size:12px; color:var(--text-muted)">{{ $done }}/{{ $total }}</span>
                </div>
                <div style="background:var(--surface2); border-radius:20px; height:8px; overflow:hidden">
                    <div style="height:100%; width:{{ $pct }}%; background:var(--accent);
                                border-radius:20px; transition:width 0.3s"></div>
                </div>
                <div style="font-size:11px; color:var(--text-muted); margin-top:4px">{{ $pct }}% completado</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection