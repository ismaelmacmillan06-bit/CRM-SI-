@extends('layouts.app')

@section('title', 'Tablero SI')

@section('content')
<style>
    /* ── Tabs ── */
    .tabs { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:28px; }
    .tab-btn {
        padding:10px 28px; font-size:13px; font-weight:600; letter-spacing:.6px;
        text-transform:uppercase; border:none; background:none; cursor:pointer;
        color:var(--text-muted); border-bottom:2px solid transparent; margin-bottom:-2px;
        transition:color .18s, border-color .18s; font-family:'Bricolage Grotesque',sans-serif;
    }
    .tab-btn.active { color:var(--accent); border-bottom-color:var(--accent); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }

    /* ── Cards de comunicado ── */
    .comunicado-grid {
        display:grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap:20px;
    }
    .comunicado-card {
        background:var(--surface);
        border:1px solid var(--border);
        border-radius:14px;
        padding:22px;
        display:flex; flex-direction:column; gap:14px;
        box-shadow:0 1px 4px rgba(24,19,17,.05);
        transition:box-shadow .2s;
        position:relative;
    }
    .comunicado-card:hover { box-shadow:0 4px 16px rgba(24,19,17,.09); }
    .comunicado-card.pasado { opacity:.72; }

    .comunicado-titulo {
        font-family:'Bricolage Grotesque',sans-serif;
        font-size:17px; font-weight:700; color:var(--text);
        line-height:1.25;
    }
    .comunicado-desc {
        font-size:14px; color:#4A4540; line-height:1.6;
        white-space:pre-line;
    }

    /* adjunto */
    .adjunto-link {
        display:inline-flex; align-items:center; gap:7px;
        padding:8px 14px; border-radius:9px;
        background:var(--surface2); border:1px solid var(--border);
        font-size:13px; font-weight:500; color:var(--text); text-decoration:none;
        transition:background .15s;
        max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }
    .adjunto-link:hover { background:var(--border); }
    .adjunto-link svg { flex-shrink:0; }

    /* adjunto imagen inline */
    .adjunto-img {
        width:100%; max-height:180px; object-fit:cover;
        border-radius:9px; border:1px solid var(--border);
    }

    /* footer de la card — margin-top:auto lo empuja siempre al fondo */
    .comunicado-footer {
        display:flex; align-items:center; justify-content:space-between;
        flex-wrap:wrap; gap:8px;
        font-size:12px; color:var(--text-muted);
        padding-top:10px; border-top:1px solid var(--border);
        margin-top:auto;
    }
    .badge-expira {
        display:inline-flex; align-items:center; gap:5px;
        padding:3px 10px; border-radius:20px;
        font-size:11px; font-weight:600;
        background:#fef3c7; color:#92400e;
    }
    .badge-sin-expira {
        display:inline-flex; align-items:center; gap:5px;
        padding:3px 10px; border-radius:20px;
        font-size:11px; font-weight:600;
        background:#d1fae5; color:#065f46;
    }
    .badge-vencido {
        display:inline-flex; align-items:center; gap:5px;
        padding:3px 10px; border-radius:20px;
        font-size:11px; font-weight:600;
        background:#f3f4f6; color:#6b7280;
    }

    .btn-eliminar {
        display:inline-flex; align-items:center; gap:5px;
        padding:5px 11px; border-radius:7px;
        font-size:12px; font-weight:500; cursor:pointer;
        background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;
        transition:background .15s;
    }
    .btn-eliminar:hover { background:#b91c1c; color:#fff; }

    /* ── Botón ojo ── */
    .btn-ver-comunicado {
        position:absolute; top:14px; right:14px;
        width:30px; height:30px; border-radius:8px;
        border:1px solid var(--border); background:var(--surface2);
        display:grid; place-items:center; cursor:pointer;
        color:var(--text-muted); transition:background .15s, color .15s, border-color .15s;
    }
    .btn-ver-comunicado:hover { background:var(--accent); color:#fff; border-color:var(--accent); }

    /* ── Modal viewer ── */
    .viewer-backdrop {
        display:none; position:fixed; inset:0;
        background:rgba(24,19,17,.6); z-index:600;
        backdrop-filter:blur(4px);
        align-items:center; justify-content:center;
        padding:20px;
    }
    .viewer-backdrop.open { display:flex; }
    .viewer-modal {
        background:var(--surface); border-radius:18px;
        width:100%; max-width:620px; max-height:90vh;
        overflow-y:auto; padding:36px;
        box-shadow:0 32px 80px -16px rgba(24,19,17,.38);
        position:relative;
        animation: viewerIn .18s ease;
    }
    @keyframes viewerIn {
        from { opacity:0; transform:scale(.96) translateY(8px); }
        to   { opacity:1; transform:scale(1)  translateY(0); }
    }
    .viewer-close {
        position:absolute; top:16px; right:16px;
        width:32px; height:32px; border-radius:8px;
        border:none; background:var(--surface2); cursor:pointer;
        display:grid; place-items:center; color:var(--text-muted);
        transition:background .15s;
    }
    .viewer-close:hover { background:var(--border); color:var(--text); }
    .viewer-titulo {
        font-family:'Bricolage Grotesque',sans-serif;
        font-size:22px; font-weight:700; color:var(--text);
        line-height:1.25; margin-bottom:16px; padding-right:36px;
    }
    .viewer-desc {
        font-size:15px; color:var(--text); line-height:1.7;
        white-space:pre-line; margin-bottom:20px;
    }
    .viewer-meta {
        font-size:12px; color:var(--text-muted);
        padding-top:16px; border-top:1px solid var(--border);
        margin-top:8px;
    }

    /* ── Empty state ── */
    .empty-state {
        text-align:center; padding:60px 20px; color:var(--text-muted);
    }
    .empty-state svg { opacity:.3; margin:0 auto 14px; display:block; }
    .empty-state p { font-size:15px; }

    /* ── Modal ── */
    .modal-backdrop {
        display:none; position:fixed; inset:0;
        background:rgba(24,19,17,.55); z-index:500;
        backdrop-filter:blur(3px);
        align-items:center; justify-content:center;
    }
    .modal-backdrop.open { display:flex; }
    .modal {
        background:var(--surface); border-radius:16px;
        width:100%; max-width:520px; max-height:90vh;
        overflow-y:auto; padding:32px;
        box-shadow:0 24px 64px -12px rgba(24,19,17,.32);
        position:relative;
    }
    .modal-title {
        font-family:'Bricolage Grotesque',sans-serif;
        font-size:22px; font-weight:700; color:var(--text);
        margin-bottom:22px;
    }
    .modal-close {
        position:absolute; top:18px; right:18px;
        width:32px; height:32px; border-radius:8px;
        border:none; background:var(--surface2); cursor:pointer;
        display:grid; place-items:center; color:var(--text-muted);
        transition:background .15s;
    }
    .modal-close:hover { background:var(--border); color:var(--text); }

    /* Upload zone */
    .upload-zone {
        border:1.5px dashed var(--border); border-radius:10px;
        padding:20px; text-align:center; cursor:pointer;
        transition:border-color .18s, background .18s;
        color:var(--text-muted); font-size:13px;
    }
    .upload-zone:hover { border-color:var(--accent); background:rgba(226,35,26,.04); }
    .upload-zone input { display:none; }
    .upload-zone svg { margin:0 auto 8px; display:block; opacity:.45; }
    #upload-label { color:var(--accent); font-weight:600; cursor:pointer; }

    @media(max-width:600px){
        .modal { padding:22px 16px; }
        .comunicado-grid { grid-template-columns:1fr; }
    }
</style>

{{-- Header --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px">
    <div>
        <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
            📌 Tablero SI
        </h2>
        <p style="font-size:13px; color:var(--text-muted); margin-top:4px">
            Comunicados y avisos para el equipo
        </p>
    </div>
    @if(auth()->user()->hasRole('admin'))
        <button onclick="abrirModal()" class="btn btn-primary" style="gap:8px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo Comunicado
        </button>
    @endif
</div>

{{-- Tabs --}}
<div class="tabs">
    <button class="tab-btn active" onclick="switchTab('activos', this)">
        Activos
        <span style="margin-left:6px; background:var(--accent); color:#fff; border-radius:99px; padding:1px 8px; font-size:11px">{{ $activos->count() }}</span>
    </button>
    <button class="tab-btn" onclick="switchTab('pasados', this)">
        Pasados
        <span style="margin-left:6px; background:var(--surface2); color:var(--text-muted); border-radius:99px; padding:1px 8px; font-size:11px; border:1px solid var(--border)">{{ $pasados->count() }}</span>
    </button>
</div>

{{-- ── PANEL ACTIVOS ── --}}
<div id="tab-activos" class="tab-panel active">
    @if($activos->isEmpty())
        <div class="empty-state">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>
            <p>No hay comunicados activos.</p>
            @if(auth()->user()->hasRole('admin'))
                <button onclick="abrirModal()" style="margin-top:14px; background:var(--accent); color:#fff; border:none; border-radius:9px; padding:10px 22px; font-size:14px; font-weight:600; cursor:pointer">
                    Publicar el primero
                </button>
            @endif
        </div>
    @else
        <div class="comunicado-grid">
            @foreach($activos as $c)
                @include('tablero._card', ['comunicado' => $c, 'tipo' => 'activo'])
            @endforeach
        </div>
    @endif
</div>

{{-- ── PANEL PASADOS ── --}}
<div id="tab-pasados" class="tab-panel">
    @if($pasados->isEmpty())
        <div class="empty-state">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
            <p>No hay comunicados pasados todavía.</p>
        </div>
    @else
        <div class="comunicado-grid">
            @foreach($pasados as $c)
                @include('tablero._card', ['comunicado' => $c, 'tipo' => 'pasado'])
            @endforeach
        </div>
    @endif
</div>

{{-- ══════════════ MODAL VIEWER ══════════════ --}}
<div class="viewer-backdrop" id="viewerBackdrop" onclick="cerrarViewerFuera(event)">
    <div class="viewer-modal" id="viewerModal">
        <button class="viewer-close" onclick="cerrarViewer()" aria-label="Cerrar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="viewer-titulo" id="viewerTitulo"></div>
        <div class="viewer-desc" id="viewerDesc"></div>
        <div id="viewerEnlace"></div>
        <div id="viewerArchivo" style="margin-top:12px"></div>
        <div class="viewer-meta" id="viewerMeta"></div>
    </div>
</div>

{{-- ══════════════ MODAL NUEVO COMUNICADO ══════════════ --}}
@if(auth()->user()->hasRole('admin'))
<div class="modal-backdrop" id="modalBackdrop" onclick="cerrarModalFuera(event)">
    <div class="modal" id="modalBox">
        <button class="modal-close" onclick="cerrarModal()" aria-label="Cerrar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div class="modal-title">Nuevo Comunicado</div>

        <form method="POST" action="{{ route('tablero.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" name="titulo" class="form-control"
                       placeholder="Ej: Actualización de bundles Q3"
                       value="{{ old('titulo') }}" required>
                @error('titulo')<small style="color:var(--danger)">{{ $message }}</small>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Descripción *</label>
                <textarea name="descripcion" class="form-control" rows="5"
                          placeholder="Escribe el contenido del comunicado..."
                          style="resize:vertical" required>{{ old('descripcion') }}</textarea>
                @error('descripcion')<small style="color:var(--danger)">{{ $message }}</small>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Adjuntar archivo <small style="font-weight:400; color:var(--text-muted)">(PDF, JPG o PNG — máx. 5 MB)</small></label>
                <div class="upload-zone" onclick="document.getElementById('archivoInput').click()">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17,8 12,3 7,8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <p><label id="upload-label">Seleccionar archivo</label> o arrastra aquí</p>
                    <p id="upload-name" style="margin-top:4px; font-size:12px; color:var(--accent)"></p>
                    <input type="file" name="archivo" id="archivoInput"
                           accept=".pdf,.jpg,.jpeg,.png"
                           onchange="mostrarNombre(this)">
                </div>
                @error('archivo')<small style="color:var(--danger)">{{ $message }}</small>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Enlace <small style="font-weight:400; color:var(--text-muted)">(opcional — agrega un botón de acceso directo)</small></label>
                <input type="url" name="enlace" class="form-control"
                       placeholder="https://sharepoint.com/..."
                       value="{{ old('enlace') }}">
                @error('enlace')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                <input type="text" name="enlace_texto" class="form-control" style="margin-top:8px"
                       placeholder="Texto del botón (ej: Ver lista ELT) — opcional"
                       maxlength="100"
                       value="{{ old('enlace_texto') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Fecha de término <small style="font-weight:400; color:var(--text-muted)">(opcional — sin fecha permanece activo)</small></label>
                <input type="date" name="fecha_termino" class="form-control"
                       min="{{ now()->addDay()->format('Y-m-d') }}"
                       value="{{ old('fecha_termino') }}">
                @error('fecha_termino')<small style="color:var(--danger)">{{ $message }}</small>@enderror
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px">
                <button type="button" onclick="cerrarModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Publicar</button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
    // ── Tabs ──
    function switchTab(id, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + id).classList.add('active');
        btn.classList.add('active');
    }

    // ── Modal ──
    function abrirModal() {
        document.getElementById('modalBackdrop').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function cerrarModal() {
        document.getElementById('modalBackdrop').classList.remove('open');
        document.body.style.overflow = '';
    }
    function cerrarModalFuera(e) {
        if (e.target === document.getElementById('modalBackdrop')) cerrarModal();
    }
    // Escape manejado en el viewer

    // ── Viewer de comunicado ──
    const svgLink = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';
    const svgPdf  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>';

    function verComunicado(btn) {
        document.getElementById('viewerTitulo').textContent = btn.dataset.titulo;
        document.getElementById('viewerDesc').textContent   = btn.dataset.descripcion;
        document.getElementById('viewerMeta').textContent   = '✏️ ' + btn.dataset.autor + '  ·  ' + btn.dataset.fecha;

        // Enlace — construcción segura con DOM (evita XSS en enlace_texto)
        const enlaceBox = document.getElementById('viewerEnlace');
        enlaceBox.innerHTML = '';
        if (btn.dataset.enlace) {
            const a = document.createElement('a');
            a.href   = btn.dataset.enlace;
            a.target = '_blank';
            a.rel    = 'noopener noreferrer';
            a.style.cssText = 'display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:10px;background:var(--accent);color:#fff;font-size:14px;font-weight:600;text-decoration:none;margin-bottom:16px';
            a.innerHTML = svgLink; // SVG estático, no datos de usuario
            a.appendChild(document.createTextNode(btn.dataset.enlaceTexto || 'Abrir enlace'));
            enlaceBox.appendChild(a);
        }

        // Archivo — construcción segura con DOM (evita XSS en archivo_nombre)
        const archivoBox = document.getElementById('viewerArchivo');
        archivoBox.innerHTML = '';
        if (btn.dataset.archivoUrl) {
            archivoBox.style.marginTop = '12px';
            if (btn.dataset.archivoTipo === 'image') {
                const img = document.createElement('img');
                img.src   = btn.dataset.archivoUrl;
                img.alt   = btn.dataset.archivoNombre;
                img.style.cssText = 'width:100%;border-radius:12px;border:1px solid var(--border)';
                archivoBox.appendChild(img);
            } else {
                const a = document.createElement('a');
                a.href   = btn.dataset.archivoUrl;
                a.target = '_blank';
                a.style.cssText = 'display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:10px;background:var(--surface2);border:1px solid var(--border);color:var(--text);font-size:13px;font-weight:500;text-decoration:none';
                a.innerHTML = svgPdf; // SVG estático
                a.appendChild(document.createTextNode(btn.dataset.archivoNombre));
                archivoBox.appendChild(a);
            }
        }

        document.getElementById('viewerBackdrop').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function cerrarViewer() {
        document.getElementById('viewerBackdrop').classList.remove('open');
        document.body.style.overflow = '';
    }
    function cerrarViewerFuera(e) {
        if (e.target === document.getElementById('viewerBackdrop')) cerrarViewer();
    }
    // Escape: modalBackdrop solo existe para admin — null-safe
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            const mb = document.getElementById('modalBackdrop');
            if (mb) { mb.classList.remove('open'); document.body.style.overflow = ''; }
            cerrarViewer();
        }
    });

    // ── Upload label ──
    function mostrarNombre(input) {
        const label = document.getElementById('upload-name');
        label.textContent = input.files[0] ? '📎 ' + input.files[0].name : '';
    }

    // ── Reabrir modal si hay errores de validación ──
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', abrirModal);
    @endif
</script>
@endsection
