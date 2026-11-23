@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
@php
    $totalProcesosGlobales = $totalProcesosGlobales ?? \App\Models\Process::count();
    $conteoSeg = $conteoSeg ?? [];
    $totalSegAcciones = array_sum($conteoSeg);
    $totalArranqueAll = $totalProcesosGlobales + $totalSegAcciones;
@endphp

<style>
    .cfg-vistas {
        display: inline-flex;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 4px;
        gap: 4px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }
    .cfg-vista-btn {
        border: none;
        background: none;
        font: inherit;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-muted);
        padding: 7px 16px;
        border-radius: 7px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: all 0.15s;
    }
    .cfg-vista-btn:hover { color: var(--text); }
    .cfg-vista-btn.activa {
        background: #ffffff;
        color: var(--text);
        box-shadow: 0 1px 3px rgba(24, 19, 17, 0.1);
    }
    .cfg-tab-count {
        font-size: 11px;
        font-weight: 700;
        padding: 1px 7px;
        border-radius: 99px;
        background: rgba(24, 19, 17, 0.08);
        color: var(--text-muted);
    }
    .cfg-vista-btn.activa .cfg-tab-count {
        background: var(--accent);
        color: #ffffff;
    }
    .cfg-arr-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 12px;
    }
    .cfg-arr-card {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 16px 18px;
        border-radius: 12px;
        border: 1.5px solid var(--border);
        background: var(--surface);
        text-align: left;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    }
    .cfg-arr-card:hover {
        border-color: var(--accent);
        background: #ffffff;
        box-shadow: 0 8px 20px rgba(24, 19, 17, 0.07);
        transform: translateY(-2px);
    }
    .cfg-arr-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .cfg-arr-ico {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--surface2);
        border: 1px solid var(--border);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .cfg-arr-count-pill {
        font-size: 11.5px;
        font-weight: 700;
        padding: 2px 9px;
        border-radius: 999px;
        background: var(--surface2);
        border: 1px solid var(--border);
        color: var(--text);
    }
    .cfg-arr-count-pill.is-zero {
        background: #fffbeb;
        border-color: #fde68a;
        color: #b45309;
    }
</style>

