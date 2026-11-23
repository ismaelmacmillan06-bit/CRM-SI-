@php
    $notifInitUnread   = (int) ($notifUnreadCount ?? 0);
    $notifInitLatest   = (int) ($notifLatestId ?? 0);
    $notifInitSummary  = $notifCategorySummary ?? [];
    $notifInitItems    = $notifInitialItems ?? [];
    $notifSystemUsers  = $notifSystemUsers ?? [];
    $notifShowLogin    = (bool) ($mostrarResumenLoginNotif ?? false);
    $notifPrevSeenId   = $notifPrevSeenId ?? null;
    $notifIsAdmin      = auth()->user()?->hasRole('admin') ?? false;
    $notifCategories   = \App\Models\UserNotification::CATEGORIAS;
    $notifRoleLabels   = \App\Models\UserNotification::ROL_ETIQUETAS;

    $allUnreadCollection = collect($notifInitItems)->where('is_unread', true)->values();
    $newSinceLastCollection = $notifPrevSeenId !== null
        ? $allUnreadCollection->filter(fn ($i) => (int) $i['id'] > (int) $notifPrevSeenId)->values()
        : $allUnreadCollection;

    $hasNewSinceLast = $newSinceLastCollection->isNotEmpty();
    $loginDisplayItems = ($hasNewSinceLast ? $newSinceLastCollection : $allUnreadCollection)->take(12)->values()->all();
    $loginAllUnreadPreview = $allUnreadCollection->take(12)->values()->all();
    $loginNewCount = $newSinceLastCollection->count();
    $loginTotalUnreadCount = $allUnreadCollection->count();

    $loginCatSummary = [];
    $summarySource = $hasNewSinceLast ? $newSinceLastCollection : $allUnreadCollection;
    if ($summarySource->isNotEmpty()) {
        $grouped = $summarySource->groupBy('category');
        foreach ($notifCategories as $slug => $meta) {
            $cnt = isset($grouped[$slug]) ? $grouped[$slug]->count() : 0;
            if ($cnt > 0) {
                $loginCatSummary[] = [
                    'slug'  => $slug,
                    'label' => $meta['label'],
                    'icon'  => $meta['icon'],
                    'count' => $cnt,
                ];
            }
        }
    }
@endphp

