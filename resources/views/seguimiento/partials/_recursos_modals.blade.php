@php
    $esAdmin = auth()->user()?->hasRole('admin');
@endphp

<style>
    .sr-toolbar {
        display:flex; align-items:center; justify-content:space-between;
        gap:14px; flex-wrap:wrap; margin-bottom:18px;
    }
    .sr-section-title {
        font-family:'Bricolage Grotesque',sans-serif; font-size:18px; font-weight:700;
        color:var(--text); margin:0;
    }
    .sr-section-sub { font-size:13px; color:var(--text-muted); margin:3px 0 0; }
    .sr-toolbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .sr-search { width:240px; max-width:100%; }

    .sr-grid {
        display:grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap:18px;
    }
    .sr-card {
        background:var(--surface);
        border:1px solid var(--border);
        border-radius:14px;
        padding:20px;
        display:flex; flex-direction:column; gap:12px;
        box-shadow:0 1px 4px rgba(24,19,17,.05);
        transition:box-shadow .18s, transform .18s;
    }
    .sr-card:hover { box-shadow:0 6px 18px rgba(24,19,17,.08); transform:translateY(-1px); }

    .sr-card-head { display:flex; align-items:flex-start; gap:12px; }
    .sr-fmt-icon {
        width:44px; height:44px; flex-shrink:0;
        border-radius:11px; border:1px solid transparent;
        display:grid; place-items:center;
    }
    .sr-card-title-wrap { min-width:0; flex:1; }
    .sr-card-titulo {
        font-family:'Bricolage Grotesque',sans-serif;
        font-size:16.5px; font-weight:700; color:var(--text);
        line-height:1.25; word-break:break-word;
    }
    .sr-card-ext-row { display:flex; align-items:center; gap:6px; margin-top:5px; flex-wrap:wrap; }
    .sr-ext-pill {
        font-size:10.5px; font-weight:700; letter-spacing:.4px;
        padding:2px 8px; border-radius:99px; border:1px solid transparent;
    }
    .sr-size-pill { font-size:11.5px; color:var(--text-muted); font-weight:500; }

    .sr-audiencias { display:flex; flex-wrap:wrap; gap:5px; }
    .sr-aud-pill {
        font-size:11px; font-weight:600; padding:2px 9px; border-radius:99px;
        background:var(--surface2); border:1px solid var(--border); color:var(--text-muted);
    }

    .sr-card-desc {
        font-size:13.5px; color:#4A4540; line-height:1.5;
        display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;
    }

    .sr-img-preview-btn {
        border:1px solid var(--border); border-radius:10px; padding:0;
        background:#fff; overflow:hidden; cursor:pointer; display:block; width:100%;
    }
    .sr-card-img { width:100%; height:165px; object-fit:cover; display:block; transition:transform .2s; }
    .sr-img-preview-btn:hover .sr-card-img { transform:scale(1.02); }

    .sr-doc-preview {
        border:1px solid var(--border); border-radius:10px;
        padding:11px 13px; display:flex; align-items:center; gap:10px;
    }
    .sr-doc-preview-info { min-width:0; display:flex; flex-direction:column; gap:2px; }
    .sr-doc-filename {
        font-size:12.5px; font-weight:600; color:var(--text);
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }
    .sr-doc-hint { font-size:11.5px; color:var(--text-muted); }

    .sr-card-bottom { margin-top:auto; display:flex; flex-direction:column; gap:12px; }
    .sr-card-actions { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .sr-btn-action { justify-content:center; gap:6px; display:inline-flex; align-items:center; font-weight:600; }

    .sr-card-footer {
        display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;
        padding-top:10px; border-top:1px solid var(--border);
        font-size:12px; color:var(--text-muted);
    }
    .sr-meta strong { color:var(--text); }
    .sr-admin-btns { display:inline-flex; gap:6px; align-items:center; }
    .sr-mini-btn {
        background:var(--surface2); color:var(--text); border:1px solid var(--border);
        border-radius:7px; padding:4px 9px; font-size:11.5px; font-weight:600; cursor:pointer;
        transition:background .15s, border-color .15s;
    }
    .sr-mini-btn:hover { background:var(--border); }
    .sr-mini-btn.danger { background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
    .sr-mini-btn.danger:hover { background:#b91c1c; color:#fff; border-color:#b91c1c; }

    .sr-empty { text-align:center; padding:48px 24px; }
    .sr-empty-ico { font-size:40px; line-height:1; margin-bottom:10px; }
    .sr-empty-title { font-family:'Bricolage Grotesque',sans-serif; font-size:17px; font-weight:700; color:var(--text); }
    .sr-empty-text { font-size:13.5px; color:var(--text-muted); margin:6px auto 0; max-width:440px; }

    /* Modales */
    .sr-backdrop {
        display:none; position:fixed; inset:0; background:rgba(24,19,17,.58);
        backdrop-filter:blur(3px); z-index:990;
        align-items:center; justify-content:center; padding:18px;
    }
    .sr-backdrop.open { display:flex; }
    .sr-modal {
        background:var(--surface); border-radius:16px; width:100%; max-width:540px;
        max-height:90vh; display:flex; flex-direction:column; overflow:hidden;
        box-shadow:0 24px 64px -12px rgba(24,19,17,.35); position:relative;
    }
    .sr-modal.sr-modal-lg { max-width:920px; }
    .sr-modal-head {
        display:flex; align-items:center; justify-content:space-between; gap:12px;
        padding:18px 22px; border-bottom:1px solid var(--border);
    }
    .sr-modal-head h3 {
        font-family:'Bricolage Grotesque',sans-serif; font-size:18px; font-weight:700;
        margin:0; color:var(--text); min-width:0; word-break:break-word;
    }
    .sr-modal-close {
        width:32px; height:32px; border-radius:8px; border:none;
        background:var(--surface2); color:var(--text-muted); cursor:pointer;
        display:grid; place-items:center; flex-shrink:0; font-size:16px;
    }
    .sr-modal-close:hover { background:var(--border); color:var(--text); }
    .sr-modal-body {
        padding:20px 22px; overflow-y:auto; flex:1; min-height:0;
        scrollbar-width:thin; scrollbar-color:#d6d1c8 transparent;
    }
    .sr-modal-body::-webkit-scrollbar { width:6px; }
    .sr-modal-body::-webkit-scrollbar-track { background:transparent; }
    .sr-modal-body::-webkit-scrollbar-thumb { background:#d6d1c8; border-radius:999px; }
    .sr-modal-body::-webkit-scrollbar-button { display:none; width:0; height:0; }

    /* Dropzone */
    .sr-dropzone {
        border:1.5px dashed var(--border); border-radius:11px; padding:20px 16px;
        text-align:center; cursor:pointer; background:#fff;
        transition:border-color .16s, background .16s;
    }
    .sr-dropzone:hover { border-color:var(--accent); background:rgba(226,35,26,.03); }
    .sr-dropzone input { display:none; }
    .sr-dropzone-title { font-size:13.5px; font-weight:600; color:var(--accent); }
    .sr-dropzone-sub { font-size:12px; color:var(--text-muted); margin-top:4px; }

    /* Custom CRM Checkboxes para roles */
    .sr-roles-grid {
        display:grid; grid-template-columns:repeat(auto-fill, minmax(195px, 1fr)); gap:8px;
        margin-top:6px;
    }
    .sr-role-opt {
        display:flex; align-items:center; gap:9px;
        padding:9px 11px; border:1px solid var(--border); border-radius:9px;
        background:#fff; cursor:pointer; transition:border-color .15s, background .15s;
        user-select:none;
    }
    .sr-role-opt:hover { border-color:#c9c2b8; background:#fdfcfb; }
    .sr-role-opt.checked { border-color:var(--accent); background:#fff7f6; }
    .sr-role-opt input { position:absolute; opacity:0; width:1px; height:1px; pointer-events:none; }
    .sr-chk-box {
        width:18px; height:18px; border-radius:5px; flex-shrink:0;
        border:1.5px solid #cbc4b8; background:#fff;
        display:grid; place-items:center; transition:all .15s;
    }
    .sr-role-opt.checked .sr-chk-box {
        background:var(--accent); border-color:var(--accent);
    }
    .sr-chk-box svg { width:12px; height:12px; color:#fff; opacity:0; transition:opacity .12s; }
    .sr-role-opt.checked .sr-chk-box svg { opacity:1; }
    .sr-role-lbl { font-size:13px; font-weight:600; color:var(--text); line-height:1.2; }

    /* Preview modal content */
    .sr-pv-meta { font-size:12.5px; color:var(--text-muted); margin-bottom:12px; display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .sr-pv-desc { font-size:13.5px; color:#4A4540; margin-bottom:14px; background:var(--surface2); padding:10px 14px; border-radius:9px; }
    .sr-pv-frame { width:100%; height:62vh; border:1px solid var(--border); border-radius:10px; background:#fff; }
    .sr-pv-img { max-width:100%; max-height:62vh; display:block; margin:0 auto; border-radius:10px; border:1px solid var(--border); }
    .sr-pv-sheet-wrap { overflow:auto; max-height:58vh; border:1px solid var(--border); border-radius:10px; background:#fff; }
    .sr-pv-sheet { width:100%; border-collapse:collapse; font-size:12.5px; }
    .sr-pv-sheet th, .sr-pv-sheet td { border:1px solid var(--border); padding:6px 10px; text-align:left; white-space:nowrap; }
    .sr-pv-sheet tr:first-child td { background:var(--surface2); font-weight:700; }
    .sr-pv-fallback {
        text-align:center; padding:36px 20px; background:#fff; border:1px solid var(--border); border-radius:12px;
    }

    .sr-pv-loading { padding:34px 20px; text-align:center; color:var(--text-muted); font-size:13.5px; }
    .sr-pv-status { font-size:12px; color:var(--text-muted); margin-bottom:8px; }
    .sr-pv-notice {
        font-size:12.5px; color:#1e40af; background:#eff6ff; border:1px solid #bfdbfe;
        border-radius:9px; padding:9px 13px; margin-bottom:12px;
    }
    .sr-pv-notice.warn { color:#92400e; background:#fffbeb; border-color:#fde68a; margin-top:12px; margin-bottom:0; }

    .sr-pv-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
    .sr-pv-tab {
        border:1px solid var(--border); background:var(--surface2); color:var(--text-muted);
        border-radius:8px; padding:5px 12px; font-size:12.5px; font-weight:600; cursor:pointer;
        transition:background .15s, color .15s, border-color .15s;
    }
    .sr-pv-tab:hover { background:#fff; color:var(--text); }
    .sr-pv-tab.active { background:var(--accent); color:#fff; border-color:var(--accent); }

    /* Reader de documentos Word / ODT / DOC */
    .sr-pv-doc-tray {
        background:#f1efeb; border:1px solid var(--border); border-radius:12px;
        padding:18px; max-height:62vh; overflow-y:auto;
    }
    .sr-pv-doc {
        background:#fff; border:1px solid #e7e2da; border-radius:10px;
        padding:26px 32px; max-width:740px; margin:0 auto;
        box-shadow:0 2px 10px rgba(24,19,17,.06);
        color:var(--text); line-height:1.65; font-size:14px;
    }
    .sr-pv-doc h1, .sr-pv-doc h2, .sr-pv-doc h3 {
        font-family:'Bricolage Grotesque',sans-serif; color:var(--text);
        margin:16px 0 8px; line-height:1.25;
    }
    .sr-pv-doc h1 { font-size:21px; }
    .sr-pv-doc h2 { font-size:17.5px; }
    .sr-pv-doc h3 { font-size:15.5px; }
    .sr-pv-doc > :first-child { margin-top:0; }
    .sr-pv-doc p { margin:0 0 10px; white-space:pre-wrap; word-break:break-word; }
    .sr-pv-doc ul { margin:0 0 12px; padding-left:22px; }
    .sr-pv-doc li { margin-bottom:4px; white-space:pre-wrap; word-break:break-word; }
    .sr-pv-doc-img {
        max-width:100%; max-height:360px; display:block; margin:12px auto;
        border-radius:8px; border:1px solid var(--border);
    }
    .sr-pv-doc-table-wrap { overflow-x:auto; margin:12px 0; border:1px solid var(--border); border-radius:8px; }
    .sr-pv-doc-table { width:100%; border-collapse:collapse; font-size:12.5px; }
    .sr-pv-doc-table td {
        border:1px solid var(--border); padding:6px 10px; vertical-align:top;
        white-space:pre-wrap; word-break:break-word;
    }
    .sr-pv-doc-table tr:first-child td { background:var(--surface2); font-weight:700; }

    /* Diapositivas PPTX / ODP / PPT */
    .sr-pv-slides {
        display:flex; flex-direction:column; gap:14px;
        max-height:60vh; overflow-y:auto; padding:2px;
    }
    .sr-pv-slide {
        background:#fff; border:1px solid var(--border); border-radius:12px;
        overflow:hidden; box-shadow:0 2px 8px rgba(24,19,17,.05);
    }
    .sr-pv-slide-head {
        background:var(--surface2); border-bottom:1px solid var(--border);
        padding:10px 16px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    }
    .sr-pv-slide-num {
        font-size:11px; font-weight:700; padding:2px 9px; border-radius:99px;
        background:var(--accent); color:#fff; white-space:nowrap;
    }
    .sr-pv-slide-title {
        font-family:'Bricolage Grotesque',sans-serif; font-size:15.5px; font-weight:700;
        color:var(--text); word-break:break-word;
    }
    .sr-pv-slide-body { padding:16px 18px; display:flex; flex-direction:column; gap:8px; font-size:13.5px; color:var(--text); }
    .sr-pv-slide-item { position:relative; padding-left:14px; line-height:1.5; white-space:pre-wrap; word-break:break-word; }
    .sr-pv-slide-item::before {
        content:'•'; position:absolute; left:0; top:0; color:var(--accent); font-weight:700;
    }
    .sr-pv-slide-item.lvl-1 { margin-left:18px; }
    .sr-pv-slide-item.lvl-2 { margin-left:36px; }
    .sr-pv-slide-item.lvl-3 { margin-left:54px; }
    .sr-pv-slide-item.lvl-4 { margin-left:72px; }
    .sr-pv-slide-empty { font-size:12.5px; color:var(--text-muted); font-style:italic; }

    /* Archivo comprimido ZIP */
    .sr-pv-archive-wrap { overflow:auto; max-height:58vh; border:1px solid var(--border); border-radius:10px; background:#fff; }
    .sr-pv-archive { width:100%; border-collapse:collapse; font-size:12.5px; }
    .sr-pv-archive th, .sr-pv-archive td { border-bottom:1px solid var(--border); padding:8px 12px; text-align:left; }
    .sr-pv-archive th { background:var(--surface2); font-weight:700; color:var(--text); position:sticky; top:0; }
    .sr-pv-archive td.num, .sr-pv-archive th.num { text-align:right; white-space:nowrap; color:var(--text-muted); }

    @media (max-width:600px) {
        .sr-pv-doc { padding:16px; }
    }

    @media (max-width:600px) {
        .sr-grid { grid-template-columns:1fr; }
        .sr-search { width:100%; }
    }
</style>

{{-- ══════════════ MODAL VISTA PREVIA ══════════════ --}}
<div class="sr-backdrop" id="sr-preview-backdrop" onclick="if(event.target===this) srCerrarPreview()">
    <div class="sr-modal sr-modal-lg">
        <div class="sr-modal-head">
            <div style="min-width:0">
                <h3 id="sr-pv-titulo">Vista previa</h3>
                <div class="sr-pv-meta" style="margin:4px 0 0">
                    <span id="sr-pv-ext" class="sr-ext-pill"></span>
                    <span id="sr-pv-tamano"></span>
                    <span id="sr-pv-autor"></span>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px">
                <a id="sr-pv-download" href="#" class="btn btn-primary btn-sm" style="white-space:nowrap">📥 Descargar</a>
                <button type="button" class="sr-modal-close" onclick="srCerrarPreview()" aria-label="Cerrar">✕</button>
            </div>
        </div>
        <div class="sr-modal-body">
            <div id="sr-pv-desc" class="sr-pv-desc" style="display:none"></div>
            <div id="sr-pv-body"></div>
        </div>
    </div>
</div>

@if($esAdmin)
{{-- ══════════════ MODAL SUBIR RECURSO (SOLO ADMIN) ══════════════ --}}
<div class="sr-backdrop" id="sr-upload-backdrop" onclick="if(event.target===this) srCerrarUpload()">
    <div class="sr-modal">
        <div class="sr-modal-head">
            <h3 id="sr-upload-titulo">Subir archivo</h3>
            <button type="button" class="sr-modal-close" onclick="srCerrarUpload()" aria-label="Cerrar">✕</button>
        </div>
        <div class="sr-modal-body">
            <form method="POST" action="{{ route('seguimiento.recursos.store', $slug) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="categoria" id="sr-upload-categoria" value="{{ old('categoria', $categoriaDefault ?? 'manuales') }}">

                <div class="form-group">
                    <label class="form-label" for="sr-input-titulo">Nombre para mostrar *</label>
                    <input type="text" id="sr-input-titulo" name="titulo" class="form-control"
                           placeholder="Ej: Guía rápida de arranque 2026"
                           value="{{ old('titulo') }}" required>
                    <small style="color:var(--text-muted); font-size:11.5px">Este nombre se mostrará en la tarjeta en lugar del nombre técnico del archivo.</small>
                    @error('titulo')<div style="color:var(--danger); font-size:12px; margin-top:4px">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="sr-input-desc">Descripción <span style="font-weight:400; color:var(--text-muted)">(opcional)</span></label>
                    <textarea id="sr-input-desc" name="descripcion" class="form-control" rows="2"
                              placeholder="Breve nota sobre cuándo usar este archivo...">{{ old('descripcion') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Archivo (Excel, Word, PDF, PowerPoint, imagen...) *</label>
                    <label class="sr-dropzone" for="sr-input-archivo" style="display:block">
                        <input type="file" id="sr-input-archivo" name="archivo" required
                               accept=".pdf,.doc,.docx,.odt,.xls,.xlsx,.csv,.ods,.ppt,.pptx,.odp,.txt,.jpg,.jpeg,.png,.webp,.gif,.zip,.rar,.7z">
                        <div class="sr-dropzone-title" id="sr-archivo-label">📎 Seleccionar archivo desde tu equipo</div>
                        <div class="sr-dropzone-sub">Máximo 25 MB · PDF, XLSX, DOCX, PPTX, PNG, JPG, ZIP, etc.</div>
                    </label>
                    @error('archivo')<div style="color:var(--danger); font-size:12px; margin-top:4px">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">¿Para quién será visible y descargable? *</label>
                    <div class="sr-roles-grid" data-roles-group="upload">
                        @foreach(\App\Models\SeguimientoRecurso::AUDIENCIAS as $rolKey => $rolLabel)
                            @php $checked = in_array($rolKey, old('audiencia', ['todos']), true); @endphp
                            <label class="sr-role-opt {{ $checked ? 'checked' : '' }}">
                                <input type="checkbox" name="audiencia[]" value="{{ $rolKey }}" {{ $checked ? 'checked' : '' }}>
                                <span class="sr-chk-box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="sr-role-lbl">{{ $rolLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('audiencia')<div style="color:var(--danger); font-size:12px; margin-top:4px">{{ $message }}</div>@enderror
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px">
                    <button type="button" class="btn btn-secondary" onclick="srCerrarUpload()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar y publicar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════ MODAL EDITAR RECURSO (SOLO ADMIN) ══════════════ --}}
<div class="sr-backdrop" id="sr-edit-backdrop" onclick="if(event.target===this) srCerrarEditar()">
    <div class="sr-modal">
        <div class="sr-modal-head">
            <h3>✏️ Editar archivo</h3>
            <button type="button" class="sr-modal-close" onclick="srCerrarEditar()" aria-label="Cerrar">✕</button>
        </div>
        <div class="sr-modal-body">
            <form method="POST" id="sr-edit-form" action="#" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="form-group">
                    <label class="form-label" for="sr-edit-titulo">Nombre para mostrar *</label>
                    <input type="text" id="sr-edit-titulo" name="titulo" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sr-edit-desc">Descripción <span style="font-weight:400; color:var(--text-muted)">(opcional)</span></label>
                    <textarea id="sr-edit-desc" name="descripcion" class="form-control" rows="2"
                              placeholder="Breve nota sobre cuándo usar este archivo..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Reemplazar archivo <span style="font-weight:400; color:var(--text-muted)">(opcional)</span></label>
                    <label class="sr-dropzone" for="sr-edit-archivo" style="display:block">
                        <input type="file" id="sr-edit-archivo" name="archivo"
                               accept=".pdf,.doc,.docx,.odt,.xls,.xlsx,.csv,.ods,.ppt,.pptx,.odp,.txt,.jpg,.jpeg,.png,.webp,.gif,.zip,.rar,.7z">
                        <div class="sr-dropzone-title" id="sr-edit-archivo-label">📎 Seleccionar nuevo archivo</div>
                        <div class="sr-dropzone-sub" id="sr-edit-archivo-actual">Si no eliges uno nuevo, se conserva el archivo actual.</div>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">¿Para quién será visible y descargable? *</label>
                    <div class="sr-roles-grid" data-roles-group="edit">
                        @foreach(\App\Models\SeguimientoRecurso::AUDIENCIAS as $rolKey => $rolLabel)
                            <label class="sr-role-opt">
                                <input type="checkbox" name="audiencia[]" value="{{ $rolKey }}">
                                <span class="sr-chk-box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="sr-role-lbl">{{ $rolLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px">
                    <button type="button" class="btn btn-secondary" onclick="srCerrarEditar()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
    // Escapa texto antes de meterlo en innerHTML (títulos y nombres de archivo)
    function srEsc(t) {
        return String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

(function () {
    // Buscador en vivo por categoría
    document.querySelectorAll('[id^="sr-buscar-"]').forEach(input => {
        const cat = input.id.replace('sr-buscar-', '');
        const grid = document.getElementById('sr-grid-' + cat);
        const sinRes = document.getElementById('sr-sin-resultados-' + cat);
        if (!grid) return;

        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            let visibles = 0;
            grid.querySelectorAll('[data-sr-card]').forEach(card => {
                const ok = !q || (card.dataset.search || '').includes(q);
                card.style.display = ok ? '' : 'none';
                if (ok) visibles++;
            });
            if (sinRes) sinRes.style.display = visibles === 0 ? '' : 'none';
        });
    });

    // Checkboxes de roles ("Todos" excluye individuales y viceversa)
    document.querySelectorAll('[data-roles-group]').forEach(group => {
        const inputs = Array.from(group.querySelectorAll('input[type="checkbox"]'));
        const syncVisual = () => inputs.forEach(inp => inp.closest('.sr-role-opt').classList.toggle('checked', inp.checked));

        inputs.forEach(inp => {
            inp.addEventListener('change', () => {
                if (inp.value === 'todos' && inp.checked) {
                    inputs.forEach(o => { if (o.value !== 'todos') o.checked = false; });
                } else if (inp.value !== 'todos' && inp.checked) {
                    const todos = inputs.find(o => o.value === 'todos');
                    if (todos) todos.checked = false;
                }
                syncVisual();
            });
        });
        syncVisual();
    });

    // Nombre de archivo elegido en dropzones
    const fileInput = document.getElementById('sr-input-archivo');
    const fileLabel = document.getElementById('sr-archivo-label');
    fileInput?.addEventListener('change', () => {
        fileLabel.textContent = fileInput.files.length
            ? '✅ ' + fileInput.files[0].name
            : '📎 Seleccionar archivo desde tu equipo';
    });

    const editFileInput = document.getElementById('sr-edit-archivo');
    const editFileLabel = document.getElementById('sr-edit-archivo-label');
    editFileInput?.addEventListener('change', () => {
        editFileLabel.textContent = editFileInput.files.length
            ? '✅ Nuevo: ' + editFileInput.files[0].name
            : '📎 Seleccionar nuevo archivo';
    });

    // Modales Upload & Editar
    window.srAbrirUpload = (categoria, etiqueta) => {
        const catEl = document.getElementById('sr-upload-categoria');
        const titEl = document.getElementById('sr-upload-titulo');
        if (catEl) catEl.value = categoria;
        if (titEl) titEl.textContent = 'Subir ' + (etiqueta || 'archivo');
        document.getElementById('sr-upload-backdrop')?.classList.add('open');
        document.body.style.overflow = 'hidden';
    };
    window.srCerrarUpload = () => {
        document.getElementById('sr-upload-backdrop')?.classList.remove('open');
        document.body.style.overflow = '';
    };

    window.srAbrirEditar = btn => {
        const form = document.getElementById('sr-edit-form');
        if (!form) return;
        form.action = btn.dataset.url;
        document.getElementById('sr-edit-titulo').value = btn.dataset.titulo || '';
        document.getElementById('sr-edit-desc').value = btn.dataset.descripcion || '';
        document.getElementById('sr-edit-archivo').value = '';
        document.getElementById('sr-edit-archivo-label').textContent = '📎 Seleccionar nuevo archivo';
        document.getElementById('sr-edit-archivo-actual').textContent = btn.dataset.archivoNombre
            ? 'Actual: ' + btn.dataset.archivoNombre + ' (déjalo vacío para conservarlo)'
            : 'Si no eliges uno nuevo, se conserva el archivo actual.';

        const roles = (btn.dataset.audiencias || '').split(',').filter(Boolean);
        form.querySelectorAll('input[type="checkbox"]').forEach(inp => {
            inp.checked = roles.includes(inp.value);
            inp.closest('.sr-role-opt').classList.toggle('checked', inp.checked);
        });
        document.getElementById('sr-edit-backdrop')?.classList.add('open');
        document.body.style.overflow = 'hidden';
    };
    window.srCerrarEditar = () => {
        document.getElementById('sr-edit-backdrop')?.classList.remove('open');
        document.body.style.overflow = '';
    };

    window.srConfirmarEliminar = (formId, titulo) => {
        if (typeof confirmarEliminar === 'function') {
            confirmarEliminar('Eliminar archivo', '¿Deseas eliminar "' + titulo + '"? Esta acción no se puede deshacer.', formId);
        } else if (confirm('¿Deseas eliminar "' + titulo + '"?')) {
            document.getElementById(formId)?.submit();
        }
    };

    // Modal Vista Previa
    let srPreviewSeq = 0;

    function srIsSafeImageUri(src) {
        return typeof src === 'string' && /^data:image\/(png|jpeg|gif|webp);base64,[A-Za-z0-9+/=]+$/.test(src);
    }

    function srCreateNotice(msg, isWarn) {
        const div = document.createElement('div');
        div.className = 'sr-pv-notice' + (isWarn ? ' warn' : '');
        div.textContent = msg;
        return div;
    }

    function srCreateFallback(msg, downloadUrl, extraTitle, extraExt) {
        const box = document.createElement('div');
        box.className = 'sr-pv-fallback';
        const ico = document.createElement('div');
        ico.style.cssText = 'font-size:36px; margin-bottom:8px';
        ico.textContent = '📄';
        box.appendChild(ico);

        if (extraTitle) {
            const strong = document.createElement('div');
            strong.style.cssText = 'font-weight:700; margin-bottom:4px';
            strong.textContent = extraTitle;
            box.appendChild(strong);
        }

        const p = document.createElement('p');
        p.style.cssText = 'margin:0 0 16px; color:var(--text-muted); font-size:13px';
        p.textContent = msg || ('Los archivos ' + (extraExt || '') + ' no permiten vista previa directa. Descárgalo para abrirlo completo.');
        box.appendChild(p);

        const a = document.createElement('a');
        a.href = downloadUrl || '#';
        a.className = 'btn btn-primary';
        a.textContent = '📥 Descargar archivo';
        box.appendChild(a);
        return box;
    }

    function srCreateLoading(msg) {
        const div = document.createElement('div');
        div.className = 'sr-pv-loading';
        div.textContent = msg;
        return div;
    }

    function srRenderRuns(parent, runs, fallbackText) {
        if (!Array.isArray(runs) || !runs.length) {
            parent.textContent = fallbackText || '';
            return;
        }
        runs.forEach(run => {
            const txt = String(run.text ?? '');
            if (!txt) return;
            let el = document.createTextNode(txt);
            if (run.bold && run.italic) {
                const strong = document.createElement('strong');
                const em = document.createElement('em');
                em.textContent = txt;
                strong.appendChild(em);
                el = strong;
            } else if (run.bold) {
                const strong = document.createElement('strong');
                strong.textContent = txt;
                el = strong;
            } else if (run.italic) {
                const em = document.createElement('em');
                em.textContent = txt;
                el = em;
            }
            parent.appendChild(el);
        });
    }

    function srBuildTable(rows, wrapClass, tableClass) {
        const wrap = document.createElement('div');
        wrap.className = wrapClass;
        const table = document.createElement('table');
        table.className = tableClass;
        (rows || []).forEach(row => {
            const tr = document.createElement('tr');
            (row || []).forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell ?? '';
                tr.appendChild(td);
            });
            table.appendChild(tr);
        });
        wrap.appendChild(table);
        return wrap;
    }

    function srRenderSpreadsheetPreview(body, json, downloadUrl) {
        const sheets = Array.isArray(json.sheets) && json.sheets.length
            ? json.sheets
            : [{ title: json.sheetTitle || 'Principal', totalRows: json.totalRows || 0, rows: json.rows || [], truncatedRows: false }];

        let currentIndex = Number.isInteger(json.activeIndex) && sheets[json.activeIndex] ? json.activeIndex : 0;
        const status = document.createElement('div');
        status.className = 'sr-pv-status';
        const container = document.createElement('div');

        const drawSheet = idx => {
            currentIndex = idx;
            const sh = sheets[idx] || sheets[0];
            const shownRows = (sh.rows || []).length;
            const totalRows = sh.totalRows || shownRows;
            status.textContent = '';
            status.appendChild(document.createTextNode('Hoja: '));
            const stTitle = document.createElement('strong');
            stTitle.textContent = sh.title || 'Principal';
            status.appendChild(stTitle);
            status.appendChild(document.createTextNode(' · Mostrando ' + shownRows + ' de ' + totalRows + ' filas'));

            container.innerHTML = '';
            if (!shownRows) {
                const empty = document.createElement('div');
                empty.className = 'sr-pv-loading';
                empty.textContent = 'Esta hoja está vacía.';
                container.appendChild(empty);
            } else {
                container.appendChild(srBuildTable(sh.rows, 'sr-pv-sheet-wrap', 'sr-pv-sheet'));
            }
            if (sh.truncatedRows) {
                container.appendChild(srCreateNotice('Mostrando las primeras ' + shownRows + ' filas. Descarga el archivo para ver la hoja completa.', true));
            }
        };

        body.innerHTML = '';
        if (sheets.length > 1) {
            const tabs = document.createElement('div');
            tabs.className = 'sr-pv-tabs';
            sheets.forEach((sh, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'sr-pv-tab' + (idx === currentIndex ? ' active' : '');
                btn.textContent = sh.title || ('Hoja ' + (idx + 1));
                btn.addEventListener('click', () => {
                    tabs.querySelectorAll('.sr-pv-tab').forEach((t, i) => t.classList.toggle('active', i === idx));
                    drawSheet(idx);
                });
                tabs.appendChild(btn);
            });
            body.appendChild(tabs);
        }

        if (!sheets.some(s => (s.rows || []).length > 0)) {
            body.appendChild(srCreateFallback('Esta hoja de cálculo no contiene filas visibles para previsualizar.', downloadUrl));
            return;
        }

        body.appendChild(status);
        body.appendChild(container);
        drawSheet(currentIndex);
    }

    function srRenderDocumentPreview(body, json) {
        body.innerHTML = '';
        if (json.notice) {
            body.appendChild(srCreateNotice(json.notice, false));
        }

        const tray = document.createElement('div');
        tray.className = 'sr-pv-doc-tray';
        const doc = document.createElement('div');
        doc.className = 'sr-pv-doc';

        let currentUl = null;
        const flushList = () => {
            if (currentUl) {
                doc.appendChild(currentUl);
                currentUl = null;
            }
        };

        (json.blocks || []).forEach(block => {
            if (block.type === 'list_item') {
                if (!currentUl) currentUl = document.createElement('ul');
                const li = document.createElement('li');
                srRenderRuns(li, block.runs, block.text);
                currentUl.appendChild(li);
                return;
            }
            flushList();

            if (block.type === 'heading') {
                const lvl = Math.min(3, Math.max(1, parseInt(block.level || 1, 10)));
                const h = document.createElement('h' + lvl);
                srRenderRuns(h, block.runs, block.text);
                doc.appendChild(h);
            } else if (block.type === 'paragraph') {
                const p = document.createElement('p');
                srRenderRuns(p, block.runs, block.text);
                doc.appendChild(p);
            } else if (block.type === 'table' && Array.isArray(block.rows)) {
                doc.appendChild(srBuildTable(block.rows, 'sr-pv-doc-table-wrap', 'sr-pv-doc-table'));
            } else if (block.type === 'image' && srIsSafeImageUri(block.src)) {
                const img = document.createElement('img');
                img.src = block.src;
                img.alt = 'Imagen del documento';
                img.className = 'sr-pv-doc-img';
                doc.appendChild(img);
            }
        });
        flushList();

        tray.appendChild(doc);
        body.appendChild(tray);

        if (json.truncated) {
            body.appendChild(srCreateNotice('Este documento es extenso; mostrando los primeros bloques. Descárgalo para leerlo completo.', true));
        }
    }

    function srRenderSlidesPreview(body, json) {
        body.innerHTML = '';
        const slides = json.slides || [];
        const total = json.totalSlides || slides.length;

        const status = document.createElement('div');
        status.className = 'sr-pv-status';
        status.textContent = 'Presentación ' + (json.format || '') + ' · Mostrando ' + slides.length + ' de ' + total + (total === 1 ? ' diapositiva' : ' diapositivas');
        body.appendChild(status);

        if (json.notice) {
            body.appendChild(srCreateNotice(json.notice, false));
        }

        const list = document.createElement('div');
        list.className = 'sr-pv-slides';

        slides.forEach(slide => {
            const card = document.createElement('div');
            card.className = 'sr-pv-slide';

            const head = document.createElement('div');
            head.className = 'sr-pv-slide-head';
            const num = document.createElement('span');
            num.className = 'sr-pv-slide-num';
            num.textContent = 'Diapositiva ' + slide.number;
            head.appendChild(num);

            const title = document.createElement('div');
            title.className = 'sr-pv-slide-title';
            title.textContent = slide.title || 'Sin título';
            head.appendChild(title);
            card.appendChild(head);

            const sBody = document.createElement('div');
            sBody.className = 'sr-pv-slide-body';
            const hasContent = (slide.items && slide.items.length) || (slide.tables && slide.tables.length) || (slide.images && slide.images.length);

            if (!hasContent) {
                const empty = document.createElement('div');
                empty.className = 'sr-pv-slide-empty';
                empty.textContent = '(Diapositiva sin texto visible)';
                sBody.appendChild(empty);
            } else {
                (slide.items || []).forEach(item => {
                    const div = document.createElement('div');
                    const lvl = Math.min(4, Math.max(0, parseInt(item.level || 0, 10)));
                    div.className = 'sr-pv-slide-item' + (lvl > 0 ? ' lvl-' + lvl : '');
                    div.textContent = item.text || '';
                    sBody.appendChild(div);
                });
                (slide.tables || []).forEach(rows => {
                    sBody.appendChild(srBuildTable(rows, 'sr-pv-doc-table-wrap', 'sr-pv-doc-table'));
                });
                (slide.images || []).forEach(src => {
                    if (!srIsSafeImageUri(src)) return;
                    const img = document.createElement('img');
                    img.src = src;
                    img.alt = 'Imagen de diapositiva';
                    img.className = 'sr-pv-doc-img';
                    sBody.appendChild(img);
                });
            }

            card.appendChild(sBody);
            list.appendChild(card);
        });

        body.appendChild(list);
        if (json.truncated) {
            body.appendChild(srCreateNotice('Mostrando las primeras ' + slides.length + ' diapositivas. Descarga la presentación para ver todas.', true));
        }
    }

    function srRenderArchivePreview(body, json, downloadUrl, d) {
        body.innerHTML = '';
        if (json.unsupportedListing) {
            body.appendChild(srCreateFallback(json.message, downloadUrl, d.nombreArchivo || d.titulo, d.ext));
            return;
        }

        const status = document.createElement('div');
        status.className = 'sr-pv-status';
        status.textContent = 'Contenido del archivo ZIP · ' + (json.totalFiles ?? 0) + ' archivos (' + (json.totalSizeFormatted || '0 B') + ' sin comprimir)';
        body.appendChild(status);

        if (!(json.entries || []).length) {
            body.appendChild(srCreateFallback('Este archivo ZIP está vacío.', downloadUrl, d.nombreArchivo || d.titulo, d.ext));
            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'sr-pv-archive-wrap';
        const table = document.createElement('table');
        table.className = 'sr-pv-archive';

        const thead = document.createElement('thead');
        const headTr = document.createElement('tr');
        ['Elemento', 'Tipo', 'Tamaño'].forEach((label, idx) => {
            const th = document.createElement('th');
            if (idx === 2) th.className = 'num';
            th.textContent = label;
            headTr.appendChild(th);
        });
        thead.appendChild(headTr);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        (json.entries || []).forEach(entry => {
            const tr = document.createElement('tr');
            const tdName = document.createElement('td');
            tdName.textContent = (entry.isDir ? '📁 ' : '📄 ') + (entry.name || '');
            const tdExt = document.createElement('td');
            tdExt.textContent = entry.ext || '';
            const tdSize = document.createElement('td');
            tdSize.className = 'num';
            tdSize.textContent = entry.sizeFormatted || '';
            tr.appendChild(tdName);
            tr.appendChild(tdExt);
            tr.appendChild(tdSize);
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        wrap.appendChild(table);
        body.appendChild(wrap);

        if (json.truncated) {
            body.appendChild(srCreateNotice('Mostrando los primeros elementos del ZIP de un total de ' + json.totalEntries + '. Descarga el archivo para verlo completo.', true));
        }
    }

    window.srAbrirPreview = btn => {
        const d = btn.dataset;
        const seq = ++srPreviewSeq;

        document.getElementById('sr-pv-titulo').textContent = d.titulo || 'Vista previa';
        const extEl = document.getElementById('sr-pv-ext');
        extEl.textContent = d.ext || '';
        extEl.style.color = d.color || '';
        extEl.style.background = d.bg || '';
        document.getElementById('sr-pv-tamano').textContent = d.tamano ? '· ' + d.tamano : '';
        document.getElementById('sr-pv-autor').textContent = d.autor ? '· Subido por ' + d.autor + ' (' + d.fecha + ')' : '';
        document.getElementById('sr-pv-download').href = d.downloadUrl || '#';

        const descEl = document.getElementById('sr-pv-desc');
        if (d.descripcion) {
            descEl.textContent = d.descripcion;
            descEl.style.display = '';
        } else {
            descEl.style.display = 'none';
        }

        const body = document.getElementById('sr-pv-body');
        body.innerHTML = '';

        const asyncModes = {
            excel: { mode: 'sheet', msg: 'Cargando vista previa de hoja de cálculo...', render: j => srRenderSpreadsheetPreview(body, j, d.downloadUrl) },
            word: { mode: 'doc', msg: 'Cargando vista previa de documento...', render: j => srRenderDocumentPreview(body, j) },
            ppt: { mode: 'slides', msg: 'Cargando vista previa de presentación...', render: j => srRenderSlidesPreview(body, j) },
            archive: { mode: 'archive', msg: 'Leyendo contenido del archivo comprimido...', render: j => srRenderArchivePreview(body, j, d.downloadUrl, d) },
        };

        if (d.tipo === 'image') {
            const img = document.createElement('img');
            img.src = d.previewUrl;
            img.alt = d.titulo;
            img.className = 'sr-pv-img';
            body.appendChild(img);
        } else if (d.tipo === 'pdf' || d.tipo === 'text') {
            const iframe = document.createElement('iframe');
            iframe.src = d.previewUrl;
            iframe.className = 'sr-pv-frame';
            iframe.title = d.titulo;
            body.appendChild(iframe);
        } else if (asyncModes[d.tipo]) {
            const cfg = asyncModes[d.tipo];
            body.appendChild(srCreateLoading(cfg.msg));
            fetch(d.previewUrl + '?mode=' + cfg.mode, { headers: { Accept: 'application/json' } })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(json => {
                    if (seq !== srPreviewSeq) return;
                    if (!json || !json.ok) {
                        body.innerHTML = '';
                        body.appendChild(srCreateFallback((json && json.message) || 'Vista previa no disponible.', d.downloadUrl, d.nombreArchivo || d.titulo, d.ext));
                        return;
                    }
                    cfg.render(json);
                })
                .catch(() => {
                    if (seq !== srPreviewSeq) return;
                    body.innerHTML = '';
                    body.appendChild(srCreateFallback('No se pudo cargar la vista previa.', d.downloadUrl, d.nombreArchivo || d.titulo, d.ext));
                });
        } else {
            body.appendChild(srCreateFallback('', d.downloadUrl, d.nombreArchivo || d.titulo, d.ext));
        }

        document.getElementById('sr-preview-backdrop').classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.srCerrarPreview = () => {
        srPreviewSeq++;
        document.getElementById('sr-preview-backdrop')?.classList.remove('open');
        const body = document.getElementById('sr-pv-body');
        if (body) body.innerHTML = '';
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            srCerrarPreview();
            srCerrarUpload();
            srCerrarEditar();
        }
    });

    @if($esAdmin && ($errors->has('titulo') || $errors->has('archivo') || $errors->has('audiencia')))
        @php $oldCat = old('categoria', $categoriaDefault ?? 'manuales'); @endphp
        srAbrirUpload(@json($oldCat), @json($oldCat === 'formatos' ? 'formato' : 'manual'));
    @endif
})();
</script>