<div style="max-width:960px">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px">
        <div>
            <h1 style="font-family:'Bricolage Grotesque',sans-serif; font-size:24px; font-weight:700; margin:0">⚙️ Configuración del CRM</h1>
            <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">Administración exclusiva para Administrador.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">← Dashboard</a>
    </div>

    <div class="cfg-vistas" role="tablist">
        <button type="button" class="cfg-vista-btn activa" data-cfg-tab="servicios" role="tab">
            📦 Servicios <span class="cfg-tab-count">{{ $types->count() }}</span>
        </button>
        @hasrole('admin')
        <button type="button" class="cfg-vista-btn" data-cfg-tab="arranque" role="tab">
            🚀 Acciones de arranque <span class="cfg-tab-count" data-cfg-arranque-total>{{ $totalArranqueAll }}</span>
        </button>
        @endhasrole
    </div>

    <div id="cfg-panel-servicios" style="max-width:720px">
    <div class="card">
        <div class="card-header">
            <span class="card-title">⚙️ Servicios contables por colegio</span>
        </div>
        <div class="card-body">

            <p style="font-size:13.5px; color:var(--text-muted); margin-bottom:20px; line-height:1.6">
                Aquí defines qué servicios adicionales se pueden marcar por colegio (Global Educa, Lego, etc.).
                Los activos aparecerán en el formulario de edición de cada colegio y en el dashboard.
            </p>

            {{-- Lista de servicios --}}
            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:28px">
                @forelse($types as $type)
                @php $errEditar = $errors->getBag('editar_' . $type->id); @endphp
                <div class="servicio-item" data-editando="{{ $errEditar->any() ? '1' : '0' }}">
                <div class="servicio-vista" style="display:flex; align-items:center; gap:12px; padding:12px 16px;
                            border:1px solid var(--border); border-radius:10px; background:var(--surface)">
                    <span style="font-size:22px; width:32px; text-align:center">{{ $type->icon }}</span>
                    <div style="flex:1">
                        <div style="font-weight:600; font-size:14px; color:var(--text)">{{ $type->name }}</div>
                        <div style="font-size:11.5px; color:var(--text-muted); margin-top:1px">
                            {{ $type->schools()->count() }} colegio(s) asignados
                            @if($type->plataforma && isset($plataformas[$type->plataforma]))
                                · 🧭 {{ $plataformas[$type->plataforma]['nombre'] }}
                            @endif
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px">
                        {{-- Badge activo/inactivo --}}
                        <span style="font-size:11px; font-weight:600; padding:3px 10px; border-radius:999px;
                                     background:{{ $type->active ? '#f0fdf4' : '#f1f5f9' }};
                                     color:{{ $type->active ? '#15803d' : '#64748b' }}">
                            {{ $type->active ? 'Activo' : 'Inactivo' }}
                        </span>
                        {{-- Editar --}}
                        <button type="button" class="servicio-btn-editar"
                                style="padding:5px 10px; border-radius:6px; font-size:12px; font-weight:500;
                                       cursor:pointer; border:1px solid var(--border); background:var(--surface-alt)">
                            ✏️ Editar
                        </button>
                        {{-- Toggle activo --}}
                        <form method="POST" action="{{ route('configuracion.servicios.toggle', $type) }}" style="margin:0">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    style="padding:5px 10px; border-radius:6px; font-size:12px; font-weight:500;
                                           cursor:pointer; border:1px solid var(--border); background:var(--surface-alt)">
                                {{ $type->active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        {{-- Eliminar --}}
                        <form method="POST" action="{{ route('configuracion.servicios.destroy', $type) }}" id="form-del-srv-{{ $type->id }}" style="margin:0">
                            @csrf @method('DELETE')
                            <button type="button"
                                    onclick="confirmarEliminar('Eliminar servicio', '¿Eliminar el servicio «{{ addslashes($type->name) }}»? Se quitará de todos los colegios que lo tengan.', 'form-del-srv-{{ $type->id }}')"
                                    style="padding:5px 10px; border-radius:6px; font-size:12px; font-weight:500;
                                           cursor:pointer; border:1px solid #fecaca; background:#fff5f5; color:#dc2626">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Edición en línea --}}
                <form method="POST" action="{{ route('configuracion.servicios.update', $type) }}" class="servicio-editar"
                      style="padding:14px 16px; border:1px solid var(--accent); border-radius:10px; background:var(--surface)">
                    @csrf @method('PATCH')
                    <div style="font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:10px">
                        Editando: {{ $type->name }}
                    </div>
                    <div style="display:grid; grid-template-columns:1fr auto auto; gap:10px; align-items:end">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="name" class="form-control" required maxlength="100"
                                   value="{{ $errEditar->any() ? old('name', $type->name) : $type->name }}">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Ícono</label>
                            <input type="text" name="icon" class="form-control" maxlength="4"
                                   value="{{ $errEditar->any() ? old('icon', $type->icon) : $type->icon }}"
                                   style="width:64px; text-align:center; font-size:20px">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control"
                                   value="{{ $errEditar->any() ? old('color', $type->color) : $type->color }}"
                                   style="width:48px; height:42px; padding:3px; cursor:pointer">
                        </div>
                    </div>
                    <div class="form-group" style="margin:10px 0 0">
                        <label class="form-label">Gestor de plataformas</label>
                        @php $plataformaActual = $errEditar->any() ? old('plataforma', $type->plataforma) : $type->plataforma; @endphp
                        <select name="plataforma" class="form-control">
                            <option value="">Ninguna</option>
                            @foreach($plataformas as $clave => $plataforma)
                                <option value="{{ $clave }}" @selected($plataformaActual === $clave)>{{ $plataforma['nombre'] }}</option>
                            @endforeach
                        </select>
                        <small style="color:var(--text-muted)">Liga este servicio con su apartado de Seguimiento. Puedes cambiar el nombre sin perder la relación.</small>
                    </div>
                    @if($errEditar->any())
                        <small style="display:block; margin-top:6px; color:var(--danger)">{{ $errEditar->first() }}</small>
                    @endif
                    @if($type->schools()->count() > 0)
                        <div style="font-size:11.5px; color:var(--text-muted); margin-top:8px">
                            Los {{ $type->schools()->count() }} colegio(s) que ya lo tienen lo conservan con el nombre nuevo.
                        </div>
                    @endif
                    <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
                        <button type="button" class="btn btn-secondary btn-sm servicio-btn-cancelar">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar cambios</button>
                    </div>
                </form>
                </div>
                @empty
                <div style="text-align:center; padding:32px; color:var(--text-muted); font-size:14px">
                    Sin servicios configurados aún.
                </div>
                @endforelse
            </div>

            {{-- Formulario agregar nuevo --}}
            <div style="border-top:1px solid var(--border); padding-top:22px">
                <div style="font-size:13px; font-weight:700; color:var(--text); margin-bottom:14px">
                    + Agregar nuevo servicio
                </div>
                <form method="POST" action="{{ route('configuracion.servicios.store') }}">
                    @csrf
                    <div style="display:grid; grid-template-columns:1fr auto auto; gap:10px; align-items:end">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name') }}" placeholder="Ej: Coding Lab" required>
                            @error('name')
                            <small style="color:var(--danger)">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Ícono</label>
                            <input type="text" name="icon" class="form-control" maxlength="4"
                                   value="{{ old('icon', '📦') }}" style="width:64px; text-align:center; font-size:20px">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control"
                                   value="{{ old('color', '#6366f1') }}"
                                   style="width:48px; height:42px; padding:3px; cursor:pointer">
                        </div>
                    </div>
                    <div class="form-group" style="margin:12px 0 0">
                        <label class="form-label">Gestor de plataformas</label>
                        <select name="plataforma" class="form-control">
                            <option value="">Ninguna</option>
                            @foreach($plataformas as $clave => $plataforma)
                                <option value="{{ $clave }}" @selected(old('plataforma') === $clave)>{{ $plataforma['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('plataforma')
                        <small style="color:var(--danger)">{{ $message }}</small>
                        @enderror
                    </div>
                    <div style="margin-top:14px; text-align:right">
                        <button type="submit" class="btn btn-primary">Guardar servicio</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
    </div>

    @hasrole('admin')
    <div id="cfg-panel-arranque" style="display:none">
        <div class="card" style="margin-bottom:18px">
            <div class="card-header">
                <span class="card-title">🏫 Acciones de arranque globales (Colegios)</span>
                <span class="cfg-arr-count-pill" data-cfg-colegios-count>{{ $totalProcesosGlobales }} {{ $totalProcesosGlobales === 1 ? 'acción' : 'acciones' }}</span>
            </div>
            <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap">
                <p style="font-size:13.5px; color:var(--text-muted); margin:0; line-height:1.55; flex:1 1 320px;">
                    Administra los pasos globales que se muestran en la ficha de <strong>Acciones de arranque</strong> por nivel de cada colegio.
                    Puedes agregar, renombrar, arrastrar para ordenar o eliminar acciones al instante.
                </p>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.abrirHubArranque('colegios')">
                    ⚙️ Personalizar acciones globales
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">🧭 Acciones de arranque por plataforma (Seguimiento)</span>
                <span style="font-size:12.5px; color:var(--text-muted)">Selecciona una plataforma para personalizar</span>
            </div>
            <div class="card-body">
                <div class="cfg-arr-grid">
                    @foreach($plataformas as $pSlug => $pMeta)
                        @php $pCant = (int) ($conteoSeg[$pSlug] ?? 0); @endphp
                        <button type="button" class="cfg-arr-card" onclick="window.abrirHubArranque('seguimiento', '{{ $pSlug }}')">
                            <div class="cfg-arr-card-top">
                                <span class="cfg-arr-ico">{{ $pMeta['icono'] }}</span>
                                <span class="cfg-arr-count-pill {{ $pCant === 0 ? 'is-zero' : '' }}" data-cfg-seg-count="{{ $pSlug }}">
                                    {{ $pCant }} {{ $pCant === 1 ? 'acción' : 'acciones' }}
                                </span>
                            </div>
                            <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:15.5px; font-weight:700; color:var(--text);">
                                {{ $pMeta['nombre'] }}
                            </div>
                            <div style="font-size:12px; color:var(--text-muted); line-height:1.45;">
                                {{ $pCant > 0 ? 'Agregar, editar, arrastrar orden o eliminar acciones.' : 'Sin acciones todavía. Haz clic para agregar.' }}
                            </div>
                            <span style="font-size:12px; font-weight:700; color:var(--accent);">Configurar {{ $pMeta['nombre'] }} →</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endhasrole

</div>

<style>
    .servicio-item .servicio-editar { display: none; }
    .servicio-item[data-editando="1"] .servicio-vista { display: none !important; }
    .servicio-item[data-editando="1"] .servicio-editar { display: block; }
</style>
<script>
    (function () {
        const tabs = Array.from(document.querySelectorAll('[data-cfg-tab]'));
        const panelServicios = document.getElementById('cfg-panel-servicios');
        const panelArranque = document.getElementById('cfg-panel-arranque');
        const storageKey = 'sic-config-tab';

        function activarTab(nombre) {
            if (!panelArranque && nombre === 'arranque') nombre = 'servicios';
            tabs.forEach(t => t.classList.toggle('activa', t.dataset.cfgTab === nombre));
            if (panelServicios) panelServicios.style.display = nombre === 'servicios' ? 'block' : 'none';
            if (panelArranque) panelArranque.style.display = nombre === 'arranque' ? 'block' : 'none';
            try { sessionStorage.setItem(storageKey, nombre); } catch (e) {}
        }

        const params = new URLSearchParams(window.location.search);
        const tabQuery = params.get('tab');
        let tabInicial = 'servicios';
        if (tabQuery === 'arranque' || tabQuery === 'servicios') {
            tabInicial = tabQuery;
        } else {
            try {
                const guardada = sessionStorage.getItem(storageKey);
                if (guardada === 'arranque' || guardada === 'servicios') tabInicial = guardada;
            } catch (e) {}
        }
        activarTab(tabInicial);

        tabs.forEach(t => t.addEventListener('click', () => activarTab(t.dataset.cfgTab)));
    })();

    document.querySelectorAll('.servicio-item').forEach(function (item) {
        const form = item.querySelector('.servicio-editar');
        item.querySelector('.servicio-btn-editar')?.addEventListener('click', function () {
            document.querySelectorAll('.servicio-item[data-editando="1"]').forEach(o => { if (o !== item) o.dataset.editando = '0'; });
            item.dataset.editando = '1';
            form.querySelector('input[name="name"]').focus();
        });
        item.querySelector('.servicio-btn-cancelar')?.addEventListener('click', function () {
            form.reset();
            item.dataset.editando = '0';
        });
    });
</script>
@endsection