<style>
    /* ── BOTÓN CAMPANA EN TOPBAR Y FLOTANTE AL HACER SCROLL ── */
    .sic-topbar-right {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .sic-bell-slot {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        flex-shrink: 0;
        min-height: 36px;
    }
    .sic-bell-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 7px 14px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        white-space: nowrap;
        transform-origin: center center;
        transition: background-color 0.24s ease, color 0.24s ease, border-color 0.24s ease, box-shadow 0.24s ease, transform 0.24s ease, border-radius 0.24s ease;
        box-shadow: 0 1px 3px rgba(24, 19, 17, 0.05);
    }
    .sic-bell-btn:hover {
        border-color: var(--accent);
        background: #fff8f7;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(226, 35, 26, 0.12);
    }
    .sic-bell-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        transition: transform 0.24s ease;
    }
    .sic-bell-text {
        display: inline-block;
        white-space: nowrap;
    }
    .sic-bell-btn.has-unread .sic-bell-icon {
        animation: sicBellRing 2.6s ease-in-out infinite;
        transform-origin: top center;
    }
    @keyframes sicBellRing {
        0%, 82%, 100% { transform: rotate(0deg); }
        85% { transform: rotate(14deg); }
        89% { transform: rotate(-12deg); }
        93% { transform: rotate(8deg); }
        96% { transform: rotate(-6deg); }
    }
    .sic-bell-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--accent);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        box-shadow: 0 0 0 2px rgba(226, 35, 26, 0.2);
        flex-shrink: 0;
    }
    .sic-bell-badge.is-zero {
        background: var(--surface2);
        color: var(--text-muted);
        box-shadow: none;
    }
    .sic-bell-btn.is-floating {
        position: fixed;
        top: 14px;
        right: 24px;
        z-index: 2490;
        width: 44px;
        height: 44px;
        padding: 0;
        gap: 0;
        border-radius: 999px;
        background: #181311;
        border-color: rgba(255, 255, 255, 0.16);
        color: #fff;
        box-shadow: 0 10px 26px rgba(24, 19, 17, 0.28), 0 0 0 1px rgba(255, 255, 255, 0.08);
    }
    .sic-bell-btn.is-floating:hover {
        background: #28201d;
        border-color: var(--accent);
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 14px 30px rgba(226, 35, 26, 0.24), 0 0 0 2px rgba(226, 35, 26, 0.16);
    }
    .sic-bell-btn.is-floating .sic-bell-icon {
        font-size: 18px;
    }
    .sic-bell-btn.is-floating .sic-bell-text {
        display: none;
    }
    .sic-bell-btn.is-floating .sic-bell-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 19px;
        height: 19px;
        padding: 0 5px;
        font-size: 10px;
        border: 2px solid #181311;
        box-shadow: 0 2px 6px rgba(226, 35, 26, 0.35);
    }
    .sic-bell-btn.is-floating .sic-bell-badge.is-zero {
        display: none;
    }
    body.sic-bell-is-floating .sic-notif-modal {
        top: 66px;
        max-height: calc(100vh - 82px);
    }
    body.sic-bell-is-floating .sic-toast-stack {
        top: 68px;
    }
    body.sic-bell-is-floating .sic-login-summary {
        top: 66px;
    }

    /* ── BANNER FLOTANTE DE RESUMEN AL INICIAR SESIÓN ── */
    .sic-login-summary {
        position: fixed;
        top: 68px;
        right: 22px;
        z-index: 2550;
        width: min(415px, calc(100vw - 24px));
        background: #fff;
        color: var(--text);
        border-radius: 14px;
        border: 1px solid var(--border);
        box-shadow: 0 20px 50px rgba(24, 19, 17, 0.24), 0 4px 14px rgba(24, 19, 17, 0.08);
        overflow: hidden;
        animation: sicToastIn 0.34s cubic-bezier(.22, 1, .36, 1);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .sic-login-summary.is-leaving {
        opacity: 0;
        transform: translateX(24px);
    }
    .sic-login-summary-head {
        background: linear-gradient(125deg, #181311 0%, #2a1d19 75%, #3d1814 100%);
        color: #fff;
        padding: 13px 15px;
        border-bottom: 2px solid var(--accent);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }
    .sic-login-summary-title {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
    }
    .sic-login-summary-sub {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.78);
        margin-top: 3px;
    }
    .sic-login-mode-tabs {
        display: flex;
        gap: 6px;
        margin-top: 8px;
    }
    .sic-login-mode-btn {
        padding: 3px 9px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.84);
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .sic-login-mode-btn.active {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }
    .sic-login-summary-body {
        padding: 10px 12px;
        background: var(--surface);
        display: flex;
        flex-direction: column;
        gap: 9px;
    }
    .sic-login-summary-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    .sic-summary-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 9px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--border);
        color: var(--text);
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.16s;
    }
    .sic-summary-pill:hover {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }
    .sic-login-summary-list {
        max-height: 220px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 5px;
        scrollbar-width: thin;
        scrollbar-color: #d4cec4 transparent;
    }
    .sic-login-summary-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 7px 9px;
        border-radius: 9px;
        background: #fff;
        border: 1px solid var(--border);
        cursor: pointer;
        text-align: left;
        width: 100%;
        transition: all 0.14s;
    }
    .sic-login-summary-item:hover {
        border-color: var(--accent);
        background: #fffaf9;
    }
    .sic-login-summary-item-title {
        font-size: 12px;
        font-weight: 600;
        color: var(--text);
        line-height: 1.3;
    }
    .sic-login-summary-item-meta {
        font-size: 10.5px;
        color: var(--text-muted);
        margin-top: 2px;
    }
    .sic-login-summary-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 9px 12px 12px;
        background: #fff;
        border-top: 1px solid var(--border);
    }
    .sic-login-progress {
        height: 3px;
        width: 100%;
        background: rgba(226, 35, 26, 0.12);
    }
    .sic-login-progress-bar {
        height: 100%;
        width: 100%;
        background: var(--accent);
        transform-origin: left center;
        animation: sicToastTimer 22s linear forwards;
    }
    .sic-login-summary:hover .sic-login-progress-bar {
        animation-play-state: paused;
    }

    /* ── BANNERS FLOTANTES EN SESIÓN (UNA NOTIFICACIÓN NUEVA) ── */
    .sic-toast-stack {
        position: fixed;
        top: 76px;
        right: 22px;
        z-index: 2600;
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: min(390px, calc(100vw - 28px));
        pointer-events: none;
    }
    .sic-toast {
        pointer-events: auto;
        position: relative;
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--border);
        border-left: 4px solid var(--accent);
        border-radius: 12px;
        padding: 13px 15px 15px;
        box-shadow: 0 16px 40px rgba(24, 19, 17, 0.2);
        animation: sicToastIn 0.32s cubic-bezier(.22, 1, .36, 1);
        transition: opacity 0.28s ease, transform 0.28s ease;
    }
    .sic-toast.is-leaving {
        opacity: 0;
        transform: translateX(24px);
    }
    @keyframes sicToastIn {
        from { opacity: 0; transform: translateX(28px) scale(0.97); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
    }
    .sic-toast-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 5px;
    }
    .sic-toast-meta {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
        font-size: 11px;
        font-weight: 600;
        min-width: 0;
        flex: 1;
    }
    .sic-toast-controls {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
    }
    .sic-toast-queue-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 7px;
        border-radius: 999px;
        background: #181311;
        color: #fff;
        font-size: 10.5px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
    .sic-toast-next {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        border: 1px solid var(--border);
        background: var(--surface2);
        color: var(--text);
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 999px;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .sic-toast-next:hover {
        border-color: var(--accent);
        color: var(--accent);
        background: #fff5f5;
    }
    .sic-toast-close {
        border: none;
        background: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
        padding: 2px 5px;
        border-radius: 6px;
    }
    .sic-toast-close:hover { background: var(--surface2); color: var(--text); }
    .sic-toast-title {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text);
        line-height: 1.35;
        margin-bottom: 3px;
    }
    .sic-toast-msg {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.4;
        margin-bottom: 9px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .sic-toast-actions {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
    }
    .sic-toast-progress {
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 100%;
        background: rgba(226, 35, 26, 0.14);
    }
    .sic-toast-progress-bar {
        height: 100%;
        width: 100%;
        background: var(--accent);
        transform-origin: left center;
        animation: sicToastTimer 8s linear forwards;
    }

    .sic-hdr-icon-btn.is-silent {
        background: rgba(226, 35, 26, 0.28);
        border-color: rgba(254, 202, 202, 0.72);
        color: #fee2e2;
    }

    /* Pill indicadora junto al botón de campana cuando modo silencioso está activo */
    .sic-bell-silent-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        background: #fff1f0;
        color: var(--accent2, #B31810);
        border: 1px solid #fecaca;
    }
    .sic-bell-btn.is-floating .sic-bell-silent-tag {
        display: none;
    }

    /* ══════════ Modal grande centrado con fondo desenfocado: Ajustes SIC de notificaciones ══════════ */
    .sic-settings-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2700;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(24, 19, 17, 0.58);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }
    .sic-settings-backdrop.open {
        display: flex;
    }
    .sic-settings-modal {
        width: min(860px, 100%);
        max-height: 90vh;
        background: var(--surface, #FBFAF7);
        border: 1px solid var(--border, #E4E0D8);
        border-radius: 16px;
        box-shadow: 0 28px 72px rgba(24, 19, 17, 0.36);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: sicModalPop 0.2s cubic-bezier(.22, 1, .36, 1);
    }
    @keyframes sicModalPop {
        from { opacity: 0; transform: scale(0.97) translateY(8px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .sic-settings-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 24px;
        background: linear-gradient(120deg, var(--primary, #181311) 0%, var(--secondary, #221A17) 100%);
        color: #ffffff;
        border-bottom: 2px solid var(--accent, #E2231A);
    }
    .sic-settings-title {
        margin: 0;
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 18.5px;
        font-weight: 700;
        color: #ffffff;
    }
    .sic-settings-subtitle {
        margin: 4px 0 0;
        font-size: 12.5px;
        color: rgba(255, 255, 255, 0.72);
        line-height: 1.45;
    }
    .sic-settings-body {
        padding: 20px 24px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 18px;
        background: var(--surface, #FBFAF7);
    }
    .sic-settings-section {
        border: 1px solid var(--border, #E4E0D8);
        border-radius: 13px;
        padding: 16px 18px;
        background: var(--surface2, #F2EFE9);
    }
    .sic-settings-sec-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }
    .sic-settings-section-title {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 13.5px;
        font-weight: 700;
        color: var(--text, #181311);
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .sic-mode-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }
    @media (max-width: 640px) {
        .sic-mode-grid { grid-template-columns: 1fr; }
    }
    .sic-mode-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 13px 14px;
        border-radius: 11px;
        background: #ffffff;
        border: 1.5px solid var(--border, #E4E0D8);
        cursor: pointer;
        text-align: left;
        transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
    }
    .sic-mode-card:hover {
        border-color: #d4cbc0;
    }
    .sic-mode-card.is-selected {
        border-color: var(--accent, #E2231A);
        background: #fff6f5;
        box-shadow: 0 0 0 2px rgba(226, 35, 26, 0.14);
    }
    .sic-mode-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: var(--surface2, #F2EFE9);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .sic-mode-card.is-selected .sic-mode-icon {
        background: rgba(226, 35, 26, 0.12);
    }
    .sic-mode-info {
        flex: 1;
        min-width: 0;
    }
    .sic-mode-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 3px;
    }
    .sic-mode-head strong {
        font-size: 13.5px;
        color: var(--text, #181311);
    }
    .sic-mode-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 999px;
        background: var(--accent, #E2231A);
        color: #ffffff;
        display: none;
    }
    .sic-mode-card.is-selected .sic-mode-badge {
        display: inline-block;
    }
    .sic-mode-info span {
        display: block;
        font-size: 12px;
        color: var(--text-muted, #6F6A64);
        line-height: 1.4;
    }
    .sic-settings-rows {
        display: flex;
        flex-direction: column;
        gap: 9px;
    }
    .sic-delivery-subsettings {
        display: flex;
        flex-direction: column;
        gap: 9px;
        margin-bottom: 9px;
        transition: opacity 0.2s;
    }
    .sic-delivery-subsettings.is-muted {
        opacity: 0.5;
        pointer-events: none;
    }
    .sic-silent-pause-note {
        display: none;
        font-size: 12px;
        font-weight: 600;
        color: var(--accent2, #B31810);
        background: #fff1f0;
        border: 1px solid #fecaca;
        border-radius: 9px;
        padding: 8px 12px;
        margin-bottom: 9px;
    }
    .sic-silent-pause-note.is-visible {
        display: block;
    }
    .sic-setting-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 11px 14px;
        background: #ffffff;
        border: 1px solid var(--border, #E4E0D8);
        border-radius: 10px;
    }
    .sic-setting-info strong {
        display: block;
        font-size: 13.5px;
        color: var(--text, #181311);
    }
    .sic-setting-info span {
        display: block;
        font-size: 12px;
        color: var(--text-muted, #6F6A64);
        margin-top: 2px;
        line-height: 1.4;
    }
    .sic-setting-select {
        padding: 7px 11px;
        border: 1px solid var(--border, #E4E0D8);
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        background: var(--surface, #FBFAF7);
        color: var(--text, #181311);
    }
    .sic-switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 26px;
        flex-shrink: 0;
    }
    .sic-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .sic-switch-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: #d6cfc5;
        border-radius: 999px;
        transition: background 0.2s;
    }
    .sic-switch-slider::before {
        content: "";
        position: absolute;
        height: 20px;
        width: 20px;
        left: 3px;
        top: 3px;
        background: #ffffff;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(24, 19, 17, 0.22);
        transition: transform 0.2s;
    }
    .sic-switch input:checked + .sic-switch-slider {
        background: var(--accent, #E2231A);
    }
    .sic-switch input:checked + .sic-switch-slider::before {
        transform: translateX(20px);
    }
    .sic-cats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
        gap: 9px;
    }
    .sic-cat-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 11px 13px;
        border-radius: 10px;
        background: #ffffff;
        border: 1px solid var(--border, #E4E0D8);
    }
    .sic-cat-card-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--text, #181311);
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }
    .sic-cat-pill-ico {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }
    .sic-mini-chip-btn {
        border: 1px solid var(--border, #E4E0D8);
        background: #ffffff;
        color: var(--text, #181311);
        border-radius: 7px;
        padding: 4px 10px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .sic-mini-chip-btn:hover {
        border-color: var(--accent, #E2231A);
        color: var(--accent, #E2231A);
    }
    .sic-settings-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 15px 24px;
        border-top: 1px solid var(--border, #E4E0D8);
        background: var(--surface2, #F2EFE9);
    }
    .sic-settings-btn-primary {
        border: none;
        background: var(--accent, #E2231A);
        color: #ffffff;
        border-radius: 9px;
        padding: 9px 18px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s;
    }
    .sic-settings-btn-primary:hover {
        background: var(--accent2, #B31810);
    }
    .sic-settings-btn-ghost {
        border: 1px solid var(--border, #E4E0D8);
        background: #ffffff;
        color: var(--text, #181311);
        border-radius: 9px;
        padding: 8px 15px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .sic-settings-btn-ghost:hover {
        background: var(--surface, #FBFAF7);
    }
    .sic-toast:hover .sic-toast-progress-bar,
    .sic-toast.is-tab-hidden .sic-toast-progress-bar { animation-play-state: paused; }
    @keyframes sicToastTimer {
        from { transform: scaleX(1); }
        to   { transform: scaleX(0); }
    }

    /* ── PANEL FLOTANTE COMPACTO DE NOTIFICACIONES (POPOVER) ── */
    .sic-notif-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2500;
        background: transparent;
    }
    .sic-notif-backdrop.is-open {
        display: block;
    }
    .sic-notif-modal {
        position: fixed;
        top: 62px;
        right: 24px;
        width: 425px;
        max-width: calc(100vw - 24px);
        max-height: min(610px, calc(100vh - 80px));
        background: var(--surface);
        border-radius: 14px;
        border: 1px solid var(--border);
        box-shadow: 0 18px 48px rgba(24, 19, 17, 0.24), 0 4px 12px rgba(24, 19, 17, 0.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: sicPopoverIn 0.2s cubic-bezier(.22, 1, .36, 1);
        transform-origin: top right;
    }
    @keyframes sicPopoverIn {
        from { opacity: 0; transform: translateY(-8px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Header compacto de una línea */
    .sic-notif-header {
        background: linear-gradient(120deg, var(--primary) 0%, var(--secondary) 100%);
        color: #fff;
        padding: 11px 14px;
        border-bottom: 2px solid var(--accent);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-shrink: 0;
    }
    .sic-notif-header-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 0;
        overflow: hidden;
    }
    .sic-notif-header-title {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
    }
    .sic-notif-header-actions {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
    }
    .sic-hdr-icon-btn {
        height: 28px;
        min-width: 28px;
        padding: 0 8px;
        border-radius: 7px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        font-size: 11.5px;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        transition: all 0.15s;
    }
    .sic-hdr-icon-btn:hover { background: rgba(255, 255, 255, 0.18); }
    .sic-hdr-icon-btn.is-icon-only {
        width: 28px;
        height: 28px;
        min-width: 28px;
        padding: 0;
        font-size: 13px;
    }
    .sic-hdr-icon-btn.win-default {
        background: rgba(245, 158, 11, 0.22);
        border-color: rgba(245, 158, 11, 0.5);
        color: #fde68a;
    }
    .sic-hdr-icon-btn.win-denied {
        background: rgba(226, 35, 26, 0.24);
        border-color: rgba(254, 202, 202, 0.65);
        color: #fecaca;
    }
    .sic-hdr-close {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
    }
    .sic-hdr-close:hover { background: var(--accent); border-color: var(--accent); }

    /* BARRA COMPACTA DE CONTROL DESPLEGABLE */
    .sic-controls-bar {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 12px;
        background: #fff;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .sic-cat-dropdown-wrap { position: relative; }
    .sic-cat-dropdown-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 5px 11px;
        border-radius: 999px;
        border: 1px solid var(--primary);
        background: var(--primary);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.14s;
    }
    .sic-cat-dropdown-btn:hover { background: var(--secondary); }
    .sic-cat-active-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 19px;
        height: 19px;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--accent);
        color: #fff;
        font-size: 10.5px;
        font-weight: 700;
        line-height: 1;
        flex-shrink: 0;
    }
    .sic-cat-dropdown-menu {
        display: none;
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        z-index: 40;
        width: 255px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 14px 36px rgba(24, 19, 17, 0.18);
        padding: 6px;
        max-height: 340px;
        overflow-y: auto;
    }
    .sic-cat-dropdown-menu.is-open { display: block; }
    .sic-cat-option {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 7px 9px;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        color: var(--text);
        cursor: pointer;
        text-align: left;
    }
    .sic-cat-option:hover { background: var(--surface2); }
    .sic-cat-option.active {
        background: #fff1f0;
        color: var(--accent2);
        font-weight: 600;
    }
    .sic-cat-opt-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 6px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1;
        background: rgba(226, 35, 26, 0.12);
        color: var(--accent);
        flex-shrink: 0;
    }

    .sic-controls-right {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .sic-dropdown-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        font-size: 11.5px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.14s;
    }
    .sic-dropdown-toggle-btn:hover,
    .sic-dropdown-toggle-btn.is-active {
        border-color: var(--accent);
        background: #fff8f7;
        color: var(--accent2);
    }

    /* ── PANEL DESPLEGABLE DE FILTROS + SELECTORES PERSONALIZADOS CRM ── */
    .sic-notif-toolbar {
        display: none;
        flex-direction: column;
        gap: 8px;
        padding: 10px 12px;
        background: var(--surface2);
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .sic-notif-toolbar.is-open { display: flex; }
    .sic-toolbar-row {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .sic-filter-input {
        padding: 6px 10px;
        border-radius: 9px;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        font-size: 12px;
        font-family: 'Inter', sans-serif;
        outline: none;
        width: 100%;
    }
    .sic-filter-input:focus { border-color: var(--accent); }

    /* Selector desplegable personalizado acorde al diseño del CRM */
    .sic-cselect {
        position: relative;
        flex: 1;
        min-width: 115px;
    }
    .sic-cselect-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 9px;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        font-size: 11.5px;
        font-weight: 500;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s;
        text-align: left;
    }
    .sic-cselect-btn:hover,
    .sic-cselect.is-open .sic-cselect-btn {
        border-color: var(--accent);
        box-shadow: 0 0 0 2px rgba(226, 35, 26, 0.1);
    }
    .sic-cselect-btn.has-filter {
        background: #fff8f7;
        border-color: #f5b4b0;
        color: var(--accent2);
        font-weight: 600;
    }
    .sic-cselect-label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }
    .sic-cselect-arrow {
        font-size: 10px;
        color: var(--text-muted);
        flex-shrink: 0;
        transition: transform 0.15s;
    }
    .sic-cselect.is-open .sic-cselect-arrow { transform: rotate(180deg); }
    .sic-cselect-menu {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        min-width: 185px;
        max-height: 220px;
        overflow-y: auto;
        z-index: 45;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 11px;
        box-shadow: 0 12px 30px rgba(24, 19, 17, 0.18);
        padding: 5px;
        scrollbar-width: thin;
        scrollbar-color: #d4cec4 transparent;
    }
    .sic-cselect.is-open .sic-cselect-menu { display: block; }
    .sic-cselect-menu.align-right { left: auto; right: 0; }
    .sic-cselect-option {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        padding: 6px 8px;
        border: none;
        background: transparent;
        border-radius: 7px;
        font-size: 11.5px;
        font-weight: 500;
        color: var(--text);
        cursor: pointer;
        text-align: left;
    }
    .sic-cselect-option:hover { background: var(--surface2); }
    .sic-cselect-option.active {
        background: #fff1f0;
        color: var(--accent2);
        font-weight: 600;
    }
    .sic-cselect-opt-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sic-cselect-opt-badge {
        padding: 1px 6px;
        border-radius: 999px;
        background: var(--surface2);
        color: var(--text-muted);
        font-size: 10px;
        font-weight: 600;
        flex-shrink: 0;
    }
    .sic-cselect-option.active .sic-cselect-opt-badge {
        background: var(--accent);
        color: #fff;
    }

    .sic-seg-btns {
        display: inline-flex;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 9px;
        overflow: hidden;
        width: 100%;
    }
    .sic-seg-btn {
        flex: 1;
        padding: 5px 6px;
        border: none;
        background: transparent;
        font-size: 11px;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        border-right: 1px solid var(--border);
        text-align: center;
    }
    .sic-seg-btn:last-child { border-right: none; }
    .sic-seg-btn.active { background: var(--accent); color: #fff; }
    .sic-link-btn {
        border: none;
        background: none;
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        padding: 2px 5px;
        border-radius: 4px;
    }
    .sic-link-btn:hover { color: var(--accent); background: rgba(226, 35, 26, 0.08); }

    /* Cuerpo de una sola columna (Lista o Detalle) */
    .sic-notif-body {
        flex: 1;
        position: relative;
        min-height: 0;
        overflow: hidden;
        background: var(--surface);
        display: flex;
        flex-direction: column;
    }
    .sic-notif-list-pane {
        flex: 1;
        overflow-y: auto;
        padding: 10px 12px;
        scrollbar-width: thin;
        scrollbar-color: #d4cec4 transparent;
    }
    .sic-notif-list-pane::-webkit-scrollbar { width: 5px; }
    .sic-notif-list-pane::-webkit-scrollbar-thumb { background: #d4cec4; border-radius: 10px; }

    .sic-notif-detail-pane {
        display: none;
        flex: 1;
        overflow-y: auto;
        padding: 12px;
        background: var(--surface2);
        scrollbar-width: thin;
    }
    .sic-notif-body.is-detail-open .sic-notif-list-pane { display: none; }
    .sic-notif-body.is-detail-open .sic-notif-detail-pane { display: block; }

    /* Grupos colapsables compactos */
    .sic-group-block { margin-bottom: 10px; }
    .sic-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        padding: 4px 8px;
        margin-bottom: 6px;
        border-radius: 6px;
        background: var(--surface2);
        font-size: 11px;
        font-weight: 600;
        color: var(--text);
        cursor: pointer;
        user-select: none;
    }
    .sic-group-header-left { display: flex; align-items: center; gap: 6px; }
    .sic-group-count {
        padding: 0 6px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--border);
        font-size: 10px;
        color: var(--text-muted);
    }

    /* Tarjetas compactas de alta legibilidad */
    .sic-notif-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 9px 11px;
        margin-bottom: 6px;
        cursor: pointer;
        transition: all 0.14s ease;
    }
    .sic-notif-card:hover {
        border-color: #cfc8bc;
        box-shadow: 0 3px 10px rgba(24, 19, 17, 0.06);
    }
    .sic-notif-card.is-unread {
        background: #fffaf9;
        border-left: 3px solid var(--accent);
    }
    .sic-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 4px;
    }
    .sic-card-badges {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
        min-width: 0;
    }
    .sic-unread-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--accent);
        display: inline-block;
        flex-shrink: 0;
    }
    .sic-chip {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 1px 7px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 600;
        max-width: 175px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sic-card-time {
        font-size: 10.5px;
        color: var(--text-muted);
        white-space: nowrap;
        flex-shrink: 0;
    }
    .sic-card-title {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text);
        line-height: 1.3;
        margin-bottom: 2px;
    }
    .sic-notif-card:not(.is-unread) .sic-card-title { font-weight: 500; }
    .sic-card-msg {
        font-size: 11.5px;
        color: var(--text-muted);
        line-height: 1.38;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .sic-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }
    .sic-card-actor {
        font-size: 10.5px;
        color: var(--text-muted);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sic-card-actions {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }
    .sic-mini-btn {
        padding: 2px 7px;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        font-size: 10.5px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .sic-mini-btn:hover { background: var(--surface2); border-color: #c9c2b6; }
    .sic-mini-btn.primary { background: #fff1f0; border-color: #fecaca; color: var(--accent2); }
    .sic-mini-btn.primary:hover { background: var(--accent); border-color: var(--accent); color: #fff; }
    .sic-mini-btn.danger:hover { background: #fef2f2; border-color: #ef4444; color: #dc2626; }

    /* ── SIMULACIONES DE MÓDULO EN VISTA DE DETALLE ── */
    .sic-detail-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
    }
    .sic-sim-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(24, 19, 17, 0.05);
    }
    .sic-sim-header {
        padding: 11px 13px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        background: var(--surface);
    }
    .sic-sim-title {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 14.5px;
        font-weight: 700;
        color: var(--text);
        line-height: 1.3;
    }
    .sic-sim-body {
        padding: 12px 13px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .sic-sim-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        padding: 10px;
        border-radius: 9px;
        background: var(--surface2);
        border: 1px solid var(--border);
    }
    .sic-sim-meta-label {
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        margin-bottom: 2px;
    }
    .sic-sim-meta-value {
        font-size: 12px;
        font-weight: 600;
        color: var(--text);
        word-break: break-word;
    }
    .sic-sim-section-lbl {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        margin-bottom: 4px;
    }
    .sic-sim-desc {
        padding: 10px 11px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
        font-size: 12.5px;
        line-height: 1.5;
        color: var(--text);
        white-space: pre-line;
    }
    .sic-sim-file-box {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 11px;
        border-radius: 9px;
        border: 1px solid var(--border);
        background: var(--surface);
    }
    .sic-sim-file-ext {
        width: 40px;
        height: 40px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        flex-shrink: 0;
    }
    .sic-detail-footer {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    /* Footer compacto del panel */
    .sic-notif-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 12px;
        background: #fff;
        border-top: 1px solid var(--border);
        font-size: 11px;
        color: var(--text-muted);
        flex-shrink: 0;
    }

    /* ── MODO COMPACTO DE NOTIFICACIONES (REDISEÑO DE FILAS Y TIRAS HORIZONTALES) ── */
    body.sic-notif-compact .sic-notif-modal {
        width: 365px;
        max-height: min(500px, calc(100vh - 76px));
        border-radius: 12px;
    }
    body.sic-notif-compact .sic-notif-header {
        padding: 9px 11px;
        gap: 6px;
    }
    body.sic-notif-compact .sic-notif-header-left {
        flex: 1;
        min-width: 0;
        overflow: hidden;
    }
    body.sic-notif-compact .sic-notif-header-title {
        font-size: 14px;
        gap: 6px;
    }
    body.sic-notif-compact .sic-hdr-btn-txt {
        display: none;
    }
    body.sic-notif-compact .sic-hdr-icon-btn {
        width: 28px;
        height: 28px;
        min-width: 28px;
        padding: 0;
        font-size: 13px;
    }
    body.sic-notif-compact .sic-hdr-close {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
    body.sic-notif-compact .sic-controls-bar {
        padding: 6px 10px;
    }
    body.sic-notif-compact .sic-cat-dropdown-btn {
        padding: 4px 9px;
        font-size: 11px;
        gap: 5px;
    }
    body.sic-notif-compact .sic-dropdown-toggle-btn {
        padding: 4px 8px;
        font-size: 11px;
    }
    body.sic-notif-compact .sic-notif-list-pane {
        padding: 7px 9px;
    }
    body.sic-notif-compact .sic-group-block {
        margin-bottom: 6px;
    }
    body.sic-notif-compact .sic-group-header {
        padding: 3px 7px;
        margin-bottom: 4px;
        font-size: 10.5px;
    }
    .sic-notif-card.is-compact-row {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 7px 9px;
        margin-bottom: 4px;
        border-radius: 9px;
    }
    .sic-compact-ico {
        position: relative;
        width: 26px;
        height: 26px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }
    .sic-compact-ico .sic-unread-dot {
        position: absolute;
        top: -2px;
        right: -2px;
        width: 7px;
        height: 7px;
        border: 1.5px solid #fff;
    }
    .sic-compact-main {
        flex: 1;
        min-width: 0;
    }
    .sic-notif-card.is-compact-row .sic-card-title {
        font-size: 12px;
        line-height: 1.25;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sic-compact-sub {
        font-size: 10.5px;
        color: var(--text-muted);
        line-height: 1.25;
        display: flex;
        align-items: center;
        gap: 5px;
        min-width: 0;
    }
    .sic-compact-sub-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
    }
    .sic-notif-card.is-compact-row .sic-card-actions {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }
    .sic-notif-card.is-compact-row .sic-mini-btn {
        padding: 3px 7px;
        font-size: 10.5px;
        border-radius: 6px;
        white-space: nowrap;
    }
    body.sic-notif-compact .sic-notif-footer {
        padding: 6px 10px;
        font-size: 10.5px;
    }
    body.sic-notif-compact .sic-toast-stack {
        width: min(355px, calc(100vw - 24px));
        gap: 6px;
    }
    .sic-toast.is-compact-toast {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 8px 10px 10px;
        border-radius: 10px;
        border-left-width: 3.5px;
        box-shadow: 0 12px 28px rgba(24, 19, 17, 0.16);
    }
    .sic-toast.is-compact-toast .sic-compact-ico {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        font-size: 14px;
    }
    .sic-toast.is-compact-toast .sic-toast-title {
        font-size: 12px;
        line-height: 1.25;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sic-toast.is-compact-toast .sic-toast-actions {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-wrap: nowrap;
        flex-shrink: 0;
    }
    .sic-toast.is-compact-toast .sic-mini-btn {
        padding: 3px 7px;
        font-size: 10.5px;
        border-radius: 6px;
        white-space: nowrap;
    }
    .sic-toast.is-compact-toast .sic-toast-queue-badge {
        font-size: 9.5px;
        padding: 1px 5px;
        flex-shrink: 0;
    }
    .sic-toast.is-compact-toast .sic-toast-next {
        font-size: 10.5px;
        padding: 3px 6px;
    }
    .sic-toast.is-compact-toast .sic-toast-close {
        font-size: 12px;
        padding: 2px 4px;
    }
    body.sic-notif-compact .sic-login-summary {
        width: min(340px, calc(100vw - 24px));
        border-radius: 12px;
    }
    body.sic-notif-compact .sic-login-summary-head {
        padding: 9px 11px;
    }
    body.sic-notif-compact .sic-login-summary-title {
        font-size: 13px;
    }
    body.sic-notif-compact .sic-login-summary-sub,
    body.sic-notif-compact .sic-login-summary-pills {
        display: none;
    }
    body.sic-notif-compact .sic-login-summary-body {
        padding: 7px 9px;
        gap: 5px;
    }
    body.sic-notif-compact .sic-login-summary-list {
        max-height: 155px;
        gap: 4px;
    }
    body.sic-notif-compact .sic-login-summary-item {
        padding: 5px 7px;
        border-radius: 7px;
        gap: 6px;
        align-items: center;
    }
    body.sic-notif-compact .sic-login-summary-item-title {
        font-size: 11.5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    body.sic-notif-compact .sic-login-summary-item-meta {
        display: none;
    }
    body.sic-notif-compact .sic-login-summary-footer {
        padding: 7px 10px 9px;
    }

    @media (max-width: 560px) {
        body.sic-notif-compact .sic-notif-modal,
        body.sic-notif-compact .sic-login-summary,
        body.sic-notif-compact .sic-toast-stack { width: auto; }
        .sic-bell-text { display: none; }
        .sic-topbar-date { display: none; }
        .sic-bell-btn { padding: 6px 10px; }
        .sic-bell-btn.is-floating {
            top: 10px;
            right: 12px;
            width: 40px;
            height: 40px;
            padding: 0;
        }
        body.sic-bell-is-floating .sic-notif-modal,
        body.sic-bell-is-floating .sic-login-summary {
            top: 58px;
        }
        body.sic-bell-is-floating .sic-toast-stack {
            top: 60px;
        }
        .sic-notif-backdrop.is-open { background: rgba(24, 19, 17, 0.45); }
        .sic-notif-modal {
            top: 56px;
            right: 10px;
            left: 10px;
            width: auto;
            max-width: none;
            max-height: calc(100vh - 70px);
        }
        .sic-login-summary {
            top: 58px;
            right: 10px;
            left: 10px;
            width: auto;
        }
    }
</style>

{{-- Stack de banners flotantes en tiempo real (una notificación nueva) --}}
<div id="sic-toast-stack" class="sic-toast-stack" aria-live="polite"></div>

{{-- Banner flotante de resumen al iniciar sesión --}}
@if($notifShowLogin && $loginTotalUnreadCount > 0)
<div class="sic-login-summary" id="sic-login-summary" role="region" aria-label="Resumen de notificaciones nuevas">
    <div class="sic-login-summary-head">
        <div style="min-width:0; flex:1">
            <div class="sic-login-summary-title">
                <span>👋 ¡Hola, {{ auth()->user()->name }}!</span>
                <span class="sic-bell-badge" id="sic-login-badge">
                    {{ $hasNewSinceLast ? $loginNewCount . ($loginNewCount === 1 ? ' nueva' : ' nuevas') : $loginTotalUnreadCount . ($loginTotalUnreadCount === 1 ? ' pendiente' : ' pendientes') }}
                </span>
            </div>
            <div class="sic-login-summary-sub" id="sic-login-sub">
                @if($hasNewSinceLast)
                    Mientras no estabas recibiste {{ $loginNewCount }} {{ $loginNewCount === 1 ? 'notificación nueva' : 'notificaciones nuevas' }}.
                @else
                    Tienes {{ $loginTotalUnreadCount }} {{ $loginTotalUnreadCount === 1 ? 'notificación sin leer' : 'notificaciones sin leer' }} de sesiones anteriores.
                @endif
            </div>
            @if($hasNewSinceLast && $loginTotalUnreadCount > $loginNewCount)
            <div class="sic-login-mode-tabs">
                <button type="button" class="sic-login-mode-btn active" id="sic-login-tab-new" onclick="SicNotif.setLoginSummaryMode('new')">
                    Nuevas ({{ $loginNewCount }})
                </button>
                <button type="button" class="sic-login-mode-btn" id="sic-login-tab-all" onclick="SicNotif.setLoginSummaryMode('all')">
                    Todas sin leer ({{ $loginTotalUnreadCount }})
                </button>
            </div>
            @endif
        </div>
        <button type="button" class="sic-hdr-close" onclick="SicNotif.dismissLoginSummary()" aria-label="Cerrar resumen">✕</button>
    </div>

    <div class="sic-login-summary-body">
        @if(count($loginCatSummary) > 0)
        <div class="sic-login-summary-pills">
            @foreach($loginCatSummary as $catSum)
                <button type="button" class="sic-summary-pill" onclick="SicNotif.dismissLoginSummary(); SicNotif.openModal({ category: '{{ $catSum['slug'] }}', status: 'unread' })">
                    <span>{{ $catSum['icon'] }}</span>
                    <span>{{ $catSum['count'] }} {{ $catSum['label'] }}</span>
                </button>
            @endforeach
        </div>
        @endif

        <div class="sic-login-summary-list" id="sic-login-summary-list">
            @foreach($loginDisplayItems as $item)
                <button type="button" class="sic-login-summary-item" onclick="SicNotif.dismissLoginSummary(); SicNotif.openModal({ selectId: {{ $item['id'] }} })">
                    <span style="font-size:14px; line-height:1.2; flex-shrink:0">{{ $item['icon'] }}</span>
                    <div style="min-width:0; flex:1">
                        <div class="sic-login-summary-item-title">{{ $item['title'] }}</div>
                        <div class="sic-login-summary-item-meta">
                            {{ $item['category_label'] }}@if(!empty($item['school_name'])) • {{ $item['school_name'] }}@endif • {{ $item['created_human'] }}
                        </div>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <div class="sic-login-summary-footer">
        <button type="button" class="btn btn-primary btn-sm" onclick="SicNotif.dismissLoginSummary(); SicNotif.openModal()">
            🔔 Ver todas en el panel
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="SicNotif.markAllRead().then(() => SicNotif.dismissLoginSummary())">
            ✓ Marcar todas leídas
        </button>
    </div>
    <div class="sic-login-progress"><div class="sic-login-progress-bar"></div></div>
</div>
@endif

{{-- Panel Flotante Compacto de Notificaciones --}}
<div id="sic-notif-modal" class="sic-notif-backdrop" role="dialog" aria-modal="true" aria-labelledby="sic-notif-modal-title">
    <div class="sic-notif-modal" onclick="event.stopPropagation()">
        <div class="sic-notif-header">
            <div class="sic-notif-header-left">
                <div class="sic-notif-header-title" id="sic-notif-modal-title">
                    <span>🔔 Notificaciones</span>
                    <span class="sic-bell-badge" id="sic-modal-unread-pill">{{ $notifInitUnread }}</span>
                </div>
            </div>
            <div class="sic-notif-header-actions">
                <button type="button" id="sic-win-notif-btn" class="sic-hdr-icon-btn is-icon-only" style="display:none" onclick="SicNotif.requestWindowsPermission()" title="Activar avisos en Windows">
                    <span id="sic-win-notif-label">🖥️</span>
                </button>
                <button type="button" id="sic-silent-toggle-btn" class="sic-hdr-icon-btn" onclick="SicNotif.toggleSilentMode()" title="Silenciar avisos y banners (seguirán llegando sin molestar)">
                    <span class="sic-hdr-btn-ico">🔕</span>
                    <span class="sic-hdr-btn-txt" id="sic-silent-toggle-label">Silenciar</span>
                </button>
                <button type="button" id="sic-settings-open-btn" class="sic-hdr-icon-btn" onclick="SicNotif.openSettingsModal()" title="Configurar notificaciones">
                    <span class="sic-hdr-btn-ico">⚙️</span>
                    <span class="sic-hdr-btn-txt">Configurar</span>
                </button>
                <button type="button" class="sic-hdr-close" onclick="SicNotif.closeModal()" aria-label="Cerrar notificaciones">✕</button>
            </div>
        </div>

        {{-- Barra compacta: solo "Todas" visible + botón desplegable de filtros --}}
        <div class="sic-controls-bar">
            <div class="sic-cat-dropdown-wrap" id="sic-cat-dropdown-wrap">
                <button type="button" class="sic-cat-dropdown-btn" id="sic-cat-dropdown-btn" onclick="SicNotif.toggleCategoryMenu()">
                    <span id="sic-cat-active-label">📋 Todas</span>
                    <span class="sic-cat-active-count" id="sic-cat-active-count">{{ $notifInitUnread }}</span>
                    <span style="font-size:10px;opacity:.85">▾</span>
                </button>
                <div class="sic-cat-dropdown-menu" id="sic-cat-dropdown-menu">
                    <button type="button" class="sic-cat-option active" data-category="all" onclick="SicNotif.setCategory('all')">
                        <span>📋 Todas</span>
                        <span class="sic-cat-opt-count" id="sic-tab-count-all">{{ $notifInitUnread }}</span>
                    </button>
                    @foreach($notifCategories as $slug => $meta)
                        <button type="button" class="sic-cat-option" data-category="{{ $slug }}" onclick="SicNotif.setCategory('{{ $slug }}')">
                            <span>{{ $meta['icon'] }} {{ $meta['label'] }}</span>
                            <span class="sic-cat-opt-count" id="sic-tab-count-{{ $slug }}">0</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="sic-controls-right">
                <button type="button" id="sic-toggle-filters-btn" class="sic-dropdown-toggle-btn" onclick="SicNotif.toggleFilters()">
                    <span>🔍 Filtros</span>
                    <span style="font-size:10px">▾</span>
                </button>
                <button type="button" class="sic-link-btn" onclick="SicNotif.markAllRead()" title="Marcar todas como leídas">✓ Leer</button>
            </div>
        </div>

        {{-- Panel desplegable de filtros con selectores propios del CRM --}}
        <div class="sic-notif-toolbar" id="sic-notif-toolbar">
            <div class="sic-toolbar-row">
                <input type="text" id="sic-filter-search" class="sic-filter-input" placeholder="{{ $notifIsAdmin ? '🔍 Buscar colegio, persona o título...' : '🔍 Buscar colegio o título...' }}" oninput="SicNotif.onSearchInput()">
            </div>
            <div class="sic-toolbar-row">
                <div class="sic-cselect" id="sic-csel-status">
                    <button type="button" class="sic-cselect-btn" onclick="SicNotif.toggleCustomSelect('status')">
                        <span class="sic-cselect-label" id="sic-csel-label-status">Estado: Todas</span>
                        <span class="sic-cselect-arrow">▾</span>
                    </button>
                    <div class="sic-cselect-menu" id="sic-csel-menu-status"></div>
                </div>
                <div class="sic-cselect" id="sic-csel-priority">
                    <button type="button" class="sic-cselect-btn" onclick="SicNotif.toggleCustomSelect('priority')">
                        <span class="sic-cselect-label" id="sic-csel-label-priority">Prioridad: Todas</span>
                        <span class="sic-cselect-arrow">▾</span>
                    </button>
                    <div class="sic-cselect-menu align-right" id="sic-csel-menu-priority"></div>
                </div>
            </div>
            <div class="sic-toolbar-row">
                <div class="sic-cselect" id="sic-csel-school">
                    <button type="button" class="sic-cselect-btn" onclick="SicNotif.toggleCustomSelect('school')">
                        <span class="sic-cselect-label" id="sic-csel-label-school">Colegios: Todos</span>
                        <span class="sic-cselect-arrow">▾</span>
                    </button>
                    <div class="sic-cselect-menu" id="sic-csel-menu-school"></div>
                </div>
            </div>
            @if($notifIsAdmin)
            <div class="sic-toolbar-row">
                <div class="sic-cselect" id="sic-csel-role">
                    <button type="button" class="sic-cselect-btn" onclick="SicNotif.toggleCustomSelect('role')">
                        <span class="sic-cselect-label" id="sic-csel-label-role">Rol: Todos</span>
                        <span class="sic-cselect-arrow">▾</span>
                    </button>
                    <div class="sic-cselect-menu" id="sic-csel-menu-role"></div>
                </div>
                <div class="sic-cselect" id="sic-csel-actor">
                    <button type="button" class="sic-cselect-btn" onclick="SicNotif.toggleCustomSelect('actor')">
                        <span class="sic-cselect-label" id="sic-csel-label-actor">Persona: Todas</span>
                        <span class="sic-cselect-arrow">▾</span>
                    </button>
                    <div class="sic-cselect-menu align-right" id="sic-csel-menu-actor"></div>
                </div>
            </div>
            @endif
            <div class="sic-toolbar-row">
                <div class="sic-seg-btns" title="Organizar notificaciones">
                    <button type="button" class="sic-seg-btn active" data-group="date" onclick="SicNotif.setGrouping('date')">📅 Fecha</button>
                    <button type="button" class="sic-seg-btn" data-group="category" onclick="SicNotif.setGrouping('category')">🗂️ Área</button>
                    <button type="button" class="sic-seg-btn" data-group="school" onclick="SicNotif.setGrouping('school')">🏫 Colegio</button>
                    @if($notifIsAdmin)
                    <button type="button" class="sic-seg-btn" data-group="actor" onclick="SicNotif.setGrouping('actor')">👤 Persona</button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Cuerpo compacto: Lista de 1 columna / Simulación de detalle por módulo --}}
        <div class="sic-notif-body" id="sic-notif-body">
            <div class="sic-notif-list-pane" id="sic-notif-list-pane"></div>
            <div class="sic-notif-detail-pane" id="sic-notif-detail-pane"></div>
        </div>

        <div class="sic-notif-footer">
            <span id="sic-notif-footer-count">Al día</span>
            <div style="display:flex;align-items:center;gap:8px">
                <button type="button" class="sic-link-btn" onclick="SicNotif.clearNotifications('read')" title="Borrar leídas">🧹 Limpiar leídas</button>
                <button type="button" class="sic-link-btn" style="color:#dc2626" onclick="SicNotif.clearNotifications('all')">🗑️ Vaciar</button>
            </div>
        </div>
    </div>
</div>

@php
    $sicNotifSettings = $notifPrefs ?? (auth()->user()?->notificationPrefs() ?? \App\Models\User::defaultNotificationPrefs());
    $sicNotifCats = \App\Models\UserNotification::CATEGORIAS;
    $sicIsSilentInit = !empty($sicNotifSettings['silent_mode']) || ($sicNotifSettings['show_floating_banner'] ?? true) === false;
@endphp

<div id="sic-notif-settings-modal" class="sic-settings-backdrop" role="dialog" aria-modal="true" aria-labelledby="sic-settings-title" onclick="if (event.target === this) SicNotif.closeSettingsModal()">
    <div class="sic-settings-modal">
        <div class="sic-settings-header">
            <div>
                <h2 id="sic-settings-title" class="sic-settings-title">⚙️ Configuración de notificaciones SIC</h2>
                <p class="sic-settings-subtitle">
                    Elige si deseas ver avisos y banners o trabajar en modo silencioso, y decide exactamente qué novedades recibir.
                </p>
            </div>
            <button type="button" class="sic-hdr-close" onclick="SicNotif.closeSettingsModal()" aria-label="Cerrar ajustes">✕</button>
        </div>

        <div class="sic-settings-body">
            <div class="sic-settings-section">
                <div class="sic-settings-sec-top">
                    <h3 class="sic-settings-section-title">Modo de avisos y banners</h3>
                </div>

                <div class="sic-mode-grid">
                    <button type="button" id="sic-mode-card-active"
                            class="sic-mode-card {{ !$sicIsSilentInit ? 'is-selected' : '' }}"
                            onclick="SicNotif.setNotificationMode(false)">
                        <span class="sic-mode-icon">🔔</span>
                        <div class="sic-mode-info">
                            <div class="sic-mode-head">
                                <strong>Avisos activos (con banners)</strong>
                                <span class="sic-mode-badge">Activo</span>
                            </div>
                            <span>Muestra tarjetas flotantes en pantalla al llegar novedades y habilita el resumen y avisos de Windows.</span>
                        </div>
                    </button>

                    <button type="button" id="sic-mode-card-silent"
                            class="sic-mode-card {{ $sicIsSilentInit ? 'is-selected' : '' }}"
                            onclick="SicNotif.setNotificationMode(true)">
                        <span class="sic-mode-icon">🔕</span>
                        <div class="sic-mode-info">
                            <div class="sic-mode-head">
                                <strong>Modo silencioso (sin banners)</strong>
                                <span class="sic-mode-badge">Activo</span>
                            </div>
                            <span>Las notificaciones llegan en silencio solo a tu bandeja. No salen banners, resumen al entrar ni avisos de Windows.</span>
                        </div>
                    </button>
                </div>

                <div id="sic-silent-pause-note" class="sic-silent-pause-note {{ $sicIsSilentInit ? 'is-visible' : '' }}">
                    🔕 Con el <strong>Modo silencioso</strong> activado, los banners, el resumen al entrar y las alertas de Windows quedan pausados automáticamente.
                </div>

                <div id="sic-delivery-subsettings" class="sic-delivery-subsettings {{ $sicIsSilentInit ? 'is-muted' : '' }}">
                    <div class="sic-setting-row">
                        <div class="sic-setting-info">
                            <strong>⏱️ Duración de cada banner</strong>
                            <span>Segundos que permanece visible cada tarjeta antes de pasar a la siguiente.</span>
                        </div>
                        <select id="sic-set-duration" class="sic-setting-select">
                            @foreach([5, 8, 12, 15] as $sec)
                                <option value="{{ $sec }}" {{ (int) ($sicNotifSettings['banner_duration_sec'] ?? 8) === $sec ? 'selected' : '' }}>{{ $sec }} segundos</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sic-setting-row">
                        <div class="sic-setting-info">
                            <strong>👋 Resumen al iniciar sesión</strong>
                            <span>Muestra un resumen automático de pendientes al entrar al CRM.</span>
                        </div>
                        <label class="sic-switch">
                            <input type="checkbox" id="sic-set-login" {{ !empty($sicNotifSettings['show_login_summary']) ? 'checked' : '' }}>
                            <span class="sic-switch-slider"></span>
                        </label>
                    </div>

                    <div class="sic-setting-row">
                        <div class="sic-setting-info">
                            <strong>🖥️ Notificaciones de escritorio en Windows</strong>
                            <span>Muestra alertas del sistema aunque tengas Chrome minimizado o en otra pestaña.</span>
                        </div>
                        <label class="sic-switch">
                            <input type="checkbox" id="sic-set-windows" {{ !empty($sicNotifSettings['windows_desktop']) ? 'checked' : '' }}>
                            <span class="sic-switch-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="sic-settings-rows">
                    <div class="sic-setting-row">
                        <div class="sic-setting-info">
                            <strong>🚨 Solo guardar prioridad Alta o Urgente</strong>
                            <span>Filtra avisos de prioridad Baja o Normal para mantener tu bandeja enfocada en lo crítico.</span>
                        </div>
                        <label class="sic-switch">
                            <input type="checkbox" id="sic-set-priority" {{ !empty($sicNotifSettings['only_high_priority']) ? 'checked' : '' }}>
                            <span class="sic-switch-slider"></span>
                        </label>
                    </div>

                    <div class="sic-setting-row">
                        <div class="sic-setting-info">
                            <strong>🗜️ Modo compacto</strong>
                            <span>Reduce el tamaño del panel, las tarjetas y los banners flotantes para que sean más discretos manteniendo lectura clara y los botones Detalles e Ir.</span>
                        </div>
                        <label class="sic-switch">
                            <input type="checkbox" id="sic-set-compact" {{ !empty($sicNotifSettings['compact_mode']) ? 'checked' : '' }}>
                            <span class="sic-switch-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="sic-settings-section">
                <div class="sic-settings-sec-top">
                    <h3 class="sic-settings-section-title">Temas que me interesa recibir</h3>
                    <div style="display:flex; gap:6px;">
                        <button type="button" class="sic-mini-chip-btn" onclick="SicNotif.setAllCategories(true)">Activar todos</button>
                        <button type="button" class="sic-mini-chip-btn" onclick="SicNotif.setAllCategories(false)">Desactivar todos</button>
                    </div>
                </div>
                <div class="sic-cats-grid">
                    @foreach($sicNotifCats as $catSlug => $catMeta)
                        <div class="sic-cat-card">
                            <div class="sic-cat-card-title">
                                <span class="sic-cat-pill-ico" style="background:{{ $catMeta['bg'] }}; color:{{ $catMeta['color'] }};">{{ $catMeta['icon'] }}</span>
                                <span>{{ $catMeta['label'] }}</span>
                            </div>
                            <label class="sic-switch">
                                <input type="checkbox" class="sic-set-cat" data-category="{{ $catSlug }}"
                                    {{ ($sicNotifSettings['categories'][$catSlug] ?? true) ? 'checked' : '' }}>
                                <span class="sic-switch-slider"></span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="sic-settings-footer">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span id="sic-settings-status" style="font-size:12.5px; font-weight:600; color:#047857;"></span>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <button type="button" class="sic-settings-btn-ghost" onclick="SicNotif.closeSettingsModal()">Cancelar</button>
                <button type="button" id="sic-settings-save-btn" class="sic-settings-btn-primary" onclick="SicNotif.saveSettings()">💾 Guardar ajustes</button>
            </div>
        </div>
    </div>
</div>

<script>
window.SicNotif = (function () {
    const routes = {
        index:     @json(route('notifications.index')),
        poll:      @json(route('notifications.poll')),
        readAll:   @json(route('notifications.read-all')),
        clear:     @json(route('notifications.clear')),
        settings:  @json(route('notifications.settings')),
        dashboard: @json(route('dashboard')),
        readOne:   (id) => @json(url('/notificaciones')) + '/' + id + '/leer',
        delOne:    (id) => @json(url('/notificaciones')) + '/' + id,
    };
    const winLogoUrl = @json(asset('icons/logo-sic.png') . '?v=3');
    const initialPrefs = @json($sicNotifSettings);

    const isAdmin = @json($notifIsAdmin);
    const systemUsers = @json($notifSystemUsers);
    const roleLabels = @json($notifRoleLabels);
    const loginNewItems = @json($newSinceLastCollection->take(12)->values()->all());
    const loginAllUnreadItems = @json($loginAllUnreadPreview);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const CATEGORY_META = {
        all: { icon: '📋', label: 'Todas' },
        @foreach($notifCategories as $slug => $meta)
        @json($slug): { icon: @json($meta['icon']), label: @json($meta['label']) },
        @endforeach
    };

    const SHOWN_KEY = 'sic_shown_banner_ids';
    function loadShownBannerIds() {
        try {
            const raw = JSON.parse(sessionStorage.getItem(SHOWN_KEY) || '[]');
            return new Set(Array.isArray(raw) ? raw.map(Number) : []);
        } catch (e) {
            return new Set();
        }
    }
    function persistShownBannerIds(set) {
        try {
            sessionStorage.setItem(SHOWN_KEY, JSON.stringify(Array.from(set).slice(-120)));
        } catch (e) {}
    }

    const shownBannerIds = loadShownBannerIds();

    const state = {
        prefs: Object.assign({
            silent_mode: false,
            compact_mode: false,
            show_floating_banner: true,
            banner_duration_sec: 8,
            show_login_summary: true,
            windows_desktop: true,
            only_high_priority: false,
            categories: {},
        }, initialPrefs || {}),
        unreadCount: {{ $notifInitUnread }},
        latestId: {{ $notifInitLatest }},
        category: 'all',
        status: 'all',
        priority: 'all',
        schoolId: 'all',
        actorRole: 'all',
        actorName: 'all',
        search: '',
        grouping: 'date',
        allItems: @json($notifInitItems),
        items: [],
        selectedId: null,
        isOpen: false,
        catMenuOpen: false,
        filtersOpen: false,
        openSelectKey: null,
        collapsedGroups: {},
        loadedFromServer: {{ count($notifInitItems) > 0 ? 'true' : 'false' }},
        pollingInFlight: false,
    };

    if (state.prefs?.compact_mode && document.body) {
        document.body.classList.add('sic-notif-compact');
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    async function api(url, method = 'GET', body = null) {
        const opts = {
            method,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        };
        if (body !== null) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const res = await fetch(url, opts);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    function updateBell(unreadCount) {
        state.unreadCount = Number(unreadCount || 0);
        const btn   = document.getElementById('sic-notif-bell-btn');
        const badge = document.getElementById('sic-notif-bell-badge');
        const pill  = document.getElementById('sic-modal-unread-pill');

        if (badge) {
            badge.textContent = state.unreadCount > 99 ? '99+' : String(state.unreadCount);
            badge.classList.toggle('is-zero', state.unreadCount === 0);
        }
        if (btn) {
            btn.classList.toggle('has-unread', state.unreadCount > 0);
        }
        if (pill) {
            pill.textContent = state.unreadCount > 99 ? '99+' : String(state.unreadCount);
            pill.classList.toggle('is-zero', state.unreadCount === 0);
        }
    }

    function syncActiveCategoryButton(unreadByCat, totalUnread) {
        const meta = CATEGORY_META[state.category] || CATEGORY_META.all;
        const lbl = document.getElementById('sic-cat-active-label');
        const cnt = document.getElementById('sic-cat-active-count');
        if (lbl) lbl.textContent = `${meta.icon} ${meta.label}`;
        if (cnt) {
            const val = state.category === 'all' ? totalUnread : (unreadByCat[state.category] || 0);
            cnt.textContent = String(val);
            cnt.style.display = val > 0 ? 'inline-flex' : 'none';
        }
    }

    // ── SELECTORES PERSONALIZADOS CRM + CASCADA ROL -> PERSONA ──
    function renderCustomSelect(key, currentVal, defaultLabel, options) {
        const wrap = document.getElementById(`sic-csel-${key}`);
        const menu = document.getElementById(`sic-csel-menu-${key}`);
        const lbl  = document.getElementById(`sic-csel-label-${key}`);
        const btn  = wrap?.querySelector('.sic-cselect-btn');
        if (!wrap || !menu || !lbl || !btn) return;

        const selectedOpt = options.find(o => String(o.value) === String(currentVal));
        lbl.textContent = selectedOpt && String(currentVal) !== 'all' ? selectedOpt.selectedText : defaultLabel;
        btn.classList.toggle('has-filter', String(currentVal) !== 'all');

        menu.innerHTML = options.map(opt => {
            const isActive = String(opt.value) === String(currentVal);
            return `
                <button type="button"
                        class="sic-cselect-option ${isActive ? 'active' : ''}"
                        data-csel-key="${escapeHtml(key)}"
                        data-csel-val="${escapeHtml(opt.value)}"
                        onclick="SicNotif.selectFilterOption(this.dataset.cselKey, this.dataset.cselVal)">
                    <span class="sic-cselect-opt-text">${escapeHtml(opt.label)}</span>
                    ${opt.badge !== undefined && opt.badge !== null ? `<span class="sic-cselect-opt-badge">${escapeHtml(opt.badge)}</span>` : ''}
                </button>`;
        }).join('');
    }

    function toggleCustomSelect(key) {
        closeCategoryMenu();
        const next = state.openSelectKey === key ? null : key;
        closeAllCustomSelects();
        if (next) {
            state.openSelectKey = next;
            document.getElementById(`sic-csel-${next}`)?.classList.add('is-open');
        }
    }

    function closeAllCustomSelects() {
        state.openSelectKey = null;
        document.querySelectorAll('.sic-cselect.is-open').forEach(el => el.classList.remove('is-open'));
    }

    function selectFilterOption(key, value) {
        closeAllCustomSelects();
        if (key === 'status') state.status = value;
        if (key === 'priority') state.priority = value;
        if (key === 'school') state.schoolId = value;
        if (key === 'role') {
            state.actorRole = value;
            // Si la persona elegida no pertenece al rol seleccionado, restablecer al instante
            if (value !== 'all' && state.actorName !== 'all') {
                const validActors = getFilteredActorsForRole(value).map(a => a.name);
                if (!validActors.includes(state.actorName)) {
                    state.actorName = 'all';
                }
            }
        }
        if (key === 'actor') state.actorName = value;

        closeDetailView();
        computeAndApplyLocalCounts();
        applyLocalFilters();
    }

    function getFilteredActorsForRole(roleSlug) {
        const countsByActor = new Map();
        state.allItems.forEach((item) => {
            const name = item.actor_name || 'Sistema SIC';
            const role = item.actor_role || 'sistema';
            if (!countsByActor.has(name)) {
                countsByActor.set(name, {
                    name,
                    role,
                    roleLabel: item.actor_role_label || 'Sistema',
                    total: 0,
                    unread: 0,
                });
            }
            const entry = countsByActor.get(name);
            entry.total++;
            if (item.is_unread) entry.unread++;
        });

        // Combinar con usuarios del sistema para que al filtrar por Rol aparezcan todas las personas de ese rol
        if (Array.isArray(systemUsers)) {
            systemUsers.forEach((u) => {
                if (!countsByActor.has(u.name)) {
                    countsByActor.set(u.name, {
                        name: u.name,
                        role: u.role || 'sistema',
                        roleLabel: u.role_label || '',
                        total: 0,
                        unread: 0,
                    });
                }
            });
        }

        let list = Array.from(countsByActor.values());
        if (roleSlug && roleSlug !== 'all') {
            list = list.filter(a => a.role === roleSlug);
        }
        return list.sort((a, b) => (b.unread - a.unread) || (b.total - a.total) || a.name.localeCompare(b.name));
    }

    function computeAndApplyLocalCounts() {
        const unreadByCat = {};
        const schoolMap = new Map();
        let totalUnread = 0;

        state.allItems.forEach((item) => {
            if (item.is_unread) {
                totalUnread++;
                unreadByCat[item.category] = (unreadByCat[item.category] || 0) + 1;
            }
            if (item.school_id && item.school_name) {
                if (!schoolMap.has(item.school_id)) {
                    schoolMap.set(item.school_id, { id: item.school_id, name: item.school_name, total: 0, unread: 0 });
                }
                const entry = schoolMap.get(item.school_id);
                entry.total++;
                if (item.is_unread) entry.unread++;
            }
        });

        updateBell(totalUnread);

        document.querySelectorAll('#sic-cat-dropdown-menu .sic-cat-option').forEach((btn) => {
            const cat = btn.dataset.category;
            const el = document.getElementById(`sic-tab-count-${cat}`);
            if (!el) return;
            const val = cat === 'all' ? totalUnread : (unreadByCat[cat] || 0);
            el.textContent = String(val);
            el.style.display = val > 0 ? 'inline-flex' : 'none';
        });

        syncActiveCategoryButton(unreadByCat, totalUnread);

        // Renderizar selectores personalizados del CRM
        renderCustomSelect('status', state.status, 'Estado: Todas', [
            { value: 'all',    label: 'Todas (leídas y nuevas)', selectedText: 'Estado: Todas',   badge: state.allItems.length },
            { value: 'unread', label: 'Solo sin leer',           selectedText: 'Solo sin leer',   badge: totalUnread },
            { value: 'read',   label: 'Solo leídas',             selectedText: 'Solo leídas',     badge: Math.max(0, state.allItems.length - totalUnread) },
        ]);

        const impCount = state.allItems.filter(i => ['high', 'urgent'].includes(i.priority)).length;
        renderCustomSelect('priority', state.priority, 'Prioridad: Todas', [
            { value: 'all',       label: 'Cualquier prioridad', selectedText: 'Prioridad: Todas', badge: state.allItems.length },
            { value: 'important', label: 'Alta / Urgente',      selectedText: 'Alta / Urgente',   badge: impCount },
        ]);

        const schools = Array.from(schoolMap.values()).sort((a, b) => (b.unread - a.unread) || a.name.localeCompare(b.name));
        renderCustomSelect('school', state.schoolId, 'Colegios: Todos', [
            { value: 'all', label: 'Todos los colegios', selectedText: 'Colegios: Todos', badge: schools.length },
            ...schools.map(s => ({
                value: String(s.id),
                label: s.name,
                selectedText: s.name,
                badge: s.unread > 0 ? `${s.unread} nuevas` : s.total,
            })),
        ]);

        if (isAdmin) {
            const roleCountMap = {};
            state.allItems.forEach((i) => {
                const r = i.actor_role || 'sistema';
                roleCountMap[r] = (roleCountMap[r] || 0) + 1;
            });

            const roleOptions = [
                { value: 'all', label: 'Todos los roles', selectedText: 'Rol: Todos', badge: state.allItems.length },
                ...Object.entries(roleLabels).map(([slug, label]) => ({
                    value: slug,
                    label: label,
                    selectedText: label,
                    badge: roleCountMap[slug] || 0,
                })),
                { value: 'sistema', label: 'Sistema (recordatorios)', selectedText: 'Sistema', badge: roleCountMap['sistema'] || 0 },
            ];
            renderCustomSelect('role', state.actorRole, 'Rol: Todos', roleOptions);

            const actors = getFilteredActorsForRole(state.actorRole);
            renderCustomSelect('actor', state.actorName, 'Persona: Todas', [
                { value: 'all', label: 'Todas las personas', selectedText: 'Persona: Todas', badge: actors.length },
                ...actors.map(a => ({
                    value: a.name,
                    label: state.actorRole === 'all' && a.roleLabel ? `${a.name} · ${a.roleLabel}` : a.name,
                    selectedText: a.name,
                    badge: a.unread > 0 ? `${a.unread} nuevas` : a.total,
                })),
            ]);
        }
    }

    function applyLocalFilters() {
        const term = state.search.trim().toLowerCase();
        state.items = state.allItems.filter((item) => {
            if (state.category !== 'all' && item.category !== state.category) return false;
            if (state.status === 'unread' && !item.is_unread) return false;
            if (state.status === 'read' && item.is_unread) return false;
            if (state.priority === 'important' && !['high', 'urgent'].includes(item.priority)) return false;
            if (state.schoolId !== 'all' && String(item.school_id) !== String(state.schoolId)) return false;
            if (isAdmin && state.actorRole !== 'all' && (item.actor_role || 'sistema') !== state.actorRole) return false;
            if (isAdmin && state.actorName !== 'all' && (item.actor_name || '') !== state.actorName) return false;
            if (term !== '') {
                const hay = [item.title, item.message, item.school_name, item.actor_name, item.actor_role_label].join(' ').toLowerCase();
                if (!hay.includes(term)) return false;
            }
            return true;
        });

        const footerLbl = document.getElementById('sic-notif-footer-count');
        if (footerLbl) {
            footerLbl.textContent = state.items.length === 1
                ? '1 notificación en esta vista'
                : `${state.items.length} notificaciones en esta vista`;
        }

        renderList();
        if (state.selectedId && state.items.some(i => i.id === state.selectedId)) {
            renderDetail();
        } else if (state.selectedId) {
            closeDetailView();
        }
    }

    function updateCategoryCounts(counts) {
        if (!counts) return;
        let totalUnread = 0;
        const unreadByCat = {};
        Object.keys(counts).forEach((slug) => {
            const c = counts[slug];
            const u = Number(c.unread || 0);
            unreadByCat[slug] = u;
            totalUnread += u;
            const el = document.getElementById(`sic-tab-count-${slug}`);
            if (el) {
                el.textContent = String(u);
                el.style.display = u > 0 ? 'inline-flex' : 'none';
            }
        });
        const allEl = document.getElementById('sic-tab-count-all');
        if (allEl) {
            allEl.textContent = String(totalUnread);
            allEl.style.display = totalUnread > 0 ? 'inline-flex' : 'none';
        }
        syncActiveCategoryButton(unreadByCat, totalUnread);
    }

    async function syncAllFromServer(openDetailId = null) {
        try {
            const data = await api(`${routes.index}?limit=100`);
            state.allItems = data.notifications || [];
            state.loadedFromServer = true;
            if (data.latest_id) state.latestId = Math.max(state.latestId, Number(data.latest_id));
            computeAndApplyLocalCounts();
            applyLocalFilters();

            if (openDetailId && state.allItems.some(i => i.id === openDetailId)) {
                await selectNotification(openDetailId, true);
            }
        } catch (e) {}
    }

    function groupItems(items) {
        const groups = new Map();
        items.forEach((item) => {
            let key = item.date_group || 'Recientes';
            let label = key;
            if (state.grouping === 'category') {
                key = item.category;
                label = `${item.category_icon} ${item.category_label}`;
            } else if (state.grouping === 'school') {
                key = item.school_id ? `school_${item.school_id}` : 'general';
                label = item.school_name ? `🏫 ${item.school_name}` : '🌐 Generales';
            } else if (state.grouping === 'actor') {
                key = `actor_${item.actor_name || 'sistema'}`;
                const roleTxt = item.actor_role_label ? ` (${item.actor_role_label})` : '';
                label = `👤 ${item.actor_name || 'Sistema SIC'}${roleTxt}`;
            }
            if (!groups.has(key)) groups.set(key, { key, label, items: [] });
            groups.get(key).items.push(item);
        });
        return Array.from(groups.values());
    }

    function renderList() {
        const pane = document.getElementById('sic-notif-list-pane');
        if (!pane) return;

        if (!state.items.length) {
            pane.innerHTML = `
                <div style="text-align:center; padding:38px 16px; color:var(--text-muted)">
                    <div style="font-size:32px; margin-bottom:8px">✨</div>
                    <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:14.5px; font-weight:600; color:var(--text); margin-bottom:3px">
                        Todo al día
                    </div>
                    <div style="font-size:12px">No hay notificaciones para este filtro.</div>
                </div>`;
            return;
        }

        const isCompact = Boolean(state.prefs && state.prefs.compact_mode);
        const groups = groupItems(state.items);
        pane.innerHTML = groups.map(group => {
            const collapsed = !!state.collapsedGroups[group.key];
            const unreadInGroup = group.items.filter(i => i.is_unread).length;
            const cardsHtml = collapsed ? '' : group.items.map(item => {
                const unreadClass = item.is_unread ? 'is-unread' : '';
                if (isCompact) {
                    const metaParts = [];
                    if (item.priority === 'urgent') metaParts.push('🔴 Urgente');
                    if (item.school_name) metaParts.push(item.school_name);
                    metaParts.push(item.created_human);

                    return `
                        <div class="sic-notif-card is-compact-row ${unreadClass}" onclick="SicNotif.selectNotification(${item.id}, true)">
                            <span class="sic-compact-ico" style="background:${item.category_bg};color:${item.category_color}" title="${escapeHtml(item.category_label)}">
                                ${item.icon}
                                ${item.is_unread ? '<span class="sic-unread-dot" title="Sin leer"></span>' : ''}
                            </span>
                            <div class="sic-compact-main">
                                <div class="sic-card-title" title="${escapeHtml(item.title)}">${escapeHtml(item.title)}</div>
                                <div class="sic-compact-sub">
                                    <span class="sic-compact-sub-text">${escapeHtml(metaParts.join(' • '))}</span>
                                </div>
                            </div>
                            <div class="sic-card-actions" onclick="event.stopPropagation()">
                                <button type="button" class="sic-mini-btn primary" onclick="SicNotif.selectNotification(${item.id}, true)">Detalles</button>
                                ${item.action_url ? `<button type="button" class="sic-mini-btn" onclick="SicNotif.goToAction(${item.id})">Ir ↗</button>` : ''}
                            </div>
                        </div>`;
                }

                const prioBadge = (item.priority === 'urgent' || item.priority === 'high')
                    ? `<span class="sic-chip" style="background:${item.priority_bg};color:${item.priority_color}">${escapeHtml(item.priority_label)}</span>`
                    : '';
                const schoolChip = item.school_name
                    ? `<span class="sic-chip" style="background:#f3f4f6;color:#374151" title="${escapeHtml(item.school_name)}">🏫 ${escapeHtml(item.school_name)}</span>`
                    : '';
                const actorLine = isAdmin && item.actor_role_label
                    ? `Por ${item.actor_name} (${item.actor_role_label})`
                    : `Por ${item.actor_name}`;

                return `
                    <div class="sic-notif-card ${unreadClass}" onclick="SicNotif.selectNotification(${item.id}, true)">
                        <div class="sic-card-top">
                            <div class="sic-card-badges">
                                ${item.is_unread ? '<span class="sic-unread-dot" title="Sin leer"></span>' : ''}
                                <span class="sic-chip" style="background:${item.category_bg};color:${item.category_color}">
                                    ${item.icon} ${escapeHtml(item.category_label)}
                                </span>
                                ${prioBadge}
                                ${schoolChip}
                            </div>
                            <span class="sic-card-time" title="${escapeHtml(item.created_exact)}">${escapeHtml(item.created_human)}</span>
                        </div>
                        <div class="sic-card-title">${escapeHtml(item.title)}</div>
                        <div class="sic-card-msg">${escapeHtml(item.message)}</div>
                        <div class="sic-card-footer">
                            <span class="sic-card-actor">${escapeHtml(actorLine)}</span>
                            <div class="sic-card-actions" onclick="event.stopPropagation()">
                                <button type="button" class="sic-mini-btn primary" onclick="SicNotif.selectNotification(${item.id}, true)">Detalles</button>
                                ${item.action_url ? `<button type="button" class="sic-mini-btn" onclick="SicNotif.goToAction(${item.id})">Ir ↗</button>` : ''}
                                <button type="button" class="sic-mini-btn" onclick="SicNotif.toggleRead(${item.id})" title="${item.is_unread ? 'Marcar leída' : 'Marcar no leída'}">
                                    ${item.is_unread ? '✓' : '↺'}
                                </button>
                                <button type="button" class="sic-mini-btn danger" onclick="SicNotif.removeOne(${item.id})" title="Eliminar">🗑️</button>
                            </div>
                        </div>
                    </div>`;
            }).join('');

            return `
                <div class="sic-group-block">
                    <div class="sic-group-header" data-group-key="${escapeHtml(group.key)}" onclick="SicNotif.toggleGroup(this.dataset.groupKey)">
                        <div class="sic-group-header-left">
                            <span>${collapsed ? '▸' : '▾'}</span>
                            <span>${escapeHtml(group.label)}</span>
                            <span class="sic-group-count">${group.items.length}${unreadInGroup > 0 ? ` • ${unreadInGroup} nuevas` : ''}</span>
                        </div>
                    </div>
                    ${cardsHtml}
                </div>`;
        }).join('');
    }

    // ── RENDERIZADORES DE SIMULACIÓN POR MÓDULO ──
    function renderTareaSimulation(item) {
        const d = item.details || {};
        const titulo = d['Título'] || d['Tarea'] || item.title;
        const aud = d['Audiencia'] || 'Equipo SI';

        return `
            <div class="sic-sim-card">
                <div class="sic-sim-header">
                    <div>
                        <div class="sic-sim-title">✅ ${escapeHtml(titulo)}</div>
                        <div style="margin-top:5px">
                            <span style="display:inline-block;font-size:10.5px;font-weight:600;padding:2px 8px;border-radius:999px;color:#4f46e5;background:#eef2ff">
                                👥 ${escapeHtml(aud)}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="sic-sim-body">
                    <div class="sic-sim-meta-grid">
                        <div>
                            <div class="sic-sim-meta-label">Colegios asignados</div>
                            <div class="sic-sim-meta-value">${escapeHtml(d['Colegios incluidos'] || '—')} colegio(s)</div>
                        </div>
                        <div>
                            <div class="sic-sim-meta-label">Publicado por</div>
                            <div class="sic-sim-meta-value">${escapeHtml(item.actor_name)}</div>
                        </div>
                    </div>
                    <div>
                        <div class="sic-sim-section-lbl">Instrucciones de la tarea</div>
                        <div class="sic-sim-desc">${escapeHtml(d['Descripción'] || item.message)}</div>
                    </div>
                </div>
            </div>`;
    }

    function renderTableroSimulation(item) {
        const d = item.details || {};
        const titulo = d['Título'] || item.title;
        const vigencia = d['Vigencia'] || 'Sin fecha límite';
        const tieneArchivo = d['Adjunto'] && d['Adjunto'] !== 'Sin archivo adjunto';
        const tieneEnlace = d['Enlace'] && d['Enlace'] !== 'Sin enlace externo';

        return `
            <div class="sic-sim-card">
                <div class="sic-sim-header">
                    <div>
                        <div class="sic-sim-title">📌 ${escapeHtml(titulo)}</div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:3px">Tablero SI • Para: <strong>${escapeHtml(d['Audiencia'] || 'Equipo')}</strong></div>
                    </div>
                    <span class="badge ${vigencia === 'Sin fecha límite' ? 'badge-success' : 'badge-warning'}">
                        ${vigencia === 'Sin fecha límite' ? '✓ Vigente' : '⏳ ' + escapeHtml(vigencia)}
                    </span>
                </div>
                <div class="sic-sim-body">
                    <div class="sic-sim-desc">${escapeHtml(d['Contenido'] || item.message)}</div>
                    ${tieneArchivo ? `
                        <div class="sic-sim-file-box">
                            <div class="sic-sim-file-ext" style="background:#fef2f2;color:#dc2626">📎</div>
                            <div style="min-width:0">
                                <div class="sic-sim-meta-label">Archivo adjunto</div>
                                <div class="sic-sim-meta-value">${escapeHtml(d['Adjunto'])}</div>
                            </div>
                        </div>` : ''}
                    ${tieneEnlace ? `
                        <div style="padding:8px 10px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;font-size:12px;color:#1d4ed8;word-break:break-all">
                            🔗 ${escapeHtml(d['Enlace'])}
                        </div>` : ''}
                    <div style="font-size:11px;color:var(--text-muted);display:flex;justify-content:space-between;padding-top:6px;border-top:1px solid var(--border)">
                        <span>Publicado por ${escapeHtml(item.actor_name)}</span>
                        <span>${escapeHtml(item.created_exact)}</span>
                    </div>
                </div>
            </div>`;
    }

    function renderManualesSimulation(item) {
        const d = item.details || {};
        const archivo = d['Archivo'] || '';
        const upper = archivo.toUpperCase();
        const ext = upper.includes('XLS') ? ['XLSX', '#15803d', '#f0fdf4']
            : (upper.includes('DOC') ? ['DOCX', '#1d4ed8', '#eff6ff']
            : (upper.includes('PPT') ? ['PPTX', '#c2410c', '#fff7ed']
            : ['PDF', '#dc2626', '#fef2f2']));

        return `
            <div class="sic-sim-card">
                <div class="sic-sim-header">
                    <div>
                        <div class="sic-sim-title">📚 ${escapeHtml(d['Recurso'] || item.title)}</div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                            Seguimiento → <strong>${escapeHtml(d['Plataforma'] || 'Plataforma')}</strong> • ${escapeHtml(d['Tipo'] || 'Recurso')}
                        </div>
                    </div>
                </div>
                <div class="sic-sim-body">
                    <div class="sic-sim-file-box">
                        <div class="sic-sim-file-ext" style="background:${ext[2]};color:${ext[1]};border:1px solid ${ext[1]}33">${ext[0]}</div>
                        <div style="min-width:0;flex:1">
                            <div class="sic-sim-meta-value">${escapeHtml(archivo || 'Documento disponible')}</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Audiencia: ${escapeHtml(d['Audiencia'] || 'General')}</div>
                        </div>
                    </div>
                    <div>
                        <div class="sic-sim-section-lbl">Descripción</div>
                        <div class="sic-sim-desc">${escapeHtml(d['Descripción'] || item.message)}</div>
                    </div>
                </div>
            </div>`;
    }

    function renderGeneralSimulation(item) {
        const d = item.details || {};
        const entries = Object.entries(d);
        const esProxima = item.event_type === 'capacitacion_proxima';
        const subHeader = esProxima ? 'Recordatorio de Capacitación / Sesión' : item.category_label;

        return `
            <div class="sic-sim-card">
                <div class="sic-sim-header">
                    <div>
                        <div class="sic-sim-title">${item.icon} ${escapeHtml(item.title)}</div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                            ${escapeHtml(subHeader)}${item.school_name ? ' • 🏫 ' + escapeHtml(item.school_name) : ''}
                        </div>
                    </div>
                    ${esProxima ? '<span class="badge badge-danger">⏰ Próxima</span>' : ''}
                </div>
                <div class="sic-sim-body">
                    <div class="sic-sim-desc">${escapeHtml(item.message)}</div>
                    ${entries.length ? `
                        <div class="sic-sim-meta-grid">
                            ${entries.map(([k, v]) => `
                                <div>
                                    <div class="sic-sim-meta-label">${escapeHtml(k)}</div>
                                    <div class="sic-sim-meta-value">${escapeHtml(Array.isArray(v) ? v.join(', ') : v)}</div>
                                </div>`).join('')}
                        </div>` : ''}
                    <div style="font-size:11px;color:var(--text-muted);display:flex;justify-content:space-between">
                        <span>Por ${escapeHtml(item.actor_name)}</span>
                        <span>${escapeHtml(item.created_exact)}</span>
                    </div>
                </div>
            </div>`;
    }

    function renderDetail() {
        const pane = document.getElementById('sic-notif-detail-pane');
        if (!pane) return;

        const item = state.items.find(i => i.id === state.selectedId) || state.allItems.find(i => i.id === state.selectedId);
        if (!item) {
            closeDetailView();
            return;
        }

        let simHtml = '';
        if (item.category === 'tareas') {
            simHtml = renderTareaSimulation(item);
        } else if (item.category === 'tablero') {
            simHtml = renderTableroSimulation(item);
        } else if (item.category === 'manuales') {
            simHtml = renderManualesSimulation(item);
        } else {
            simHtml = renderGeneralSimulation(item);
        }

        pane.innerHTML = `
            <div class="sic-detail-nav">
                <button type="button" class="sic-mini-btn" onclick="SicNotif.closeDetailView()">← Volver a la lista</button>
                <span class="sic-chip" style="background:${item.is_unread ? '#fee2e2' : '#d1fae5'};color:${item.is_unread ? '#991b1b' : '#065f46'}">
                    ${item.is_unread ? '● Sin leer' : '✓ Leída'}
                </span>
            </div>
            ${simHtml}
            <div class="sic-detail-footer">
                ${item.action_url ? `
                    <button type="button" class="btn btn-primary btn-sm" onclick="SicNotif.goToAction(${item.id})">
                        ${escapeHtml(item.action_label || 'Ir al módulo')} →
                    </button>` : ''}
                <button type="button" class="btn btn-secondary btn-sm" onclick="SicNotif.toggleRead(${item.id})">
                    ${item.is_unread ? '✓ Leída' : '↺ No leída'}
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="SicNotif.removeOne(${item.id})">
                    🗑️ Eliminar
                </button>
            </div>`;
    }

    async function selectNotification(id, openDetail = true) {
        state.selectedId = id;
        closeCategoryMenu();
        closeAllCustomSelects();
        if (openDetail) {
            document.getElementById('sic-notif-body')?.classList.add('is-detail-open');
        }

        const item = state.allItems.find(i => i.id === id);
        if (item && item.is_unread) {
            markAsRead(id, false);
        } else {
            renderList();
            renderDetail();
        }
    }

    function closeDetailView() {
        state.selectedId = null;
        document.getElementById('sic-notif-body')?.classList.remove('is-detail-open');
    }

    async function markAsRead(id, toggle = false) {
        const item = state.allItems.find(i => i.id === id);
        if (!item) return;

        item.is_unread = toggle ? !item.is_unread : false;
        computeAndApplyLocalCounts();
        applyLocalFilters();

        try {
            const data = await api(routes.readOne(id), 'PATCH', { toggle });
            item.is_unread = !!data.is_unread;
            updateBell(data.unread_count);
            updateCategoryCounts(data.category_counts);
        } catch (e) {}
    }

    async function toggleRead(id) {
        await markAsRead(id, true);
    }

    async function goToAction(id) {
        const item = state.allItems.find(i => i.id === id);
        if (!item || !item.action_url) return;
        if (item.is_unread) {
            item.is_unread = false;
            computeAndApplyLocalCounts();
            try { await api(routes.readOne(id), 'PATCH', { toggle: false }); } catch (e) {}
        }
        window.location.href = item.action_url;
    }

    async function removeOne(id) {
        state.allItems = state.allItems.filter(i => i.id !== id);
        if (state.selectedId === id) {
            closeDetailView();
        }
        computeAndApplyLocalCounts();
        applyLocalFilters();

        try {
            const data = await api(routes.delOne(id), 'DELETE');
            updateBell(data.unread_count);
            updateCategoryCounts(data.category_counts);
        } catch (e) {}
    }

    function matchesBulkScope(i) {
        if (state.category !== 'all' && i.category !== state.category) return false;
        if (state.schoolId !== 'all' && String(i.school_id) !== String(state.schoolId)) return false;
        if (isAdmin && state.actorRole !== 'all' && (i.actor_role || 'sistema') !== state.actorRole) return false;
        if (isAdmin && state.actorName !== 'all' && (i.actor_name || '') !== state.actorName) return false;
        return true;
    }

    async function markAllRead() {
        state.allItems.forEach(i => {
            if (matchesBulkScope(i)) i.is_unread = false;
        });
        computeAndApplyLocalCounts();
        applyLocalFilters();

        const payload = {
            category: state.category,
            school_id: state.schoolId,
        };
        if (isAdmin) {
            payload.actor_role = state.actorRole;
            payload.actor_name = state.actorName;
        }

        try {
            const data = await api(routes.readAll, 'POST', payload);
            updateBell(data.unread_count);
            updateCategoryCounts(data.category_counts);
        } catch (e) {}
    }

    async function clearNotifications(mode) {
        state.allItems = state.allItems.filter(i => {
            if (!matchesBulkScope(i)) return true;
            return mode === 'read' ? i.is_unread : false;
        });
        closeDetailView();
        computeAndApplyLocalCounts();
        applyLocalFilters();

        const params = new URLSearchParams({ mode });
        if (state.category !== 'all') params.set('category', state.category);
        if (state.schoolId !== 'all') params.set('school_id', state.schoolId);
        if (isAdmin) {
            if (state.actorRole !== 'all') params.set('actor_role', state.actorRole);
            if (state.actorName !== 'all') params.set('actor_name', state.actorName);
        }

        try {
            const data = await api(`${routes.clear}?${params.toString()}`, 'DELETE');
            updateBell(data.unread_count);
            updateCategoryCounts(data.category_counts);
        } catch (e) {}
    }

    function toggleCategoryMenu() {
        closeAllCustomSelects();
        state.catMenuOpen = !state.catMenuOpen;
        document.getElementById('sic-cat-dropdown-menu')?.classList.toggle('is-open', state.catMenuOpen);
    }

    function closeCategoryMenu() {
        state.catMenuOpen = false;
        document.getElementById('sic-cat-dropdown-menu')?.classList.remove('is-open');
    }

    function toggleFilters() {
        closeCategoryMenu();
        closeAllCustomSelects();
        state.filtersOpen = !state.filtersOpen;
        document.getElementById('sic-notif-toolbar')?.classList.toggle('is-open', state.filtersOpen);
        document.getElementById('sic-toggle-filters-btn')?.classList.toggle('is-active', state.filtersOpen);
    }

    function openModal(opts = {}) {
        if (state.isOpen && !opts.category && !opts.status && !opts.selectId) {
            closeModal();
            return;
        }
        if (opts.category) state.category = opts.category;
        if (opts.status) state.status = opts.status;

        document.querySelectorAll('#sic-cat-dropdown-menu .sic-cat-option').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.category === state.category);
        });

        document.getElementById('sic-notif-modal')?.classList.add('is-open');
        state.isOpen = true;
        closeCategoryMenu();
        closeAllCustomSelects();
        refreshWindowsButtonState();

        computeAndApplyLocalCounts();
        applyLocalFilters();
        if (opts.selectId && state.allItems.some(i => i.id === opts.selectId)) {
            selectNotification(opts.selectId, true);
        } else if (!state.loadedFromServer) {
            syncAllFromServer(opts.selectId || null);
        }
    }

    function closeModal() {
        closeCategoryMenu();
        closeAllCustomSelects();
        document.getElementById('sic-notif-modal')?.classList.remove('is-open');
        closeDetailView();
        state.isOpen = false;
    }

    function setCategory(cat) {
        state.category = cat;
        closeCategoryMenu();
        closeDetailView();
        document.querySelectorAll('#sic-cat-dropdown-menu .sic-cat-option').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.category === cat);
        });
        computeAndApplyLocalCounts();
        applyLocalFilters();
    }

    function setGrouping(groupMode) {
        state.grouping = groupMode;
        document.querySelectorAll('.sic-seg-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.group === groupMode);
        });
        renderList();
    }

    function toggleGroup(key) {
        state.collapsedGroups[key] = !state.collapsedGroups[key];
        renderList();
    }

    function onSearchInput() {
        state.search = document.getElementById('sic-filter-search')?.value || '';
        closeDetailView();
        applyLocalFilters();
    }

    function setLoginSummaryMode(mode) {
        const listEl = document.getElementById('sic-login-summary-list');
        const tabNew = document.getElementById('sic-login-tab-new');
        const tabAll = document.getElementById('sic-login-tab-all');
        if (!listEl) return;

        tabNew?.classList.toggle('active', mode === 'new');
        tabAll?.classList.toggle('active', mode === 'all');

        const source = mode === 'new' ? loginNewItems : loginAllUnreadItems;
        listEl.innerHTML = source.map(item => `
            <button type="button" class="sic-login-summary-item" onclick="SicNotif.dismissLoginSummary(); SicNotif.openModal({ selectId: ${item.id} })">
                <span style="font-size:14px; line-height:1.2; flex-shrink:0">${item.icon}</span>
                <div style="min-width:0; flex:1">
                    <div class="sic-login-summary-item-title">${escapeHtml(item.title)}</div>
                    <div class="sic-login-summary-item-meta">
                        ${escapeHtml(item.category_label)}${item.school_name ? ' • ' + escapeHtml(item.school_name) : ''} • ${escapeHtml(item.created_human)}
                    </div>
                </div>
            </button>`).join('');
    }

    let loginSummaryTimer = null;
    function dismissLoginSummary() {
        clearTimeout(loginSummaryTimer);
        const el = document.getElementById('sic-login-summary');
        if (!el || el.classList.contains('is-leaving')) return;
        el.classList.add('is-leaving');
        setTimeout(() => el.remove(), 280);
    }

    // ── BANNERS FLOTANTES EN SESIÓN CON COLA SECUENCIAL (1 A LA VEZ, SIN DUPLICADOS) ──
    function getBannerDurationMs() {
        const sec = parseInt(state.prefs?.banner_duration_sec, 10);
        return [5, 8, 12, 15].includes(sec) ? sec * 1000 : 8000;
    }
    const bannerQueue = [];
    let activeBannerState = null;

    function updateActiveBannerQueueUI() {
        if (!activeBannerState || !activeBannerState.el) return;
        const badgeEl = activeBannerState.el.querySelector('[data-toast-queue-badge]');
        const nextBtn = activeBannerState.el.querySelector('[data-toast-next-btn]');
        const pending = bannerQueue.length;

        if (badgeEl) {
            if (pending > 0) {
                badgeEl.textContent = `+${pending} por ver`;
                badgeEl.title = `${pending} notificación(es) pendiente(s) por ver`;
                badgeEl.style.display = 'inline-flex';
            } else {
                badgeEl.style.display = 'none';
            }
        }
        if (nextBtn) {
            nextBtn.style.display = pending > 0 ? 'inline-flex' : 'none';
        }
    }

    function scheduleBannerAutoAdvance(bannerState) {
        if (!bannerState || bannerState.closing || bannerState.isPaused) return;
        clearTimeout(bannerState.timer);
        bannerState.startedAt = Date.now();
        bannerState.timer = setTimeout(() => {
            bannerState.advanceNext(240);
        }, Math.max(200, bannerState.remainingMs));
    }

    function syncBannerPauseState(bannerState) {
        if (!bannerState || bannerState.closing) return;
        const shouldPause = !!(bannerState.hoverPaused || bannerState.hiddenPaused);
        bannerState.el?.classList.toggle('is-tab-hidden', !!bannerState.hiddenPaused);

        if (shouldPause && !bannerState.isPaused) {
            bannerState.isPaused = true;
            clearTimeout(bannerState.timer);
            if (bannerState.startedAt !== null) {
                const elapsed = Date.now() - bannerState.startedAt;
                bannerState.remainingMs = Math.max(0, bannerState.remainingMs - elapsed);
                bannerState.startedAt = null;
            }
        } else if (!shouldPause && bannerState.isPaused) {
            bannerState.isPaused = false;
            scheduleBannerAutoAdvance(bannerState);
        }
    }

    function renderNextBanner(item) {
        const stack = document.getElementById('sic-toast-stack');
        if (!stack || !item || !item.id) return;

        if (activeBannerState) {
            clearTimeout(activeBannerState.timer);
            activeBannerState.el?.remove();
            activeBannerState = null;
        }
        stack.innerHTML = '';

        const id = Number(item.id);
        const isCompact = Boolean(state.prefs && state.prefs.compact_mode);
        const toast = document.createElement('div');
        toast.className = isCompact ? 'sic-toast is-compact-toast' : 'sic-toast';
        toast.dataset.notifId = String(id);
        toast.style.borderLeftColor = item.category_color || 'var(--accent)';

        if (isCompact) {
            toast.innerHTML = `
                <span class="sic-compact-ico" style="background:${item.category_bg};color:${item.category_color}" title="${escapeHtml(item.category_label)}">
                    ${item.icon}
                </span>
                <div class="sic-compact-main">
                    <div class="sic-toast-title" title="${escapeHtml(item.title)}">${escapeHtml(item.title)}</div>
                    <div class="sic-compact-sub">
                        <span class="sic-compact-sub-text">${escapeHtml(item.school_name || item.category_label)}</span>
                        <span class="sic-toast-queue-badge" data-toast-queue-badge style="display:none"></span>
                    </div>
                </div>
                <div class="sic-toast-actions">
                    <button type="button" class="sic-mini-btn primary">Detalles</button>
                    ${item.action_url ? `<a href="${escapeHtml(item.action_url)}" class="sic-mini-btn">Ir ↗</a>` : ''}
                    <button type="button" class="sic-toast-next" data-toast-next-btn style="display:none" title="Ver siguiente notificación">→</button>
                    <button type="button" class="sic-toast-close" title="Cerrar avisos flotantes" aria-label="Cerrar avisos flotantes">✕</button>
                </div>
                <div class="sic-toast-progress"><div class="sic-toast-progress-bar"></div></div>
            `;
        } else {
            toast.innerHTML = `
                <div class="sic-toast-head">
                    <div class="sic-toast-meta">
                        <span class="sic-chip" style="background:${item.category_bg};color:${item.category_color}">
                            ${item.icon} ${escapeHtml(item.category_label)}
                        </span>
                        ${item.school_name ? `<span class="sic-chip" style="background:#f3f4f6;color:#374151">🏫 ${escapeHtml(item.school_name)}</span>` : ''}
                    </div>
                    <div class="sic-toast-controls">
                        <span class="sic-toast-queue-badge" data-toast-queue-badge style="display:none"></span>
                        <button type="button" class="sic-toast-next" data-toast-next-btn style="display:none" title="Ver siguiente notificación">Siguiente →</button>
                        <button type="button" class="sic-toast-close" title="Cerrar avisos flotantes" aria-label="Cerrar avisos flotantes">✕</button>
                    </div>
                </div>
                <div class="sic-toast-title">${escapeHtml(item.title)}</div>
                <div class="sic-toast-msg">${escapeHtml(item.message)}</div>
                <div class="sic-toast-actions">
                    <button type="button" class="sic-mini-btn primary">👁️ Ver detalles</button>
                    ${item.action_url ? `<a href="${escapeHtml(item.action_url)}" class="sic-mini-btn">Ir →</a>` : ''}
                </div>
                <div class="sic-toast-progress"><div class="sic-toast-progress-bar"></div></div>
            `;
        }

        const progBar = toast.querySelector('.sic-toast-progress-bar');
        if (progBar) {
            progBar.style.animationDuration = getBannerDurationMs() + 'ms';
        }

        const startsHidden = !!document.hidden;
        const bannerState = {
            id,
            el: toast,
            timer: null,
            remainingMs: getBannerDurationMs(),
            startedAt: null,
            hoverPaused: false,
            hiddenPaused: startsHidden,
            isPaused: startsHidden,
            closing: false,
            dismissedAll: false,
            advanceNext: (leaveDelayMs = 240) => {
                if (bannerState.closing) return;
                bannerState.closing = true;
                clearTimeout(bannerState.timer);
                toast.classList.add('is-leaving');
                setTimeout(() => {
                    toast.remove();
                    if (activeBannerState === bannerState) {
                        activeBannerState = null;
                    }
                    if (!bannerState.dismissedAll && bannerQueue.length > 0) {
                        const nextItem = bannerQueue.shift();
                        renderNextBanner(nextItem);
                    }
                }, leaveDelayMs);
            },
            dismissAll: () => {
                bannerQueue.length = 0;
                bannerState.dismissedAll = true;
                if (bannerState.closing) return;
                bannerState.closing = true;
                clearTimeout(bannerState.timer);
                toast.classList.add('is-leaving');
                setTimeout(() => {
                    toast.remove();
                    if (activeBannerState === bannerState) {
                        activeBannerState = null;
                    }
                }, 220);
            },
        };

        if (startsHidden) {
            toast.classList.add('is-tab-hidden');
        }

        toast.querySelector('.sic-toast-next')?.addEventListener('click', () => {
            bannerState.advanceNext(160);
        });
        toast.querySelector('.sic-toast-close')?.addEventListener('click', () => {
            bannerState.dismissAll();
        });
        toast.querySelector('.sic-mini-btn.primary')?.addEventListener('click', () => {
            bannerState.dismissAll();
            openModal({ selectId: id });
        });

        toast.addEventListener('mouseenter', () => {
            bannerState.hoverPaused = true;
            syncBannerPauseState(bannerState);
        });
        toast.addEventListener('mouseleave', () => {
            bannerState.hoverPaused = false;
            syncBannerPauseState(bannerState);
        });

        activeBannerState = bannerState;
        stack.appendChild(toast);
        updateActiveBannerQueueUI();
        if (!startsHidden) {
            scheduleBannerAutoAdvance(bannerState);
        }
    }

    function showFloatingBanner(item) {
        if (!state.prefs || state.prefs.silent_mode || state.prefs.show_floating_banner === false) return;

        const stack = document.getElementById('sic-toast-stack');
        if (!stack || !item || !item.id) return;

        const id = Number(item.id);
        if (shownBannerIds.has(id)) return;
        if (activeBannerState && Number(activeBannerState.id) === id && !activeBannerState.dismissedAll) return;
        if (bannerQueue.some(queued => Number(queued.id) === id)) return;

        shownBannerIds.add(id);
        persistShownBannerIds(shownBannerIds);

        if (!activeBannerState || activeBannerState.dismissedAll) {
            renderNextBanner(item);
        } else {
            bannerQueue.push(item);
            updateActiveBannerQueueUI();
        }
    }

    // ── NOTIFICACIONES NATIVAS DE WINDOWS (SOLO SI NO ESTÁN ACTIVAS) ──
    function refreshWindowsButtonState() {
        const btn = document.getElementById('sic-win-notif-btn');
        const lbl = document.getElementById('sic-win-notif-label');
        if (!btn || !lbl) return;

        if (!('Notification' in window) || Notification.permission === 'granted') {
            btn.style.display = 'none';
            return;
        }

        btn.style.display = 'inline-flex';
        btn.classList.remove('win-default', 'win-denied');
        lbl.textContent = '🖥️';
        if (Notification.permission === 'denied') {
            btn.classList.add('win-denied');
            btn.title = 'Notificaciones de Windows bloqueadas en el navegador (haz clic para ver cómo activarlas)';
        } else {
            btn.classList.add('win-default');
            btn.title = 'Activar notificaciones de escritorio en Windows';
        }
    }

    async function showWindowsNotification(title, body, url = routes.dashboard, tag = 'sic-notif') {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;

        if (!state.prefs || state.prefs.silent_mode || state.prefs.windows_desktop === false) {
            return;
        }
        const options = {
            body: body || '',
            icon: winLogoUrl,
            badge: winLogoUrl,
            tag,
            renotify: true,
            data: { url },
        };

        try {
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.getRegistration();
                if (reg && reg.showNotification) {
                    await reg.showNotification(title, options);
                    return;
                }
            }
        } catch (e) {}

        try {
            const n = new Notification(title, options);
            n.onclick = () => {
                window.focus();
                if (url) window.location.href = url;
                n.close();
            };
        } catch (e) {}
    }

    async function requestWindowsPermission() {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'denied') {
            alert('Las notificaciones están bloqueadas en tu navegador. Habilítalas desde el ícono de candado junto a la dirección del sitio.');
            return;
        }

        const result = await Notification.requestPermission();
        refreshWindowsButtonState();
        if (result === 'granted') {
            showWindowsNotification(
                'MacmillanSI CRM — Avisos activados',
                'Recibirás alertas de Windows cuando lleguen recordatorios, tareas, comunicados o recursos.',
                window.location.href,
                'sic-win-enabled'
            );
        }
    }

    function mergeIncomingNotifications(list) {
        if (!Array.isArray(list) || !list.length) return [];
        const existingIds = new Set(state.allItems.map(i => Number(i.id)));
        const added = list.filter(n => !existingIds.has(Number(n.id)));
        if (added.length) {
            state.allItems = [...added.slice().reverse(), ...state.allItems].slice(0, 100);
            computeAndApplyLocalCounts();
            if (state.isOpen) applyLocalFilters();
        }
        return added;
    }

    async function poll() {
        if (state.pollingInFlight) return;
        state.pollingInFlight = true;
        try {
            const data = await api(`${routes.poll}?since_id=${state.latestId}`);
            updateBell(data.unread_count);
            if (data.category_counts) updateCategoryCounts(data.category_counts);

            if (data.latest_id) {
                state.latestId = Math.max(state.latestId, Number(data.latest_id));
            }

            const nuevas = Array.isArray(data.new_notifications) ? data.new_notifications : [];
            const added = mergeIncomingNotifications(nuevas);

            if (added.length > 0 && !state.prefs?.silent_mode) {
                if (state.prefs?.show_floating_banner !== false) {
                    added.forEach(n => showFloatingBanner(n));
                }

                if (state.prefs?.windows_desktop !== false) {
                    if (added.length > 3) {
                        showWindowsNotification(
                            `MacmillanSI CRM: ${added.length} notificaciones nuevas`,
                            added.slice(0, 2).map(n => n.title).join(' • '),
                            routes.dashboard,
                            'sic-batch-' + Date.now()
                        );
                    } else {
                        added.forEach(n => showWindowsNotification(
                            n.title,
                            n.message,
                            n.action_url || routes.dashboard,
                            `sic-notif-${n.id}`
                        ));
                    }
                }
            }
        } catch (e) {
        } finally {
            state.pollingInFlight = false;
        }
    }

    function setupFloatingBell() {
        const slot = document.getElementById('sic-bell-slot');
        const btn = document.getElementById('sic-notif-bell-btn');
        if (!slot || !btn) return;

        let isFloating = false;
        let activeAnim = null;
        let rafPending = false;
        let slotOutByObserver = false;

        const prefersReducedMotion = () => window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const runFlipTransition = (firstRect, lastRect, enteringFloat) => {
            if (typeof btn.animate !== 'function' || prefersReducedMotion()) return;
            if (!firstRect.width || !firstRect.height || !lastRect.width || !lastRect.height) return;

            if (activeAnim) {
                activeAnim.cancel();
                activeAnim = null;
            }

            const firstCx = firstRect.left + firstRect.width / 2;
            const firstCy = firstRect.top + firstRect.height / 2;
            const lastCx = lastRect.left + lastRect.width / 2;
            const lastCy = lastRect.top + lastRect.height / 2;

            const dx = Math.max(-140, Math.min(140, firstCx - lastCx));
            const dy = Math.max(-90, Math.min(90, firstCy - lastCy));
            const startScale = enteringFloat ? 1.18 : 0.72;

            activeAnim = btn.animate(
                [
                    {
                        transform: `translate(${dx}px, ${dy}px) scale(${startScale})`,
                        opacity: 0.78,
                    },
                    {
                        transform: 'translate(0px, 0px) scale(1)',
                        opacity: 1,
                    },
                ],
                {
                    duration: enteringFloat ? 260 : 300,
                    easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                }
            );

            activeAnim.onfinish = () => { activeAnim = null; };
            activeAnim.oncancel = () => { activeAnim = null; };
        };

        const setFloating = (nextFloating, animate = true) => {
            if (nextFloating === isFloating) return;

            const firstRect = btn.getBoundingClientRect();
            isFloating = nextFloating;

            if (nextFloating) {
                const slotRect = slot.getBoundingClientRect();
                if (slotRect.width > 0 && slotRect.height > 0) {
                    slot.style.width = Math.ceil(slotRect.width) + 'px';
                    slot.style.height = Math.ceil(slotRect.height) + 'px';
                }
                btn.classList.add('is-floating');
                document.body.classList.add('sic-bell-is-floating');
            } else {
                btn.classList.remove('is-floating');
                document.body.classList.remove('sic-bell-is-floating');
                slot.style.width = '';
                slot.style.height = '';
            }

            const lastRect = btn.getBoundingClientRect();
            if (animate) {
                runFlipTransition(firstRect, lastRect, nextFloating);
            } else if (activeAnim) {
                activeAnim.cancel();
                activeAnim = null;
            }
        };

        const evaluateFloatingState = (animate = true) => {
            const slotRect = slot.getBoundingClientRect();
            const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;

            let nextFloating = isFloating;
            if (scrollTop <= 6) {
                nextFloating = false;
            } else if (!isFloating) {
                nextFloating = slotOutByObserver || slotRect.bottom < 24;
            } else {
                nextFloating = !(slotRect.top >= 6 && slotRect.bottom <= window.innerHeight);
            }

            if (nextFloating !== isFloating) {
                setFloating(nextFloating, animate);
            }
        };

        const scheduleEvaluate = (animate = true) => {
            if (rafPending) return;
            rafPending = true;
            requestAnimationFrame(() => {
                rafPending = false;
                evaluateFloatingState(animate);
            });
        };

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(
                (entries) => {
                    const entry = entries[0];
                    if (!entry) return;
                    if (entry.intersectionRatio < 0.2 && entry.boundingClientRect.top < 12) {
                        slotOutByObserver = true;
                    } else if (entry.intersectionRatio >= 0.92 || entry.boundingClientRect.top >= 6) {
                        slotOutByObserver = false;
                    }
                    scheduleEvaluate(true);
                },
                { threshold: [0, 0.2, 0.92, 1] }
            );
            observer.observe(slot);
        }

        window.addEventListener('scroll', () => scheduleEvaluate(true), { passive: true });
        window.addEventListener('resize', () => scheduleEvaluate(false), { passive: true });

        evaluateFloatingState(false);
        requestAnimationFrame(() => evaluateFloatingState(false));
    }

    function init() {
        if (window.__sicNotifBooted) return;
        window.__sicNotifBooted = true;

        const nowMs = Date.now();
        state.allItems.forEach((item) => {
            const createdMs = Date.parse(item.created_at || '') || 0;
            const isJustCreated = createdMs > 0 && (nowMs - createdMs) <= 6000;
            if (!isJustCreated) {
                shownBannerIds.add(Number(item.id));
            }
        });
        persistShownBannerIds(shownBannerIds);

        @if(!$notifShowLogin)
        if (!state.prefs?.silent_mode && state.prefs?.show_floating_banner !== false) {
            state.allItems.forEach((item) => {
                const createdMs = Date.parse(item.created_at || '') || 0;
                if (item.is_unread && createdMs > 0 && (nowMs - createdMs) <= 6000 && !shownBannerIds.has(Number(item.id))) {
                    showFloatingBanner(item);
                }
            });
        }
        @endif

        const backdrop = document.getElementById('sic-notif-modal');
        backdrop?.addEventListener('click', (e) => {
            if (e.target === backdrop) closeModal();
        });
        document.addEventListener('click', (e) => {
            if (state.catMenuOpen && !document.getElementById('sic-cat-dropdown-wrap')?.contains(e.target)) {
                closeCategoryMenu();
            }
            if (state.openSelectKey && !e.target.closest('.sic-cselect')) {
                closeAllCustomSelects();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (state.openSelectKey) closeAllCustomSelects();
                else if (state.catMenuOpen) closeCategoryMenu();
                else if (state.isOpen) closeModal();
            }
        });

        setupFloatingBell();
        computeAndApplyLocalCounts();
        refreshWindowsButtonState();

        const loginBox = document.getElementById('sic-login-summary');
        if (loginBox) {
            loginSummaryTimer = setTimeout(dismissLoginSummary, 22000);
            loginBox.addEventListener('mouseenter', () => clearTimeout(loginSummaryTimer));
            loginBox.addEventListener('mouseleave', () => {
                loginSummaryTimer = setTimeout(dismissLoginSummary, 8000);
            });
        }

        @if($notifShowLogin && $loginTotalUnreadCount > 0)
            if (!state.prefs?.silent_mode && state.prefs?.windows_desktop !== false && state.prefs?.show_login_summary !== false) {
                setTimeout(() => {
                    showWindowsNotification(
                        @json($hasNewSinceLast
                            ? "MacmillanSI CRM — Recibiste {$loginNewCount} notificación(es) nueva(s)"
                            : "MacmillanSI CRM — Tienes {$loginTotalUnreadCount} notificación(es) pendiente(s)"),
                        @json(collect($loginCatSummary)->map(fn($c) => "{$c['icon']} {$c['count']} {$c['label']}")->implode(' • ')),
                        routes.dashboard,
                        'sic-login-summary'
                    );
                }, 900);
            }
        @endif

        startBackgroundPolling();
    }

    function startBackgroundPolling() {
        let usingWorker = false;

        try {
            if (typeof Worker !== 'undefined' && typeof Blob !== 'undefined' && window.URL?.createObjectURL) {
                const workerSrc = 'setInterval(function(){ postMessage("tick"); }, 4000);';
                const blobUrl = URL.createObjectURL(new Blob([workerSrc], { type: 'application/javascript' }));
                const worker = new Worker(blobUrl);
                let revoked = false;
                worker.onmessage = () => {
                    if (!revoked) {
                        revoked = true;
                        URL.revokeObjectURL(blobUrl);
                    }
                    poll();
                };
                worker.onerror = () => {
                    if (!revoked) {
                        revoked = true;
                        URL.revokeObjectURL(blobUrl);
                    }
                    try { worker.terminate(); } catch (e) {}
                    if (!window.__sicNotifPollTimer) {
                        window.__sicNotifPollTimer = setInterval(poll, 4000);
                    }
                };
                window.__sicNotifWorker = worker;
                usingWorker = true;
            }
        } catch (e) {
            usingWorker = false;
        }

        if (!usingWorker) {
            window.__sicNotifPollTimer = setInterval(poll, 4000);
        }

        document.addEventListener('visibilitychange', () => {
            if (activeBannerState) {
                activeBannerState.hiddenPaused = !!document.hidden;
                syncBannerPauseState(activeBannerState);
            }
            if (!document.hidden) poll();
        });
        window.addEventListener('focus', () => poll());
    }

    let modalSilentChoice = Boolean(state.prefs && (state.prefs.silent_mode || state.prefs.show_floating_banner === false));

    function applyPrefsUI() {
        const silent = Boolean(state.prefs && (state.prefs.silent_mode || state.prefs.show_floating_banner === false));
        const compact = Boolean(state.prefs && state.prefs.compact_mode);
        if (document.body) {
            document.body.classList.toggle('sic-notif-compact', compact);
        }
        const silentBtn = document.getElementById('sic-silent-toggle-btn');
        const silentLbl = document.getElementById('sic-silent-toggle-label');
        if (silentBtn) {
            silentBtn.classList.toggle('is-silent', silent);
            silentBtn.title = silent
                ? 'Modo silencioso activo: haz clic para activar avisos con banners'
                : 'Activar modo silencioso (solo bandeja, sin banners)';
        }
        if (silentLbl) {
            silentLbl.textContent = silent ? 'Silenciado' : 'Silenciar';
        }

        if (state.loadedFromServer || state.allItems.length) {
            applyLocalFilters();
        }

        const bellContainers = new Set();
        const badgeEl = document.getElementById('sic-notif-bell-badge');
        if (badgeEl?.parentElement) bellContainers.add(badgeEl.parentElement);
        document.querySelectorAll('#sic-notif-bell-btn, [data-sic-notif-bell], [onclick*="SicNotif.openModal"]').forEach(el => bellContainers.add(el));

        bellContainers.forEach((bellBtn, idx) => {
            let tag = bellBtn.querySelector('.sic-bell-silent-tag');
            if (silent) {
                if (!tag) {
                    tag = document.createElement('span');
                    tag.className = 'sic-bell-silent-tag';
                    tag.textContent = idx === 0 ? '🔕 Silencioso' : '🔕';
                    bellBtn.appendChild(tag);
                }
            } else if (tag) {
                tag.remove();
            }
        });

        if (silent) {
            bannerQueue.length = 0;
            if (activeBannerState) {
                activeBannerState.dismissAll();
            }
            const stack = document.getElementById('sic-toast-stack');
            if (stack) stack.innerHTML = '';
            dismissLoginSummary();
        }
    }

    function setNotificationMode(isSilent) {
        modalSilentChoice = Boolean(isSilent);
        const cardActive = document.getElementById('sic-mode-card-active');
        const cardSilent = document.getElementById('sic-mode-card-silent');
        const subBox = document.getElementById('sic-delivery-subsettings');
        const note = document.getElementById('sic-silent-pause-note');

        if (cardActive) cardActive.classList.toggle('is-selected', !modalSilentChoice);
        if (cardSilent) cardSilent.classList.toggle('is-selected', modalSilentChoice);
        if (subBox) subBox.classList.toggle('is-muted', modalSilentChoice);
        if (note) note.classList.toggle('is-visible', modalSilentChoice);
    }

    function setAllCategories(enabled) {
        document.querySelectorAll('.sic-set-cat').forEach(el => {
            el.checked = Boolean(enabled);
        });
    }

    function syncSettingsFormFromPrefs() {
        const p = state.prefs || {};
        const setChk = (id, val) => { const el = document.getElementById(id); if (el) el.checked = Boolean(val); };
        setNotificationMode(Boolean(p.silent_mode || p.show_floating_banner === false));
        setChk('sic-set-login', p.show_login_summary !== false);
        setChk('sic-set-windows', p.windows_desktop !== false);
        setChk('sic-set-priority', p.only_high_priority);
        setChk('sic-set-compact', p.compact_mode);

        const dur = document.getElementById('sic-set-duration');
        if (dur) dur.value = String([5, 8, 12, 15].includes(Number(p.banner_duration_sec)) ? p.banner_duration_sec : 8);

        const cats = p.categories || {};
        document.querySelectorAll('.sic-set-cat').forEach(el => {
            const slug = el.dataset.category;
            el.checked = cats[slug] !== false;
        });

        const st = document.getElementById('sic-settings-status');
        if (st) st.textContent = '';
    }

    function openSettingsModal() {
        syncSettingsFormFromPrefs();
        const modal = document.getElementById('sic-notif-settings-modal');
        if (modal) modal.classList.add('open');
    }

    function closeSettingsModal() {
        const modal = document.getElementById('sic-notif-settings-modal');
        if (modal) modal.classList.remove('open');
    }

    async function persistSettings(patch) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch(routes.settings, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(patch),
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (data && data.settings) {
            state.prefs = data.settings;
            applyPrefsUI();
        }
        return data;
    }

    async function toggleSilentMode() {
        try {
            const nextSilent = !(state.prefs && (state.prefs.silent_mode || state.prefs.show_floating_banner === false));
            await persistSettings({ silent_mode: nextSilent, show_floating_banner: !nextSilent });
        } catch (err) {
            console.error('Error toggling silent mode:', err);
        }
    }

    async function saveSettings() {
        const btn = document.getElementById('sic-settings-save-btn');
        const status = document.getElementById('sic-settings-status');
        if (btn) btn.disabled = true;
        if (status) {
            status.style.color = 'var(--text-muted)';
            status.textContent = 'Guardando...';
        }

        const categories = {};
        document.querySelectorAll('.sic-set-cat').forEach(el => {
            if (el.dataset.category) categories[el.dataset.category] = Boolean(el.checked);
        });

        const payload = {
            silent_mode:          modalSilentChoice,
            compact_mode:         Boolean(document.getElementById('sic-set-compact')?.checked),
            show_floating_banner: !modalSilentChoice,
            banner_duration_sec:  Number(document.getElementById('sic-set-duration')?.value || 8),
            show_login_summary:   Boolean(document.getElementById('sic-set-login')?.checked),
            windows_desktop:      Boolean(document.getElementById('sic-set-windows')?.checked),
            only_high_priority:   Boolean(document.getElementById('sic-set-priority')?.checked),
            categories,
        };

        try {
            await persistSettings(payload);
            if (status) {
                status.style.color = '#047857';
                status.textContent = '✓ Ajustes guardados correctamente.';
            }
            setTimeout(() => closeSettingsModal(), 350);
        } catch (err) {
            console.error('Error saving settings:', err);
            if (status) {
                status.style.color = '#dc2626';
                status.textContent = 'No se pudo guardar. Intenta de nuevo.';
            }
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const settingsModal = document.getElementById('sic-notif-settings-modal');
            if (settingsModal && settingsModal.classList.contains('open')) {
                closeSettingsModal();
            }
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        init();
        applyPrefsUI();
    });

    return {
        openModal,
        closeModal,
        toggleCategoryMenu,
        toggleFilters,
        toggleCustomSelect,
        selectFilterOption,
        setCategory,
        setGrouping,
        toggleGroup,
        onSearchInput,
        setLoginSummaryMode,
        selectNotification,
        closeDetailView,
        toggleRead,
        goToAction,
        removeOne,
        markAllRead,
        clearNotifications,
        dismissLoginSummary,
        requestWindowsPermission,
        toggleSilentMode,
        setNotificationMode,
        setAllCategories,
        openSettingsModal,
        closeSettingsModal,
        saveSettings,
    };
})();
</script>
