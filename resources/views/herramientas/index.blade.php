@extends('layouts.app')

@section('title', 'Herramientas SI')

@section('content')
<style>
    /* ── Tarjetas de herramientas ── */
    .herramientas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
        margin-top: 8px;
    }
    .herramienta-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 28px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 14px;
        box-shadow: 0 1px 4px rgba(24,19,17,.05);
        transition: box-shadow .2s, transform .2s;
    }
    .herramienta-card:hover {
        box-shadow: 0 6px 24px rgba(24,19,17,.10);
        transform: translateY(-2px);
    }
    .herramienta-icono {
        width: 56px; height: 56px;
        border-radius: 14px;
        background: rgba(226,35,26,.08);
        display: grid; place-items: center;
        font-size: 28px;
        flex-shrink: 0;
    }
    .herramienta-titulo {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 17px; font-weight: 700;
        color: var(--text); line-height: 1.2;
    }
    .herramienta-desc {
        font-size: 13.5px; color: var(--text-muted);
        line-height: 1.6; flex: 1;
    }
    .herramienta-tag {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600;
        background: var(--surface2); color: var(--text-muted);
        border: 1px solid var(--border);
    }
    .btn-abrir {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 22px; border-radius: 10px;
        background: var(--accent); color: #fff;
        font-size: 13px; font-weight: 600;
        text-decoration: none; border: none; cursor: pointer;
        transition: background .15s;
        margin-top: auto;
    }
    .btn-abrir:hover { background: var(--accent2); color: #fff; }

    /* ── Sección Archivos SI ── */
    .archivos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    .archivo-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 22px;
        display: flex; flex-direction: column; gap: 12px;
        box-shadow: 0 1px 4px rgba(24,19,17,.05);
        transition: box-shadow .2s;
    }
    .archivo-card:hover { box-shadow: 0 4px 16px rgba(24,19,17,.09); }
    .archivo-titulo {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 16px; font-weight: 700; color: var(--text);
    }
    .archivo-desc {
        font-size: 13.5px; color: #4A4540;
        line-height: 1.6; white-space: pre-line; flex: 1;
    }
    .archivo-adjunto-link {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 13px; border-radius: 9px;
        background: var(--surface2); border: 1px solid var(--border);
        font-size: 12px; font-weight: 500; color: var(--text);
        text-decoration: none; transition: background .15s;
        max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .archivo-adjunto-link:hover { background: var(--border); }
    .archivo-adjunto-img {
        width: 100%; max-height: 160px; object-fit: cover;
        border-radius: 9px; border: 1px solid var(--border);
    }
    .archivo-footer {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 8px;
        font-size: 12px; color: var(--text-muted);
        padding-top: 10px; border-top: 1px solid var(--border);
    }
    .archivo-acciones { display: flex; gap: 6px; }
    .btn-editar-arch {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 11px; border-radius: 7px;
        font-size: 12px; font-weight: 500; cursor: pointer;
        background: var(--surface2); color: var(--text); border: 1px solid var(--border);
        transition: background .15s;
    }
    .btn-editar-arch:hover { background: var(--border); }
    .btn-eliminar-arch {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 11px; border-radius: 7px;
        font-size: 12px; font-weight: 500; cursor: pointer;
        background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;
        transition: background .15s;
    }
    .btn-eliminar-arch:hover { background: #b91c1c; color: #fff; }

    /* ── Empty state ── */
    .empty-state {
        text-align: center; padding: 50px 20px; color: var(--text-muted);
    }
    .empty-state svg { opacity: .3; margin: 0 auto 14px; display: block; }
    .empty-state p { font-size: 14px; }

    /* ── Modal compartido ── */
    .modal-backdrop {
        display: none; position: fixed; inset: 0;
        background: rgba(24,19,17,.55); z-index: 500;
        backdrop-filter: blur(3px);
        align-items: center; justify-content: center;
    }
    .modal-backdrop.open { display: flex; }
    .modal {
        background: var(--surface); border-radius: 16px;
        width: 100%; max-width: 520px; max-height: 90vh;
        overflow-y: auto; padding: 32px;
        box-shadow: 0 24px 64px -12px rgba(24,19,17,.32);
        position: relative;
    }
    .modal-title {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 20px; font-weight: 700; color: var(--text);
        margin-bottom: 20px;
    }
    .modal-close {
        position: absolute; top: 18px; right: 18px;
        width: 32px; height: 32px; border-radius: 8px;
        border: none; background: var(--surface2); cursor: pointer;
        display: grid; place-items: center; color: var(--text-muted);
        transition: background .15s;
    }
    .modal-close:hover { background: var(--border); color: var(--text); }
    .upload-zone {
        border: 1.5px dashed var(--border); border-radius: 10px;
        padding: 18px; text-align: center; cursor: pointer;
        transition: border-color .18s, background .18s;
        color: var(--text-muted); font-size: 13px;
    }
    .upload-zone:hover { border-color: var(--accent); background: rgba(226,35,26,.04); }
    .upload-zone input { display:none; }
    .upload-zone svg { margin: 0 auto 8px; display: block; opacity: .45; }

    @media(max-width:600px){
        .herramientas-grid, .archivos-grid { grid-template-columns: 1fr; }
        .modal { padding: 22px 16px; }
    }
</style>

{{-- ══ HERRAMIENTAS ══ --}}
<div style="margin-bottom:28px">
    <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
        🛠️ Herramientas SI
    </h2>
    <p style="font-size:13px; color:var(--text-muted); margin-top:4px">
        Utilidades internas del equipo — solo accesibles con sesión iniciada
    </p>
</div>

<div class="herramientas-grid" style="margin-bottom:48px">

    <div class="herramienta-card">
        <div class="herramienta-icono">📦</div>
        <div class="herramienta-titulo">Generador de Altas de Bundles</div>
        <span class="herramienta-tag">📄 Genera .xlsx</span>
        <p class="herramienta-desc">
            Selecciona libros y cantidades del catálogo (683 títulos · 26 series),
            ingresa el ID del colegio y descarga el Excel listo para el bot de altas MEE.
        </p>
        <a href="{{ route('herramientas.altas-bundles') }}" target="_blank" class="btn-abrir">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Abrir herramienta
        </a>
    </div>

    <div class="herramienta-card">
        <div class="herramienta-icono">🪪</div>
        <div class="herramienta-titulo">Generador de Tarjetas de Accesos</div>
        <span class="herramienta-tag">📥 Requiere Excel</span>
        <p class="herramienta-desc">
            Sube un Excel con Nombre Completo, Usuario y Contraseña, y genera tarjetas
            recortables listas para imprimir o guardar como PDF.
        </p>
        <a href="{{ route('herramientas.accesos') }}" target="_blank" class="btn-abrir">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Abrir herramienta
        </a>
    </div>

    <div class="herramienta-card">
        <div class="herramienta-icono">📱</div>
        <div class="herramienta-titulo">Generador de Consulta de Accesos</div>
        <span class="herramienta-tag">📥 Requiere Excel · Genera .html</span>
        <p class="herramienta-desc">
            Sube el mismo Excel y descarga una mini-app HTML offline para que
            la escuela consulte sus credenciales desde el celular sin internet.
        </p>
        <a href="{{ route('herramientas.consulta-accesos') }}" target="_blank" class="btn-abrir">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Abrir herramienta
        </a>
    </div>

    <div class="herramienta-card">
        <div class="herramienta-icono">🔄</div>
        <div class="herramienta-titulo">Cambio de Nombre de Usuario</div>
        <span class="herramienta-tag">📄 Genera .xlsx</span>
        <p class="herramienta-desc">
            Ingresa el usuario actual y el nuevo lado a lado, descarga el Excel con
            las columnas <b>Correo</b> y <b>UsuarioNuevo</b> listo para enviar al equipo MEE.
        </p>
        <a href="{{ route('herramientas.cambio-usuario') }}" target="_blank" class="btn-abrir">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Abrir herramienta
        </a>
    </div>

    <div class="herramienta-card">
        <div class="herramienta-icono">🔐</div>
        <div class="herramienta-titulo">Cambio de Password</div>
        <span class="herramienta-tag">📄 Genera .xlsx</span>
        <p class="herramienta-desc">
            Ingresa el usuario y la nueva contraseña lado a lado, descarga el Excel con
            las columnas <b>Correo</b> y <b>Password</b> listo para enviar al equipo MEE.
        </p>
        <a href="{{ route('herramientas.cambio-password') }}" target="_blank" class="btn-abrir">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Abrir herramienta
        </a>
    </div>

    <div class="herramienta-card">
        <div class="herramienta-icono">📦</div>
        <div class="herramienta-titulo">Eliminación de Bundles</div>
        <span class="herramienta-tag">✉️ Genera solicitud por correo</span>
        <p class="herramienta-desc">
            Ingresa el ID del colegio y genera el correo de solicitud para que el equipo MEE
            procese la baja de todos los bundles del colegio.
        </p>
        <a href="{{ route('herramientas.eliminacion-bundles') }}" target="_blank" class="btn-abrir">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Abrir herramienta
        </a>
    </div>

    <div class="herramienta-card">
        <div class="herramienta-icono">🗑️</div>
        <div class="herramienta-titulo">Borrado de Usuarios</div>
        <span class="herramienta-tag">📄 Genera .xlsx</span>
        <p class="herramienta-desc">
            Genera el archivo de baja masiva de cuentas MEE. Pega los usuarios a eliminar
            y descarga el Excel listo para enviar al equipo.
        </p>
        <a href="{{ route('herramientas.borrar-usuarios') }}" target="_blank" class="btn-abrir">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Abrir herramienta
        </a>
    </div>

</div>

{{-- ══ TEMPLATES OUTLOOK ══ --}}
<div style="margin-bottom:20px; margin-top:8px">
    <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0">
        ✉️ Templates Outlook
    </h2>
    <p style="font-size:13px; color:var(--text-muted); margin-top:4px">
        Correos prediseñados listos para copiar y pegar en Outlook
    </p>
</div>

<div class="herramientas-grid" style="margin-bottom:48px">

    <div class="herramienta-card">
        <div class="herramienta-icono">👋</div>
        <div class="herramienta-titulo">Template Bienvenida SI</div>
        <span class="herramienta-tag">📋 Genera correo para Outlook</span>
        <p class="herramienta-desc">
            Selecciona el consultor, llena el nombre del director y el colegio, y copia el
            correo de bienvenida listo para pegar directamente en Outlook.
        </p>
        <a href="{{ route('herramientas.template-bienvenida') }}" target="_blank" class="btn-abrir">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Abrir herramienta
        </a>
    </div>

    <div class="herramienta-card">
        <div class="herramienta-icono">📅</div>
        <div class="herramienta-titulo">Template Confirmación de Reunión</div>
        <span class="herramienta-tag">📋 Genera correo para Outlook</span>
        <p class="herramienta-desc">
            Confirma la fecha, hora y liga de la reunión. Genera el correo de respuesta
            con el botón de acceso listo para pegar en Outlook.
        </p>
        <a href="{{ route('herramientas.template-confirmacion') }}" target="_blank" class="btn-abrir">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Abrir herramienta
        </a>
    </div>

</div>

{{-- ══ ARCHIVOS SI COLEGIOS ══ --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px">
    <div>
        <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:20px; font-weight:700; color:var(--text); margin:0">
            📁 Archivos SI Colegios
        </h2>
        <p style="font-size:13px; color:var(--text-muted); margin-top:4px">
            Documentos permanentes para entregar o solicitar información a colegios
        </p>
    </div>
    @if(auth()->user()->hasRole('admin'))
        <button onclick="abrirModalCrear()" class="btn btn-primary" style="gap:8px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Subir Archivo
        </button>
    @endif
</div>

@if($archivos->isEmpty())
    <div class="empty-state">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        <p>No hay archivos publicados todavía.</p>
        @if(auth()->user()->hasRole('admin'))
            <button onclick="abrirModalCrear()" style="margin-top:14px; background:var(--accent); color:#fff; border:none; border-radius:9px; padding:10px 22px; font-size:13px; font-weight:600; cursor:pointer">
                Subir el primero
            </button>
        @endif
    </div>
@else
    <div class="archivos-grid">
        @foreach($archivos as $archivo)
        <div class="archivo-card">
            <div class="archivo-titulo">{{ $archivo->titulo }}</div>

            @if($archivo->descripcion)
                <p class="archivo-desc">{{ $archivo->descripcion }}</p>
            @endif

            @if($archivo->archivo)
                @if($archivo->archivo_tipo === 'image')
                    <img src="{{ asset('storage/' . $archivo->archivo) }}"
                         alt="{{ $archivo->archivo_nombre }}" class="archivo-adjunto-img">
                @else
                    <a href="{{ asset('storage/' . $archivo->archivo) }}"
                       target="_blank" class="archivo-adjunto-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            @if($archivo->archivo_tipo === 'pdf')
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/>
                            @else
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13,2 13,9 20,9"/>
                            @endif
                        </svg>
                        {{ $archivo->archivo_nombre }}
                    </a>
                @endif
            @endif

            <div class="archivo-footer">
                <span>
                    <strong style="color:var(--text)">{{ $archivo->user->name ?? '—' }}</strong>
                    · {{ $archivo->created_at->diffForHumans() }}
                </span>
                @if(auth()->user()->hasRole('admin'))
                    <div class="archivo-acciones">
                        <button class="btn-editar-arch"
                            onclick="abrirModalEditar(
                                {{ $archivo->id }},
                                {{ json_encode($archivo->titulo) }},
                                {{ json_encode($archivo->descripcion) }},
                                {{ json_encode($archivo->archivo_nombre) }}
                            )">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Editar
                        </button>
                        <form method="POST" action="{{ route('herramientas.archivos.destroy', $archivo) }}"
                              onsubmit="return confirm('¿Eliminar este archivo?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-eliminar-arch">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3,6 5,6 21,6"/><path d="M19,6l-1,14a2,2,0,0,1-2,2H8a2,2,0,0,1-2-2L5,6"/><path d="M10,11v6"/><path d="M14,11v6"/></svg>
                                Eliminar
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- ══ MODALES (solo admin) ══ --}}
@if(auth()->user()->hasRole('admin'))

{{-- Modal Crear --}}
<div class="modal-backdrop" id="modalCrear" onclick="cerrarModalFuera(event,'modalCrear')">
    <div class="modal">
        <button class="modal-close" onclick="cerrarModal('modalCrear')" aria-label="Cerrar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="modal-title">Subir Archivo</div>
        <form method="POST" action="{{ route('herramientas.archivos.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_form" value="crear">
            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" name="titulo" class="form-control"
                       value="{{ old('titulo') }}" required>
                @error('titulo')<small style="color:var(--danger)">{{ $message }}</small>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="4"
                          style="resize:vertical">{{ old('descripcion') }}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Archivo <small style="font-weight:400; color:var(--text-muted)">(PDF, imagen, Excel, Word — máx. 10 MB)</small></label>
                <div class="upload-zone" onclick="document.getElementById('archivoInputCrear').click()">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17,8 12,3 7,8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <p><span style="color:var(--accent);font-weight:600">Seleccionar archivo</span> o arrastra aquí</p>
                    <p id="nombreCrear" style="margin-top:4px;font-size:12px;color:var(--accent)"></p>
                    <input type="file" name="archivo" id="archivoInputCrear"
                           accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.docx,.doc"
                           onchange="mostrarNombre(this,'nombreCrear')">
                </div>
                @error('archivo')<small style="color:var(--danger)">{{ $message }}</small>@enderror
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
                <button type="button" onclick="cerrarModal('modalCrear')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Publicar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modalEditar" onclick="cerrarModalFuera(event,'modalEditar')">
    <div class="modal">
        <button class="modal-close" onclick="cerrarModal('modalEditar')" aria-label="Cerrar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="modal-title">Editar Archivo</div>
        <form method="POST" id="formEditar" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_form" value="editar">
            <input type="hidden" name="_id" id="editId" value="">
            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" name="titulo" id="editTitulo" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" id="editDesc" class="form-control" rows="4" style="resize:vertical"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Reemplazar archivo <small style="font-weight:400;color:var(--text-muted)">(opcional — deja vacío para conservar el actual)</small></label>
                <div class="upload-zone" onclick="document.getElementById('archivoInputEditar').click()">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17,8 12,3 7,8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <p id="archivoActualLabel" style="font-size:12px;color:var(--text-muted)"></p>
                    <p id="nombreEditar" style="margin-top:4px;font-size:12px;color:var(--accent)"></p>
                    <input type="file" name="archivo" id="archivoInputEditar"
                           accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.docx,.doc"
                           onchange="mostrarNombre(this,'nombreEditar')">
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
                <button type="button" onclick="cerrarModal('modalEditar')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

@endif

<script>
    function abrirModalCrear() {
        document.getElementById('modalCrear').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function abrirModalEditar(id, titulo, descripcion, archivoNombre) {
        document.getElementById('editId').value     = id;
        document.getElementById('editTitulo').value = titulo || '';
        document.getElementById('editDesc').value   = descripcion || '';
        document.getElementById('archivoActualLabel').textContent = archivoNombre
            ? '📎 Archivo actual: ' + archivoNombre
            : 'Sin archivo adjunto actualmente';
        document.getElementById('nombreEditar').textContent = '';
        document.getElementById('archivoInputEditar').value = '';
        document.getElementById('formEditar').action = '/herramientas-si/archivos/' + id;
        document.getElementById('modalEditar').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal(id) {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
    }

    function cerrarModalFuera(e, id) {
        if (e.target === document.getElementById(id)) cerrarModal(id);
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            cerrarModal('modalCrear');
            cerrarModal('modalEditar');
        }
    });

    function mostrarNombre(input, targetId) {
        document.getElementById(targetId).textContent =
            input.files[0] ? '📎 ' + input.files[0].name : '';
    }

    @if($errors->any())
        @if(old('_form') === 'editar')
            document.addEventListener('DOMContentLoaded', function(){
                abrirModalEditar(
                    {{ (int) old('_id', 0) }},
                    {{ json_encode(old('titulo', '')) }},
                    {{ json_encode(old('descripcion', '')) }},
                    null
                );
            });
        @else
            document.addEventListener('DOMContentLoaded', abrirModalCrear);
        @endif
    @endif
</script>
@endsection
