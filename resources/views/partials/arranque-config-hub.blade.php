{{-- Hub global exclusivo de Administrador para configurar Acciones de Arranque (Colegios Globales y Seguimiento por Plataforma) --}}
@php
    $hubPlatforms = \App\Http\Controllers\SeguimientoController::PLATAFORMAS;
    $hubGlobalItems = ($arranqueGlobalProcesses ?? collect())->map(fn ($p) => [
        'id'    => (int) $p->id,
        'text'  => (string) $p->name,
        'order' => (int) $p->order,
    ])->values()->all();

    $hubSegPayload = [];
    $hubSegTotal = 0;
    foreach ($hubPlatforms as $pSlug => $pMeta) {
        $rows = ($arranqueSeguimientoCatalogo[$pSlug] ?? collect())->map(fn ($a) => [
            'id'    => (int) $a->id,
            'text'  => (string) $a->etiqueta,
            'order' => (int) $a->orden,
        ])->values()->all();
        $hubSegTotal += count($rows);
        $hubSegPayload[$pSlug] = [
            'slug'   => $pSlug,
            'nombre' => $pMeta['nombre'],
            'icono'  => $pMeta['icono'],
            'items'  => $rows,
            'routes' => [
                'store'   => route('seguimiento.acciones.store', $pSlug),
                'reorder' => route('seguimiento.acciones.reorder', $pSlug),
                'update'  => route('seguimiento.acciones.update', [$pSlug, '__ID__']),
                'destroy' => route('seguimiento.acciones.destroy', [$pSlug, '__ID__']),
            ],
        ];
    }
    $hubGlobalRoutes = [
        'store'   => route('processes.catalog.store'),
        'reorder' => route('processes.catalog.reorder'),
        'update'  => route('processes.catalog.update', '__ID__'),
        'destroy' => route('processes.catalog.destroy', '__ID__'),
    ];
@endphp

