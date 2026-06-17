<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MacmillanSI CRM</title>
    {{-- PWA --}}
    <meta name="theme-color" content="#e94560">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MacmillanSI">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap');

        :root {
            --primary: #1a1a2e;
            --secondary: #16213e;
            --accent: #e94560;
            --accent2: #0f3460;
            --surface: #ffffff;
            --surface2: #f4f6f9;
            --text: #1a1a2e;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface2);
            color: var(--text);
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            left: 0; top: 0;
            width: 260px;
            height: 100vh;
            background: var(--primary);
            display: flex;
            flex-direction: column;
            z-index: 100;
            padding: 0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-brand {
            padding: 24px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .sidebar-brand span {
            color: var(--accent);
        }

        .sidebar-brand p {
            font-size: 11px;
            color: rgba(255,255,255,0.4);
            margin-top: 2px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 16px;
            overflow-y: auto;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.3);
            padding: 0 12px;
            margin: 16px 0 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: all 0.2s;
            margin-bottom: 2px;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .nav-item.active {
            background: var(--accent);
            color: #fff;
        }

        .nav-icon { font-size: 16px; width: 20px; text-align: center; }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
        }

        .user-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 600; color: #fff;
        }

        .user-name { font-size: 13px; color: #fff; font-weight: 500; }
        .user-role { font-size: 11px; color: rgba(255,255,255,0.4); }

        .logout-btn {
            display: block;
            text-align: center;
            margin-top: 8px;
            padding: 8px;
            border-radius: 8px;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
        }

        .logout-btn:hover { color: var(--accent); background: rgba(233,69,96,0.1); }

        /* MAIN */
        .main {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
        }

        .content {
            padding: 32px;
            flex: 1;
        }

        /* CARDS */
        .card {
            background: var(--surface);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 16px;
            font-weight: 600;
        }

        .card-body { padding: 24px; }

        /* BUTTONS */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #d63651; color: #fff; }
        .btn-secondary { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-danger { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
        .btn-danger:hover { background: var(--danger); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* TABLE */
        .table { width: 100%; border-collapse: collapse; }
        .table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            background: var(--surface2);
        }
        .table td {
            padding: 14px 16px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .table tr:last-child td { border-bottom: none; }
        .table tr:hover td { background: #fafafa; }

        /* BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }
        .badge-info    { background: #dbeafe; color: #1e40af; }
        .badge-gray    { background: #f3f4f6; color: #374151; }

        /* FORMS */
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--text);
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            background: var(--surface);
            transition: border-color 0.2s;
            outline: none;
        }
        .form-control:focus { border-color: var(--accent); }

        /* ALERTS */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            transition: opacity 0.5s ease, max-height 0.5s ease, margin 0.5s ease, padding 0.5s ease;
            overflow: hidden;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* GRID */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }

        /* ── HAMBURGER ── */
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
            width: 36px; height: 36px;
            cursor: pointer;
            border: none;
            background: none;
            padding: 5px;
            border-radius: 6px;
            flex-shrink: 0;
        }
        .hamburger span {
            display: block;
            width: 20px; height: 2px;
            background: var(--text);
            border-radius: 2px;
            transition: all 0.25s;
        }
        .hamburger.is-open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .hamburger.is-open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .hamburger.is-open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        /* ── SIDEBAR OVERLAY ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 99;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }

        /* ── TABLE SCROLL WRAPPER ── */
        .table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* ════════════════════════════════
           RESPONSIVE — TABLET / MOBILE
           ════════════════════════════════ */
        @media (max-width: 900px) {
            .hamburger { display: flex; }

            .sidebar {
                transform: translateX(-260px);
                z-index: 200;
            }
            .sidebar.sidebar-open {
                transform: translateX(0);
                box-shadow: 6px 0 32px rgba(0,0,0,0.35);
            }
            .sidebar-overlay.sidebar-open { display: block; }

            .main { margin-left: 0 !important; }

            .topbar {
                padding: 12px 16px;
                position: sticky;
                top: 0;
                z-index: 50;
                background: var(--surface);
            }

            .content { padding: 16px; }

            /* Grids de 2 columnas → 1 columna */
            .grid-2, .grid-3 { grid-template-columns: 1fr !important; }

            /* Grids inline con 2 columnas fijas */
            [style*="grid-template-columns:1fr 1fr"],
            [style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }

            /* Cards de colegios (3 columnas → 1) */
            [style*="grid-template-columns:repeat(3,1fr)"],
            [style*="grid-template-columns: repeat(3, 1fr)"] {
                grid-template-columns: 1fr !important;
            }

            /* Card header wrap */
            .card-header { flex-wrap: wrap; gap: 8px; padding: 14px 16px; }
            .card-body   { padding: 16px; }

            /* Botones más compactos */
            .btn    { font-size: 12px; padding: 8px 14px; }
            .btn-sm { padding: 5px 10px; font-size: 11px; }

            .page-title { font-size: 16px; }

            .table th { font-size: 10px; padding: 10px 10px; }
            .table td { font-size: 13px; padding: 10px 10px; }
        }

        /* ─ MÓVIL PEQUEÑO ─ */
        @media (max-width: 480px) {
            /* Alumnos SI: de auto-fit → 2 columnas fijas */
            [style*="minmax(160px, 1fr)"] {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            /* Stats principales: 2 columnas */
            [style*="minmax(190px, 1fr)"] {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            .content { padding: 12px; }
        }

        /* ─ LANDSCAPE MÓVIL ─ */
        @media (max-width: 900px) and (orientation: landscape) {
            .sidebar { width: 220px; transform: translateX(-220px); }
            .sidebar.sidebar-open { transform: translateX(0); }
            .sidebar-nav { padding: 12px; }
            .nav-item { padding: 8px 10px; font-size: 13px; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h1>Macmillan<span>SI</span></h1>
        <p>CRM de Colegios</p>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="nav-icon">📊</span> Dashboard
        </a>
        <a href="{{ route('tareas.index') }}" class="nav-item {{ request()->routeIs('tareas.*') ? 'active' : '' }}">
            <span class="nav-icon">✅</span> Tareas SI
        </a>
        <a href="{{ route('bitacora.index') }}" class="nav-item {{ request()->routeIs('bitacora.*') ? 'active' : '' }}">
            <span class="nav-icon">📋</span> Bitácora
        </a>
        <div class="nav-label">Gestión</div>
        <a href="{{ route('schools.index') }}" class="nav-item {{ request()->routeIs('schools.*') ? 'active' : '' }}">
            <span class="nav-icon">🏫</span> Colegios
        </a>
        <a href="{{ route('consultants.index') }}" class="nav-item {{ request()->routeIs('consultants.*') ? 'active' : '' }}">
            <span class="nav-icon">👥</span> Equipo SI
        </a>
        <a href="{{ route('bundles.index') }}" class="nav-item {{ request()->routeIs('bundles.*') ? 'active' : '' }}">
            <span class="nav-icon">📚</span> Bundles SI
        </a>
        
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            
@php
    $consultant = auth()->user()->consultant;
@endphp

@php
    $consultantPhoto = null;
    $authConsultant = \App\Models\Consultant::where('user_id', auth()->id())->first();
    if ($authConsultant && $authConsultant->photo) {
        $consultantPhoto = asset('storage/' . $authConsultant->photo);
    }
@endphp

@if($consultantPhoto)
    <img src="{{ $consultantPhoto }}"
         style="width:32px; height:32px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,0.2)">
@else
    <div class="user-avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
@endif

            <div>
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">{{ auth()->user()->getRoleNames()->first() }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">← Cerrar sesión</button>
        </form>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div style="display:flex; align-items:center; gap:10px">
            <button class="hamburger" id="hamburger-btn" aria-label="Abrir menú">
                <span></span><span></span><span></span>
            </button>
            <div class="page-title">@yield('title', 'Dashboard')</div>
        </div>
        <div style="font-size:13px; color: var(--text-muted);">{{ now()->format('d M Y') }}</div>
    </div>
    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">❌ {{ session('error') }}</div>
        @endif
        @if(session('error_acceso'))
            <div class="alert alert-danger">🔒 {{ session('error_acceso') }}</div>
        @endif
        @yield('content')
    </div>
</div>

<script>
(function () {
    // ── Sidebar toggle ──
    const btn     = document.getElementById('hamburger-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    function openSidebar() {
        sidebar.classList.add('sidebar-open');
        overlay.classList.add('sidebar-open');
        btn.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        overlay.classList.remove('sidebar-open');
        btn.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    btn?.addEventListener('click', () =>
        sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar()
    );
    overlay?.addEventListener('click', closeSidebar);

    // Cerrar al navegar en móvil
    document.querySelectorAll('.nav-item').forEach(item =>
        item.addEventListener('click', () => { if (window.innerWidth <= 900) closeSidebar(); })
    );

    // Cerrar al rotar a landscape si ya está abierto
    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) closeSidebar();
    });

    // ── Tablas scrolleables en móvil ──
    document.querySelectorAll('.table').forEach(table => {
        if (!table.parentElement.classList.contains('table-scroll')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-scroll';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    // ── Service Worker (PWA) ──
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }

    // ── Auto-dismiss flash messages ──
    document.querySelectorAll('.alert').forEach(function(el) {
        setTimeout(function() {
            el.style.opacity = '0';
            el.style.maxHeight = '0';
            el.style.marginBottom = '0';
            el.style.padding = '0';
        }, 4000);
    });
})();
</script>

</body>
</html>