<div id="formato-entrega-resultado" class="alert alert-success" role="status" aria-live="polite"
    data-student-review-count="{{ $formatoResumen['studentReviewCount'] ?? 0 }}"
    data-student-class-count="{{ $formatoResumen['studentClassCount'] ?? 0 }}"
    data-teacher-review-count="{{ $formatoResumen['teacherReviewCount'] ?? 0 }}"
    data-teacher-class-count="{{ $formatoResumen['teacherClassCount'] ?? 0 }}"
    data-student-count="{{ $formatoResumen['studentCount'] ?? 0 }}"
    data-teacher-count="{{ $formatoResumen['teacherCount'] ?? 0 }}"
    data-persistent
    style="display:none; white-space:pre-line; margin-top:-12px; margin-bottom:20px">
</div>
<div id="formato-entrega-dialog" class="formato-modal-overlay" aria-hidden="true">
    <section class="formato-modal-panel" role="dialog" aria-modal="true" aria-labelledby="formato-entrega-titulo">
        <header class="formato-modal-header">
            <div>
                <span class="formato-modal-kicker">{{ $schoolName ?? '' }}</span>
                <h2 id="formato-entrega-titulo">{{ $modalTitle ?? 'Formato de entrega Global Educa' }}</h2>
                <span class="formato-modal-badge">{{ $modalBadge ?? '🌐 Exclusivo para colegios Global Educa' }}</span>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" data-formato-cancel aria-label="Cerrar">✕</button>
        </header>
        <div class="formato-tabs" data-formato-tabs role="tablist">
            <button type="button" class="formato-tab is-active" role="tab" aria-selected="true" data-formato-tab="excel">
                <span aria-hidden="true">📊</span> Excel
            </button>
            <button type="button" class="formato-tab" role="tab" aria-selected="false" data-formato-tab="pdf">
                <span aria-hidden="true">🖨️</span> Tarjetas PDF
            </button>
        </div>
        <fieldset class="formato-opciones" data-formato-opciones>
            <legend>¿Qué cuentas necesitas?</legend>
            <label class="formato-opcion is-selected">
                <input type="radio" name="formato-tipo" value="alumnos" checked>
                <span><strong>Alumnos</strong><small data-excel="Un Excel con las hojas por clase." data-pdf="Un PDF con tarjetas recortables por clase.">Un Excel con las hojas por clase.</small></span>
            </label>
            <label class="formato-opcion">
                <input type="radio" name="formato-tipo" value="docentes">
                <span><strong>Docentes</strong><small data-excel="Un Excel con las hojas por grado." data-pdf="Un PDF con tarjetas recortables por grado.">Un Excel con las hojas por grado.</small></span>
            </label>
            <label class="formato-opcion">
                <input type="radio" name="formato-tipo" value="ambos">
                <span><strong>Ambos</strong><small data-excel="Descarga dos archivos independientes." data-pdf="Descarga dos PDF independientes.">Descarga dos archivos independientes.</small></span>
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
    .formato-modal-badge {
        display:inline-block; margin-top:8px; padding:3px 10px; border-radius:999px; font-size:11.5px;
        font-weight:600; background:#dcfce7; color:#16a34a;
    }
    .formato-modal-header h2 { margin:0; font-family:'Bricolage Grotesque',sans-serif; font-size:21px; }
    .formato-tabs {
        display:grid; grid-template-columns:1fr 1fr; gap:4px; padding:4px; margin-bottom:22px;
        border:1px solid var(--border); border-radius:10px; background:var(--surface2);
    }
    .formato-tabs[hidden] { display:none; }
    .formato-tab {
        display:flex; align-items:center; justify-content:center; gap:8px; padding:9px 12px;
        border:0; border-radius:7px; background:transparent; color:var(--text-muted);
        font:600 13px 'Spline Sans',sans-serif; cursor:pointer; transition:background .15s, color .15s, box-shadow .15s;
    }
    .formato-tab:hover { color:var(--text); }
    .formato-tab.is-active { background:var(--surface); color:var(--accent); box-shadow:0 1px 4px rgba(24,19,17,.14); }
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