<style>
    .hub-arr-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2650;
        background: rgba(24, 19, 17, 0.58);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .hub-arr-backdrop.open { display: flex; }
    .hub-arr-modal {
        background: var(--surface, #FBFAF7);
        border: 1px solid var(--border, #E4E0D8);
        border-radius: 16px;
        width: min(860px, 100%);
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 28px 72px rgba(24, 19, 17, 0.36);
        animation: hubArrIn 0.2s cubic-bezier(.22, 1, .36, 1);
    }
    @keyframes hubArrIn {
        from { opacity: 0; transform: scale(0.97) translateY(8px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .hub-arr-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 18px 22px;
        background: linear-gradient(120deg, var(--primary, #181311) 0%, var(--secondary, #221A17) 100%);
        color: #ffffff;
        border-bottom: 2px solid var(--accent, #E2231A);
    }
    .hub-arr-head-left {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }
    .hub-arr-back {
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border-radius: 8px;
        padding: 6px 11px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        flex-shrink: 0;
        margin-top: 2px;
        transition: background 0.15s;
    }
    .hub-arr-back:hover { background: rgba(255, 255, 255, 0.2); }
    .hub-arr-title {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
    }
    .hub-arr-sub {
        font-size: 12.5px;
        color: rgba(255, 255, 255, 0.72);
        margin: 4px 0 0;
        line-height: 1.45;
    }
    .hub-arr-close {
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border-radius: 8px;
        width: 34px;
        height: 34px;
        cursor: pointer;
        font-size: 15px;
        font-weight: 700;
        flex-shrink: 0;
        transition: background 0.15s, border-color 0.15s;
    }
    .hub-arr-close:hover { background: var(--accent, #E2231A); border-color: var(--accent, #E2231A); }
    .hub-arr-body {
        padding: 20px 22px 22px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 16px;
        background: var(--surface, #FBFAF7);
    }

    /* Tarjetas de selección Home & Plataformas */
    .hub-arr-choice-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
    .hub-arr-plat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }
    @media (max-width: 640px) {
        .hub-arr-choice-grid { grid-template-columns: 1fr; }
    }
    .hub-arr-card {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding: 18px;
        border-radius: 13px;
        border: 1.5px solid var(--border, #E4E0D8);
        background: #ffffff;
        text-align: left;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    }
    .hub-arr-card:hover {
        border-color: var(--accent, #E2231A);
        box-shadow: 0 10px 24px rgba(24, 19, 17, 0.08);
        transform: translateY(-2px);
    }
    .hub-arr-card-top {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .hub-arr-card-ico {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        background: var(--surface2, #F2EFE9);
        border: 1px solid var(--border, #E4E0D8);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 21px;
        flex-shrink: 0;
    }
    .hub-arr-card-badge {
        font-size: 11.5px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--surface2, #F2EFE9);
        color: var(--text, #181311);
        border: 1px solid var(--border, #E4E0D8);
    }
    .hub-arr-card-badge.is-zero {
        background: #fffbeb;
        color: #b45309;
        border-color: #fde68a;
    }
    .hub-arr-card-title {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 15.5px;
        font-weight: 700;
        color: var(--text, #181311);
    }
    .hub-arr-card-desc {
        font-size: 12.5px;
        color: var(--text-muted, #6F6A64);
        line-height: 1.45;
    }
    .hub-arr-card-cta {
        margin-top: auto;
        font-size: 12px;
        font-weight: 700;
        color: var(--accent, #E2231A);
    }

    /* Editor arrastrable con guardado en vivo */
    .hub-arr-add {
        background: var(--surface2, #F2EFE9);
        border: 1px solid var(--border, #E4E0D8);
        border-radius: 12px;
        padding: 14px 16px;
    }
    .hub-arr-add-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text, #181311);
        margin-bottom: 9px;
    }
    .hub-arr-form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }
    .hub-arr-input {
        width: 100%;
        padding: 9px 11px;
        border: 1px solid var(--border, #E4E0D8);
        border-radius: 8px;
        font-size: 13.5px;
        background: #ffffff;
        color: var(--text, #181311);
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .hub-arr-input:focus {
        outline: none;
        border-color: var(--accent, #E2231A);
        box-shadow: 0 0 0 3px rgba(226, 35, 26, 0.12);
    }
    .hub-arr-list-hdr {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }
    .hub-arr-status {
        font-size: 12px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--surface2, #F2EFE9);
        color: var(--text-muted, #6F6A64);
        border: 1px solid var(--border, #E4E0D8);
    }
    .hub-arr-status.is-saving { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .hub-arr-status.is-saved  { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .hub-arr-status.is-error  { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

    .hub-arr-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .hub-arr-item {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        padding: 9px 12px;
        border: 1px solid var(--border, #E4E0D8);
        border-radius: 11px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(24, 19, 17, 0.04);
        transition: border-color 0.15s, box-shadow 0.15s, opacity 0.15s;
    }
    .hub-arr-item:hover { border-color: #d6cfc3; box-shadow: 0 4px 12px rgba(24, 19, 17, 0.07); }
    .hub-arr-item.is-dragging { opacity: 0.45; border-style: dashed; border-color: var(--accent, #E2231A); background: #fff8f7; }
    .hub-arr-item.is-drag-over { border-color: var(--accent, #E2231A); box-shadow: 0 0 0 2px rgba(226, 35, 26, 0.18); }
    .hub-arr-handle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 34px;
        border-radius: 7px;
        color: var(--text-muted, #6F6A64);
        background: var(--surface2, #F2EFE9);
        border: 1px solid var(--border, #E4E0D8);
        cursor: grab;
        user-select: none;
        font-size: 14px;
        letter-spacing: -1px;
        flex-shrink: 0;
    }
    .hub-arr-handle:active { cursor: grabbing; }
    .hub-arr-pos {
        min-width: 34px;
        height: 28px;
        padding: 0 7px;
        border-radius: 999px;
        background: var(--surface2, #F2EFE9);
        border: 1px solid var(--border, #E4E0D8);
        color: var(--text, #181311);
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .hub-arr-move-btns { display: inline-flex; flex-direction: column; gap: 2px; flex-shrink: 0; }
    .hub-arr-move-btn {
        width: 22px;
        height: 16px;
        border: 1px solid var(--border, #E4E0D8);
        background: var(--surface, #FBFAF7);
        color: var(--text-muted, #6F6A64);
        border-radius: 4px;
        font-size: 9px;
        line-height: 1;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    .hub-arr-move-btn:hover:not(:disabled) { border-color: var(--accent, #E2231A); color: var(--accent, #E2231A); background: #fff8f7; }
    .hub-arr-move-btn:disabled { opacity: 0.35; cursor: default; }
    .hub-arr-del-btn {
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #b91c1c;
        border-radius: 8px;
        padding: 7px 11px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        flex-shrink: 0;
    }
    .hub-arr-del-btn:hover { background: #fee2e2; }
    .hub-arr-empty {
        text-align: center;
        padding: 26px 16px;
        border: 1px dashed var(--border, #E4E0D8);
        border-radius: 10px;
        background: #ffffff;
        color: var(--text-muted, #6F6A64);
        font-size: 13.5px;
    }
    .hub-arr-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 12px 22px;
        border-top: 1px solid var(--border, #E4E0D8);
        background: var(--surface2, #F2EFE9);
        font-size: 12px;
        color: var(--text-muted, #6F6A64);
    }
</style>

<div id="sic-arranque-hub-backdrop" class="hub-arr-backdrop" role="dialog" aria-modal="true" aria-labelledby="hub-arr-title">
    <div class="hub-arr-modal">
        <div class="hub-arr-head">
            <div class="hub-arr-head-left">
                <button type="button" id="hub-arr-back-btn" class="hub-arr-back" style="display:none">← Atrás</button>
                <div>
                    <h3 id="hub-arr-title" class="hub-arr-title">🚀 Configurar acciones de arranque</h3>
                    <p id="hub-arr-sub" class="hub-arr-sub">Elige si deseas administrar el catálogo global de colegios o las acciones de Seguimiento por plataforma.</p>
                </div>
            </div>
            <button type="button" id="hub-arr-close-btn" class="hub-arr-close" aria-label="Cerrar">✕</button>
        </div>

        <div class="hub-arr-body">
            {{-- Vista 1: Inicio (2 opciones principales) --}}
            <div id="hub-view-home">
                <div class="hub-arr-choice-grid">
                    <button type="button" class="hub-arr-card" onclick="window.abrirHubArranque('colegios')">
                        <div class="hub-arr-card-top">
                            <span class="hub-arr-card-ico">🏫</span>
                            <span class="hub-arr-card-badge" data-hub-count="colegios">{{ count($hubGlobalItems) }} acciones</span>
                        </div>
                        <div class="hub-arr-card-title">Acciones de arranque globales (Colegios)</div>
                        <div class="hub-arr-card-desc">
                            Configura los pasos de arranque que aplican a los niveles escolares de todos los colegios en el CRM.
                        </div>
                        <div class="hub-arr-card-cta">Administrar acciones de colegios →</div>
                    </button>

                    <button type="button" class="hub-arr-card" onclick="window.abrirHubArranque('seguimiento')">
                        <div class="hub-arr-card-top">
                            <span class="hub-arr-card-ico">🧭</span>
                            <span class="hub-arr-card-badge" data-hub-count="seg-total">{{ $hubSegTotal }} acciones</span>
                        </div>
                        <div class="hub-arr-card-title">Acciones de arranque de Seguimiento</div>
                        <div class="hub-arr-card-desc">
                            Administra de forma independiente el checklist de Meta, Global Educa, Francés, Red Roo Reads y Equipamiento.
                        </div>
                        <div class="hub-arr-card-cta">Elegir plataforma de Seguimiento →</div>
                    </button>
                </div>
            </div>

            {{-- Vista 2: Menú de plataformas de Seguimiento --}}
            <div id="hub-view-platforms" style="display:none">
                <div class="hub-arr-plat-grid">
                    @foreach($hubSegPayload as $pSlug => $pData)
                        @php $pCount = count($pData['items']); @endphp
                        <button type="button" class="hub-arr-card" onclick="window.abrirHubArranque('seguimiento', '{{ $pSlug }}')">
                            <div class="hub-arr-card-top">
                                <span class="hub-arr-card-ico">{{ $pData['icono'] }}</span>
                                <span class="hub-arr-card-badge {{ $pCount === 0 ? 'is-zero' : '' }}" data-hub-count="seg-{{ $pSlug }}">
                                    {{ $pCount }} {{ $pCount === 1 ? 'acción' : 'acciones' }}
                                </span>
                            </div>
                            <div class="hub-arr-card-title">{{ $pData['nombre'] }}</div>
                            <div class="hub-arr-card-desc">
                                {{ $pCount > 0 ? 'Personaliza, renombra, reordena por arrastre o elimina acciones.' : 'Aún sin acciones configuradas. Haz clic para agregar.' }}
                            </div>
                            <div class="hub-arr-card-cta">Configurar {{ $pData['nombre'] }} →</div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Vista 3: Editor arrastrable con guardado automático en vivo --}}
            <div id="hub-view-editor" style="display:none">
                <form id="hub-arr-add-form" class="hub-arr-add">
                    <div class="hub-arr-add-title">+ Agregar nueva acción (se guarda al instante sin cerrar)</div>
                    <div class="hub-arr-form-row">
                        <div style="flex:1 1 280px; min-width:200px;">
                            <input id="hub-arr-add-input" type="text" class="hub-arr-input" maxlength="200" required
                                   placeholder="Escribe el nombre de la acción y presiona Enter...">
                        </div>
                        <button type="submit" id="hub-arr-add-btn" class="btn btn-primary btn-sm">+ Agregar acción</button>
                    </div>
                </form>

                <div>
                    <div class="hub-arr-list-hdr">
                        <div>
                            <div id="hub-arr-list-title" style="font-size:13px; font-weight:700; color:var(--text);">Acciones configuradas</div>
                            <div style="font-size:12px; color:var(--text-muted);">
                                ✋ Arrastra desde <strong>⋮⋮</strong> para ordenar. Al editar un texto o agregar, todo se guarda automáticamente.
                            </div>
                        </div>
                        <span id="hub-arr-status" class="hub-arr-status">Todo guardado</span>
                    </div>

                    <div id="hub-arr-sortable-list" class="hub-arr-list"></div>
                    <div id="hub-arr-empty" class="hub-arr-empty" style="display:none">
                        Aún no hay acciones de arranque configuradas aquí. Agrega las que necesites arriba sin salir de esta ventana.
                    </div>
                </div>
            </div>
        </div>

        <div class="hub-arr-footer">
            <span>Los cambios se guardan automáticamente en la base de datos mientras editas.</span>
            <button type="button" id="hub-arr-done-btn" class="btn btn-secondary btn-sm">Cerrar</button>
        </div>
    </div>
</div>

<script>
(function () {
    const backdrop = document.getElementById('sic-arranque-hub-backdrop');
    if (!backdrop) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const viewHome = document.getElementById('hub-view-home');
    const viewPlats = document.getElementById('hub-view-platforms');
    const viewEditor = document.getElementById('hub-view-editor');
    const backBtn = document.getElementById('hub-arr-back-btn');
    const titleEl = document.getElementById('hub-arr-title');
    const subEl = document.getElementById('hub-arr-sub');
    const listEl = document.getElementById('hub-arr-sortable-list');
    const emptyEl = document.getElementById('hub-arr-empty');
    const listTitleEl = document.getElementById('hub-arr-list-title');
    const statusEl = document.getElementById('hub-arr-status');
    const addForm = document.getElementById('hub-arr-add-form');
    const addInput = document.getElementById('hub-arr-add-input');
    const addBtn = document.getElementById('hub-arr-add-btn');

    const storeData = {
        colegios: {
            kind: 'colegios',
            label: 'Colegios globales',
            icono: '🏫',
            field: 'name',
            items: @json($hubGlobalItems),
            routes: @json($hubGlobalRoutes),
        },
        seguimiento: @json($hubSegPayload),
    };

    let currentView = 'home'; // 'home' | 'platforms' | 'editor'
    let activeScope = null;
    let dirty = false;
    let draggedRow = null;

    function setStatus(cls, text) {
        if (!statusEl) return;
        statusEl.className = 'hub-arr-status' + (cls ? ' ' + cls : '');
        statusEl.textContent = text;
    }

    function escapeAttr(val) {
        return String(val ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function syncAllCounts() {
        const colCount = storeData.colegios.items.length;
        const colBadge = document.querySelector('[data-hub-count="colegios"]');
        if (colBadge) colBadge.textContent = `${colCount} ${colCount === 1 ? 'acción' : 'acciones'}`;
        document.querySelectorAll('[data-hub-count="colegios-num"]').forEach(el => {
            el.textContent = String(colCount);
        });
        document.querySelectorAll('[data-cfg-colegios-count]').forEach(el => {
            el.textContent = `${colCount} ${colCount === 1 ? 'acción' : 'acciones'}`;
        });

        let segTotal = 0;
        Object.keys(storeData.seguimiento).forEach(slug => {
            const c = storeData.seguimiento[slug].items.length;
            segTotal += c;
            const badge = document.querySelector(`[data-hub-count="seg-${slug}"]`);
            if (badge) {
                badge.textContent = `${c} ${c === 1 ? 'acción' : 'acciones'}`;
                badge.classList.toggle('is-zero', c === 0);
            }
            document.querySelectorAll(`[data-btn-seg-count="${slug}"]`).forEach(el => {
                el.textContent = String(c);
            });
            document.querySelectorAll(`[data-cfg-seg-count="${slug}"]`).forEach(el => {
                el.textContent = `${c} ${c === 1 ? 'acción' : 'acciones'}`;
                el.classList.toggle('is-zero', c === 0);
            });
        });

        const totalBadge = document.querySelector('[data-hub-count="seg-total"]');
        if (totalBadge) totalBadge.textContent = `${segTotal} acciones`;
        document.querySelectorAll('[data-cfg-arranque-total]').forEach(el => {
            el.textContent = String(colCount + segTotal);
        });

        if (activeScope && listTitleEl) {
            listTitleEl.textContent = `Acciones configuradas (${activeScope.items.length})`;
        }
    }

    function renderList() {
        if (!activeScope || !listEl) return;
        listEl.innerHTML = '';

        const items = activeScope.items;
        if (emptyEl) emptyEl.style.display = items.length === 0 ? 'block' : 'none';

        items.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'hub-arr-item';
            row.draggable = true;
            row.dataset.id = String(item.id);
            row.innerHTML = `
                <span class="hub-arr-handle" title="Arrastra para reordenar">⋮⋮</span>
                <span class="hub-arr-pos">#${idx + 1}</span>
                <div class="hub-arr-move-btns">
                    <button type="button" class="hub-arr-move-btn" data-move="up" title="Subir" ${idx === 0 ? 'disabled' : ''}>▲</button>
                    <button type="button" class="hub-arr-move-btn" data-move="down" title="Bajar" ${idx === items.length - 1 ? 'disabled' : ''}>▼</button>
                </div>
                <div style="flex:1 1 240px; min-width:180px;">
                    <input type="text" class="hub-arr-input hub-row-text" maxlength="200" required value="${escapeAttr(item.text)}" data-saved-text="${escapeAttr(item.text)}" aria-label="Nombre de la acción">
                </div>
                <button type="button" class="btn btn-secondary btn-sm hub-row-save">Guardar</button>
                <button type="button" class="hub-arr-del-btn hub-row-del">Eliminar</button>
            `;
            listEl.appendChild(row);
        });
        syncAllCounts();
    }

    function refreshRowNumbers() {
        const rows = Array.from(listEl.querySelectorAll('.hub-arr-item'));
        rows.forEach((row, idx) => {
            const posEl = row.querySelector('.hub-arr-pos');
            if (posEl) posEl.textContent = '#' + (idx + 1);
            const up = row.querySelector('[data-move="up"]');
            const down = row.querySelector('[data-move="down"]');
            if (up) up.disabled = idx === 0;
            if (down) down.disabled = idx === rows.length - 1;
        });
    }

    async function apiJson(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        if (!res.ok) {
            const errData = await res.json().catch(() => null);
            throw new Error(errData?.message || ('HTTP ' + res.status));
        }
        return await res.json();
    }

    async function saveRowName(row) {
        if (!activeScope) return;
        const input = row.querySelector('.hub-row-text');
        const saveBtn = row.querySelector('.hub-row-save');
        if (!input) return;
        const nextText = input.value.trim();
        if (!nextText) {
            input.value = input.dataset.savedText || '';
            return;
        }
        if (nextText === input.dataset.savedText) return;

        const id = Number(row.dataset.id);
        const url = activeScope.routes.update.replace('__ID__', String(id));
        const payload = { [activeScope.field]: nextText };

        if (saveBtn) saveBtn.disabled = true;
        setStatus('is-saving', '⏳ Guardando cambio...');

        try {
            await apiJson(url, 'PATCH', payload);
            input.value = nextText;
            input.dataset.savedText = nextText;
            const found = activeScope.items.find(i => Number(i.id) === id);
            if (found) found.text = nextText;
            dirty = true;
            setStatus('is-saved', '✓ Acción actualizada');
        } catch (err) {
            console.error(err);
            setStatus('is-error', 'No se pudo guardar el nombre');
        } finally {
            if (saveBtn) saveBtn.disabled = false;
        }
    }

    async function persistOrder() {
        if (!activeScope) return;
        const rows = Array.from(listEl.querySelectorAll('.hub-arr-item'));
        const ids = rows.map(r => Number(r.dataset.id));
        if (!ids.length) return;

        const mapById = new Map(activeScope.items.map(i => [Number(i.id), i]));
        activeScope.items = ids.map(id => mapById.get(id)).filter(Boolean);

        setStatus('is-saving', '⏳ Guardando orden...');
        try {
            await apiJson(activeScope.routes.reorder, 'POST', { ids });
            dirty = true;
            setStatus('is-saved', '✓ Orden guardado');
        } catch (err) {
            console.error(err);
            setStatus('is-error', 'No se pudo guardar el orden');
        }
    }

    function deleteRowWithSicModal(row) {
        if (!activeScope) return;
        const id = Number(row.dataset.id);
        const item = activeScope.items.find(i => Number(i.id) === id);
        const nombre = item ? item.text : 'esta acción';

        window.confirmarEliminar(
            'Eliminar acción de arranque',
            `¿Deseas eliminar «${nombre}» de ${activeScope.label}? También se eliminará su registro de avance en todos los colegios.`,
            async () => {
                const url = activeScope.routes.destroy.replace('__ID__', String(id));
                setStatus('is-saving', '⏳ Eliminando...');
                try {
                    await apiJson(url, 'DELETE');
                    activeScope.items = activeScope.items.filter(i => Number(i.id) !== id);
                    dirty = true;
                    renderList();
                    setStatus('is-saved', '✓ Acción eliminada');
                } catch (err) {
                    console.error(err);
                    setStatus('is-error', 'No se pudo eliminar la acción');
                }
            }
        );
    }

    function showHome() {
        currentView = 'home';
        activeScope = null;
        viewHome.style.display = 'block';
        viewPlats.style.display = 'none';
        viewEditor.style.display = 'none';
        backBtn.style.display = 'none';
        titleEl.textContent = '🚀 Configurar acciones de arranque';
        subEl.textContent = 'Elige si deseas administrar el catálogo global de colegios o las acciones de Seguimiento por plataforma.';
        syncAllCounts();
    }

    function showPlatforms() {
        currentView = 'platforms';
        activeScope = null;
        viewHome.style.display = 'none';
        viewPlats.style.display = 'block';
        viewEditor.style.display = 'none';
        backBtn.style.display = 'inline-flex';
        backBtn.textContent = '← Inicio';
        titleEl.textContent = '🧭 Acciones de arranque de Seguimiento';
        subEl.textContent = 'Selecciona una plataforma para agregar, editar o reordenar sus acciones de arranque.';
        syncAllCounts();
    }

    function showEditor(mode, slug) {
        currentView = 'editor';
        if (mode === 'colegios') {
            activeScope = storeData.colegios;
            backBtn.textContent = '← Inicio';
            titleEl.textContent = '🏫 Acciones de arranque globales (Colegios)';
            subEl.textContent = 'Al agregar una acción aquí, se crea automáticamente en pendiente para todos los niveles escolares.';
        } else {
            const p = storeData.seguimiento[slug];
            if (!p) return showPlatforms();
            activeScope = {
                kind: 'seguimiento',
                slug: p.slug,
                label: p.nombre,
                icono: p.icono,
                field: 'etiqueta',
                get items() { return p.items; },
                set items(v) { p.items = v; },
                routes: p.routes,
            };
            backBtn.textContent = '← Plataformas';
            titleEl.textContent = `${p.icono} Acciones de arranque — ${p.nombre}`;
            subEl.textContent = `Agrega, edita o arrastra para ordenar las acciones de ${p.nombre}. Todo se guarda automáticamente sin cerrar esta ventana.`;
        }
        viewHome.style.display = 'none';
        viewPlats.style.display = 'none';
        viewEditor.style.display = 'block';
        backBtn.style.display = 'inline-flex';
        setStatus('', 'Todo guardado');
        renderList();
        setTimeout(() => addInput?.focus(), 60);
    }

    function closeHub() {
        backdrop.classList.remove('open');
        if (dirty) {
            window.location.reload();
        }
    }

    window.abrirHubArranque = function (mode, slug) {
        if (mode === 'colegios') {
            showEditor('colegios');
        } else if (mode === 'seguimiento') {
            if (slug && storeData.seguimiento[slug]) {
                showEditor('seguimiento', slug);
            } else {
                showPlatforms();
            }
        } else {
            showHome();
        }
        backdrop.classList.add('open');
    };

    window.abrirCatalogoProcesos = function () {
        window.abrirHubArranque('colegios');
    };

    window.abrirCatalogoSeguimiento = function (slug) {
        const targetSlug = slug || window.__sicCurrentSegSlug || '';
        window.abrirHubArranque('seguimiento', targetSlug);
    };

    backBtn?.addEventListener('click', () => {
        if (currentView === 'editor' && activeScope?.kind === 'seguimiento') {
            showPlatforms();
        } else {
            showHome();
        }
    });

    document.getElementById('hub-arr-close-btn')?.addEventListener('click', closeHub);
    document.getElementById('hub-arr-done-btn')?.addEventListener('click', closeHub);
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) closeHub();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && backdrop.classList.contains('open')) {
            const delModal = document.getElementById('modal-confirmar-eliminar');
            if (delModal && delModal.style.display === 'flex') return;
            closeHub();
        }
    });

    addForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!activeScope || !addInput) return;
        const text = addInput.value.trim();
        if (!text) return;

        addBtn.disabled = true;
        setStatus('is-saving', '⏳ Agregando...');
        try {
            const res = await apiJson(activeScope.routes.store, 'POST', { [activeScope.field]: text });
            const item = res.item || {};
            const newText = item.name ?? item.etiqueta ?? text;
            activeScope.items.push({
                id: Number(item.id),
                text: String(newText),
                order: Number(item.order ?? item.orden ?? (activeScope.items.length + 1)),
            });
            dirty = true;
            addInput.value = '';
            renderList();
            setStatus('is-saved', `✓ «${newText}» agregada`);
            addInput.focus();
        } catch (err) {
            console.error(err);
            setStatus('is-error', 'No se pudo agregar la acción');
        } finally {
            addBtn.disabled = false;
        }
    });

    listEl?.addEventListener('click', (e) => {
        const row = e.target.closest('.hub-arr-item');
        if (!row) return;

        if (e.target.closest('.hub-row-save')) {
            saveRowName(row);
            return;
        }
        if (e.target.closest('.hub-row-del')) {
            deleteRowWithSicModal(row);
            return;
        }
        const moveBtn = e.target.closest('[data-move]');
        if (moveBtn) {
            if (moveBtn.dataset.move === 'up' && row.previousElementSibling) {
                listEl.insertBefore(row, row.previousElementSibling);
            } else if (moveBtn.dataset.move === 'down' && row.nextElementSibling) {
                listEl.insertBefore(row.nextElementSibling, row);
            } else {
                return;
            }
            refreshRowNumbers();
            persistOrder();
        }
    });

    listEl?.addEventListener('change', (e) => {
        if (e.target.classList.contains('hub-row-text')) {
            const row = e.target.closest('.hub-arr-item');
            if (row) saveRowName(row);
        }
    });

    listEl?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.classList.contains('hub-row-text')) {
            e.preventDefault();
            const row = e.target.closest('.hub-arr-item');
            if (row) saveRowName(row);
        }
    });

    listEl?.addEventListener('mousedown', (e) => {
        const row = e.target.closest('.hub-arr-item');
        if (!row) return;
        const interactive = e.target.closest('input, textarea, button, select');
        row.setAttribute('draggable', interactive ? 'false' : 'true');
    });

    listEl?.addEventListener('dragstart', (e) => {
        const row = e.target.closest('.hub-arr-item');
        if (!row) return;
        draggedRow = row;
        row.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.dataset.id || '');
    });

    listEl?.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (!draggedRow) return;
        const target = e.target.closest('.hub-arr-item');
        listEl.querySelectorAll('.hub-arr-item.is-drag-over').forEach(el => el.classList.remove('is-drag-over'));
        if (!target || target === draggedRow) return;
        target.classList.add('is-drag-over');
        const rect = target.getBoundingClientRect();
        const after = (e.clientY - rect.top) > (rect.height / 2);
        listEl.insertBefore(draggedRow, after ? target.nextSibling : target);
        refreshRowNumbers();
    });

    listEl?.addEventListener('drop', (e) => {
        e.preventDefault();
        listEl.querySelectorAll('.hub-arr-item.is-drag-over').forEach(el => el.classList.remove('is-drag-over'));
    });

    listEl?.addEventListener('dragend', () => {
        if (!draggedRow) return;
        draggedRow.classList.remove('is-dragging');
        draggedRow.setAttribute('draggable', 'true');
        listEl.querySelectorAll('.hub-arr-item.is-drag-over').forEach(el => el.classList.remove('is-drag-over'));
        draggedRow = null;
        refreshRowNumbers();
        persistOrder();
    });
})();
</script>
