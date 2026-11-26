@extends('layouts.app')

@section('title', 'Alumnos — ' . $school->name)

@section('content')
@if($school->status === 'inactivo')
<div style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:12px 16px;
            border-radius:8px; margin-bottom:16px; font-size:13.5px">
    ⚠️ Este colegio está <strong>inactivo</strong> — actívalo en
    <a href="{{ route('schools.edit', $school) }}" style="color:#92400e; text-decoration:underline">Editar colegio</a>
    para poder agregar alumnos aquí.
</div>
@endif
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    @hasanyrole('admin|consultor_digital')
    @if($school->status !== 'inactivo')
    <a href="{{ route('schools.students.create', $school) }}" class="btn btn-primary">+ Nuevo Alumno</a>
    <button onclick="document.getElementById('modal-excel').style.display='flex'"
            class="btn btn-secondary">📊 Carga masiva Excel/CSV</button>
    <button onclick="document.getElementById('modal-pdf').style.display='flex'"
            class="btn btn-secondary">📄 Carga masiva PDF</button>
    @endif
    @puedeEliminar($school)
    <form method="POST" action="{{ route('schools.students.destroy-all', $school) }}"
          id="form-borrar-todos-alumnos">
        @csrf @method('DELETE')
        <button type="button" class="btn btn-danger"
                onclick="confirmarEliminar('Borrar todos los alumnos', '¿Estás seguro? Esto eliminará TODOS los alumnos de este colegio.', 'form-borrar-todos-alumnos')">
            🗑️ Borrar todos
        </button>
    </form>
    @endpuedeEliminar
    @endhasanyrole
    <button type="button"
            id="btn-descargar-por-clase"
            onclick="abrirModalDescargarClases()"
            class="btn btn-secondary"
            @if($clasesDisponibles->isEmpty()) disabled title="No hay alumnos registrados para descargar" @endif>
        📥 Descargar por clase
    </button>
</div>

{{-- Modal Excel --}}
<div id="modal-excel" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
     z-index:999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:32px; width:560px; max-width:95%; max-height:90vh; overflow-y:auto; position:relative">
        <button onclick="document.getElementById('modal-excel').style.display='none'"
                style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:20px; cursor:pointer; color:#888">✕</button>

        <h3 style="font-family:'Bricolage Grotesque',sans-serif; margin:0 0 6px">📊 Carga masiva desde Excel/CSV</h3>
        <p style="margin:0 0 20px; font-size:14px; color:var(--text-muted)">
            Puedes subir directamente el mismo <strong>CSV</strong> que usas para dar de alta alumnos en Macmillan
            (columnas <strong>Nombre (s) · Apellidos · Usuario · Password · … · Nivel · Grado · Grupo</strong>),
            o la plantilla clásica de Excel: <strong>Nombre Completo · Usuario · Contraseña</strong>
            (+ opcionalmente <strong>Clase</strong> en columna D). La herramienta detecta el formato automáticamente
            leyendo los encabezados.
        </p>

        <form method="POST" action="{{ route('schools.students.import-excel', $school) }}"
              enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label">Archivo Excel (.xlsx / .xls) o CSV (.csv) *</label>
                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                @error('excel_file')
                    <p style="color:#e74c3c; font-size:12px; margin:4px 0 0">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Nivel</label>
                    <select name="level" class="form-control">
                        <option value="">-- Selecciona --</option>
                        @forelse($nivelesDelColegio as $nivel)
                            <option value="{{ $nivel }}">{{ $nivel }}</option>
                        @empty
                            <option value="Maternal">Maternal</option>
                            <option value="Preescolar">Preescolar</option>
                            <option value="Primaria">Primaria</option>
                            <option value="Secundaria">Secundaria</option>
                            <option value="Bachillerato">Bachillerato</option>
                            <option value="Universidad">Universidad</option>
                        @endforelse
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Grado <span style="font-size:11px; color:var(--text-muted)">(se aplica a todos si col. D está vacía)</span></label>
                    <input type="text" name="grade" class="form-control" placeholder="Ej: 1°A, 2°B">
                </div>
            </div>

            <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:12px; margin-bottom:20px; font-size:13px; color:#0369a1">
                💡 Si el archivo trae Nivel/Grado/Grupo (o Clase) por alumno, esos valores se usan por fila y sobreescriben los campos de arriba.
            <br>Antes de cargar te mostraremos cuántos alumnos son nuevos y cuántos ya existen y se actualizarían (por ejemplo, si cambió su contraseña), para que decidas qué hacer.
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end">
                <button type="button" onclick="document.getElementById('modal-excel').style.display='none'"
                        class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">📤 Importar alumnos</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal PDF --}}
<div id="modal-pdf" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
     z-index:999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:32px; width:500px; max-width:90%">
        <h3 style="font-family:'Bricolage Grotesque',sans-serif; margin-bottom:20px">📄 Carga masiva desde PDF</h3>
        <form method="POST" action="{{ route('schools.students.upload-pdf', $school) }}"
              enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Archivo PDF de MEE *</label>
                <input type="file" name="pdf_file" class="form-control" accept=".pdf" required>
                <small style="color:var(--text-muted)">Sube el PDF generado por MEE con usuarios y contraseñas</small>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Nivel</label>
                    <select name="level" class="form-control">
                        <option value="">-- Selecciona --</option>
                        @forelse($nivelesDelColegio as $nivel)
                            <option value="{{ $nivel }}">{{ $nivel }}</option>
                        @empty
                            {{-- Fallback si el colegio no tiene niveles configurados aún --}}
                            <option value="Maternal">Maternal</option>
                            <option value="Preescolar">Preescolar</option>
                            <option value="Primaria">Primaria</option>
                            <option value="Secundaria">Secundaria</option>
                            <option value="Bachillerato">Bachillerato</option>
                            <option value="Universidad">Universidad</option>
                        @endforelse
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Grado</label>
                    <input type="text" name="grade" class="form-control" placeholder="Ej: 1°A, 2°B">
                </div>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:10px">
                <button type="button" onclick="document.getElementById('modal-pdf').style.display='none'"
                        class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">📤 Cargar PDF</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Descargar alumnos por clase (estilo Global Educa) --}}
<div id="modal-descargar-clases" class="formato-modal-overlay" aria-hidden="true">
    <section class="formato-modal-panel" role="dialog" aria-modal="true" aria-labelledby="descargar-clases-titulo">
        <header class="formato-modal-header">
            <div>
                <span class="formato-modal-kicker">{{ $school->name }}</span>
                <h2 id="descargar-clases-titulo">Descargar alumnos por clase</h2>
                <span class="formato-modal-badge">
                    📚 {{ $clasesDisponibles->count() }} {{ $clasesDisponibles->count() === 1 ? 'clase detectada' : 'clases detectadas' }}
                </span>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="cerrarModalDescargarClases()" aria-label="Cerrar">✕</button>
        </header>

        <form method="POST" action="{{ route('schools.students.export-by-class', $school) }}" id="form-descargar-clases">
            @csrf
            <input type="hidden" name="modo" id="descargar-clases-modo" value="hojas">

            <div class="formato-modal-scroll">
                <div class="formato-tabs" role="tablist" aria-label="Formato de Excel">
                <button type="button" class="formato-tab is-active" role="tab" aria-selected="true"
                        data-clase-modo="hojas" onclick="cambiarModoClases('hojas', this)">
                    <span aria-hidden="true">📊</span> Excel por hojas
                </button>
                <button type="button" class="formato-tab" role="tab" aria-selected="false"
                        data-clase-modo="unica" onclick="cambiarModoClases('unica', this)">
                    <span aria-hidden="true">📋</span> Excel unificado
                </button>
            </div>
            <p id="descargar-clases-modo-desc" style="margin:-14px 0 16px; font-size:12px; color:var(--text-muted)">
                Genera un archivo Excel con una hoja independiente por cada clase seleccionada.
            </p>

            @if($clasesDisponibles->isEmpty())
                <div class="formato-aviso">
                    <strong>Sin clases disponibles</strong>
                    <p>Este colegio aún no tiene alumnos registrados.</p>
                </div>
            @else
                <div class="clases-toolbar">
                    <div class="clases-search-wrap">
                        <input type="search" id="filtro-clases" class="form-control"
                               placeholder="🔍 Filtrar clase (ej. Primaria, 1°A, azul)..."
                               autocomplete="off" oninput="filtrarClasesModal()">
                    </div>
                    <div class="clases-actions-bar">
                        <div style="display:flex; gap:6px; flex-wrap:wrap">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="seleccionarTodasClases(true)">
                                Seleccionar todas
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="seleccionarTodasClases(false)">
                                Limpiar
                            </button>
                        </div>
                        <span id="clases-contador" style="font-size:12px; font-weight:600; color:var(--text-muted)">
                            0 seleccionadas · 0 alumnos
                        </span>
                    </div>
                </div>

                @php
                    $clasesPorNivel = $clasesDisponibles->groupBy('level');
                    $nivelIconos = [
                        'Maternal'     => '🍼',
                        'Preescolar'   => '🎨',
                        'Primaria'     => '📚',
                        'Secundaria'   => '🔬',
                        'Bachillerato' => '🎓',
                        'Preparatoria' => '🎓',
                        'Universidad'  => '🏛️',
                        'Licenciatura' => '🏛️',
                    ];
                @endphp

                <div id="clases-grid" class="niveles-acordeon" role="group" aria-label="Clases disponibles por nivel">
                    @foreach($clasesPorNivel as $nivelNombre => $clasesDelNivel)
                        @php
                            $nivelTotalAlumnos = $clasesDelNivel->sum('total');
                            $nivelIcono = $nivelIconos[$nivelNombre] ?? '📋';
                        @endphp
                        <details class="nivel-grupo" @if($loop->first) open @endif>
                            <summary class="nivel-grupo-header">
                                <div class="nivel-grupo-titulo">
                                    <span class="nivel-chevron" aria-hidden="true">▸</span>
                                    <span>{{ $nivelIcono }}</span>
                                    <strong>{{ $nivelNombre }}</strong>
                                    <span class="nivel-grupo-meta">
                                        {{ $clasesDelNivel->count() }} {{ $clasesDelNivel->count() === 1 ? 'clase' : 'clases' }}
                                        · {{ $nivelTotalAlumnos }} {{ $nivelTotalAlumnos === 1 ? 'alumno' : 'alumnos' }}
                                    </span>
                                </div>
                                <div class="nivel-grupo-acciones" onclick="event.preventDefault(); event.stopPropagation();">
                                    <span class="nivel-sel-badge" hidden></span>
                                    <button type="button" class="nivel-toggle-btn" onclick="alternarNivelClases(this)">
                                        Seleccionar nivel
                                    </button>
                                </div>
                            </summary>
                            <div class="clases-grid">
                                @foreach($clasesDelNivel as $clase)
                                    <label class="formato-opcion clase-card"
                                           data-clase-label="{{ mb_strtolower($clase['label']) }}"
                                           data-clase-total="{{ $clase['total'] }}">
                                        <input type="checkbox"
                                               name="clases[]"
                                               value="{{ $clase['key'] }}"
                                               onchange="actualizarContadorClases()">
                                        <span>
                                            <strong>{{ $clase['label'] }}</strong>
                                            <small>{{ $clase['total'] }} {{ $clase['total'] === 1 ? 'alumno' : 'alumnos' }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>

                <div id="clases-sin-resultados" style="display:none; text-align:center; padding:24px 12px; color:var(--text-muted); font-size:13px">
                    No hay clases que coincidan con tu búsqueda.
                </div>
            @endif
            </div>

            <footer class="formato-modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" onclick="cerrarModalDescargarClases()">Cancelar</button>
                <button type="submit" id="btn-submit-descargar-clases" class="btn btn-primary btn-sm" disabled>
                    📥 Descargar alumnos
                </button>
            </footer>
        </form>
    </section>
</div>

<style>
    .formato-modal-overlay {
        position:fixed; inset:0; z-index:1200; display:none; align-items:center; justify-content:center;
        padding:14px; background:rgba(24,19,17,.58); backdrop-filter:blur(3px);
    }
    .formato-modal-overlay.is-open { display:flex; }
    .formato-modal-panel {
        position:relative; width:min(640px, 100%); max-height:calc(100vh - 28px); overflow:hidden;
        display:flex; flex-direction:column;
        border:1px solid var(--border); border-radius:12px; background:var(--surface);
        color:var(--text); box-shadow:0 22px 64px rgba(24,19,17,.28);
    }
    .formato-modal-panel::before { content:""; position:absolute; inset:0 0 auto; height:4px; background:var(--accent); z-index:3; }
    .formato-modal-header {
        display:flex; align-items:flex-start; justify-content:space-between; gap:16px;
        padding:22px 24px 14px; border-bottom:1px solid var(--border); flex-shrink:0;
    }
    .formato-modal-kicker { display:block; margin-bottom:3px; color:var(--text-muted); font-size:12px; }
    .formato-modal-badge {
        display:inline-block; margin-top:6px; padding:3px 10px; border-radius:999px; font-size:11.5px;
        font-weight:600; background:#dcfce7; color:#16a34a;
    }
    .formato-modal-header h2 { margin:0; font-family:'Bricolage Grotesque',sans-serif; font-size:20px; }
    .formato-tabs {
        display:grid; grid-template-columns:1fr 1fr; gap:4px; padding:4px; margin-bottom:18px;
        border:1px solid var(--border); border-radius:10px; background:var(--surface2);
    }
    .formato-tab {
        display:flex; align-items:center; justify-content:center; gap:8px; padding:9px 12px;
        border:0; border-radius:7px; background:transparent; color:var(--text-muted);
        font:600 13px 'Inter',sans-serif; cursor:pointer; transition:background .15s, color .15s, box-shadow .15s;
    }
    .formato-tab:hover { color:var(--text); }
    .formato-tab.is-active { background:var(--surface); color:var(--accent); box-shadow:0 1px 4px rgba(24,19,17,.14); }
    .formato-opcion {
        display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border);
        border-radius:8px; background:#fff; cursor:pointer; transition:border-color .15s, background .15s;
    }
    .formato-opcion:hover, .formato-opcion.is-selected { border-color:var(--accent); background:#fff8f6; }
    .formato-opcion input[type="checkbox"] {
        -webkit-appearance:none; appearance:none;
        position:relative; width:18px; height:18px; margin:0; flex:0 0 auto;
        border:1.5px solid #cfc9be; border-radius:5px; background:var(--surface);
        cursor:pointer; transition:border-color .15s, background .15s, box-shadow .15s;
    }
    .formato-opcion:hover input[type="checkbox"] { border-color:var(--accent); }
    .formato-opcion input[type="checkbox"]:focus-visible {
        outline:none; box-shadow:0 0 0 3px rgba(226,35,26,.18);
    }
    .formato-opcion input[type="checkbox"]:checked {
        border-color:var(--accent); background:var(--accent);
        box-shadow:0 1px 3px rgba(226,35,26,.28);
    }
    .formato-opcion input[type="checkbox"]:checked::after {
        content:""; position:absolute; left:5px; top:1.5px;
        width:5px; height:9px;
        border:solid #fff; border-width:0 2px 2px 0;
        transform:rotate(42deg);
    }
    .formato-opcion span { display:grid; gap:2px; min-width:0; }
    .formato-opcion strong { font-size:13.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .formato-opcion small { color:var(--text-muted); font-size:12px; }
    .formato-aviso {
        padding:16px; border:1px solid var(--border); border-left:4px solid var(--accent);
        border-radius:8px; background:var(--surface2);
    }
    .formato-aviso strong { display:block; margin-bottom:4px; font-size:14px; }
    .formato-aviso p { margin:0; color:var(--text-muted); font-size:13px; }
    .formato-modal-actions {
        display:flex; justify-content:flex-end; gap:8px; flex-shrink:0;
        background:var(--surface); padding:12px 24px 18px; border-top:1px solid var(--border);
    }
    #form-descargar-clases { display:flex; flex-direction:column; flex:1; min-height:0; }
    .formato-modal-scroll {
        flex:1; min-height:0; overflow-y:auto; padding:16px 18px 16px 24px;
        scrollbar-width:thin; scrollbar-color:#d6d1c8 transparent;
    }
    .formato-modal-scroll::-webkit-scrollbar { width:6px; }
    .formato-modal-scroll::-webkit-scrollbar-track { background:transparent; margin:8px 0; }
    .formato-modal-scroll::-webkit-scrollbar-thumb { background:#d6d1c8; border-radius:999px; }
    .formato-modal-scroll::-webkit-scrollbar-thumb:hover { background:#b8b1a6; }
    .formato-modal-scroll::-webkit-scrollbar-button { display:none; height:0; width:0; }
    .clases-toolbar { display:flex; flex-direction:column; gap:8px; margin-bottom:10px; }
    .clases-actions-bar { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
    .niveles-acordeon {
        display:flex; flex-direction:column; gap:8px;
    }
    .nivel-grupo {
        border:1px solid var(--border); border-radius:10px; background:#fff; overflow:hidden; flex-shrink:0;
    }
    .nivel-grupo-header {
        list-style:none; display:flex; align-items:center; justify-content:space-between; gap:10px;
        padding:10px 14px; background:var(--surface2); cursor:pointer; user-select:none;
        transition:background .15s;
    }
    .nivel-grupo-header::-webkit-details-marker { display:none; }
    .nivel-grupo-header:hover { background:#ebe6dc; }
    .nivel-grupo-titulo { display:flex; align-items:center; gap:8px; min-width:0; flex-wrap:wrap; }
    .nivel-grupo-titulo strong { font-size:13.5px; color:var(--text); }
    .nivel-grupo-meta { font-size:11.5px; color:var(--text-muted); }
    .nivel-chevron {
        display:inline-block; font-size:12px; color:var(--text-muted); transition:transform .15s;
    }
    .nivel-grupo[open] .nivel-chevron { transform:rotate(90deg); color:var(--accent); }
    .nivel-grupo-acciones { display:flex; align-items:center; gap:6px; flex-shrink:0; }
    .nivel-sel-badge {
        padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600;
        background:#fee2e2; color:var(--accent);
    }
    .nivel-toggle-btn {
        padding:4px 9px; border:1px solid var(--border); border-radius:6px; background:#fff;
        color:var(--text-muted); font-size:11.5px; font-weight:600; cursor:pointer; transition:all .15s;
    }
    .nivel-toggle-btn:hover { border-color:var(--accent); color:var(--accent); }
    .clases-grid {
        display:grid; grid-template-columns:repeat(auto-fill, minmax(210px, 1fr)); gap:8px;
        padding:12px; border-top:1px solid var(--border);
    }
    #btn-submit-descargar-clases:disabled { opacity:.55; cursor:not-allowed; }
</style>

@if(session('error'))
    <div class="alert alert-danger">❌ {{ session('error') }}</div>
@endif

@if(session('excel_omitidos') && count(session('excel_omitidos')))
    <div id="aviso-omitidos" style="background:#fffbeb; border:1px solid #fcd34d; color:#92400e; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px; position:relative">
        <button type="button" onclick="cerrarAvisoOmitidos()" aria-label="Cerrar"
                style="position:absolute; top:8px; right:10px; background:none; border:none; font-size:18px; line-height:1; cursor:pointer; color:#92400e">✕</button>
        <strong>Alumnos omitidos (usuario ya registrado):</strong>
        <ul style="margin:6px 0 0 16px">
            @foreach(session('excel_omitidos') as $msg)
                <li>{{ $msg }}</li>
            @endforeach
        </ul>
    </div>
    <script>
        function cerrarAvisoOmitidos() {
            const aviso = document.getElementById('aviso-omitidos');
            if (aviso) aviso.remove();
        }
        setTimeout(cerrarAvisoOmitidos, 6000);
    </script>
@endif
{{-- Mini cards por nivel --}}
@if($porNivel->isNotEmpty())
<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px">
    @php
        $nivelConfig = [
            'Maternal'      => ['icon' => '🍼', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'border' => '#fcd34d'],
            'Preescolar'    => ['icon' => '🎨', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'border' => '#c4b5fd'],
            'Primaria'      => ['icon' => '📚', 'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#93c5fd'],
            'Secundaria'    => ['icon' => '🔬', 'color' => '#059669', 'bg' => '#ecfdf5', 'border' => '#6ee7b7'],
            // Bachillerato/Universidad son los nombres que usa la carga masiva;
            // Preparatoria/Licenciatura se mantienen por datos antiguos ya guardados así.
            'Bachillerato'  => ['icon' => '🎓', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fca5a5'],
            'Preparatoria'  => ['icon' => '🎓', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fca5a5'],
            'Universidad'   => ['icon' => '🏛️', 'color' => '#0d1117', 'bg' => '#f8fafc', 'border' => '#cbd5e1'],
            'Licenciatura'  => ['icon' => '🏛️', 'color' => '#0d1117', 'bg' => '#f8fafc', 'border' => '#cbd5e1'],
        ];
    @endphp
    @foreach($porNivel as $nivel => $total)
        @php
            $cfg = $nivelConfig[$nivel] ?? ['icon' => '📋', 'color' => '#6b7280', 'bg' => '#f9fafb', 'border' => '#e5e7eb'];
        @endphp
        <div style="background:{{ $cfg['bg'] }}; border:1.5px solid {{ $cfg['border'] }}; border-radius:12px;
                    padding:14px 20px; display:flex; align-items:center; gap:12px; min-width:160px; flex:1; max-width:220px">
            <span style="font-size:26px; line-height:1">{{ $cfg['icon'] }}</span>
            <div>
                <div style="font-size:22px; font-weight:800; color:{{ $cfg['color'] }}; line-height:1">{{ $total }}</div>
                <div style="font-size:12px; font-weight:600; color:{{ $cfg['color'] }}; opacity:.8; margin-top:2px">{{ $nivel }}</div>
            </div>
        </div>
    @endforeach
    {{-- Total general --}}
    <div style="background:#0d1117; border:1.5px solid #1e293b; border-radius:12px;
                padding:14px 20px; display:flex; align-items:center; gap:12px; min-width:160px; flex:1; max-width:220px">
        <span style="font-size:26px; line-height:1">👨‍🎓</span>
        <div>
            <div style="font-size:22px; font-weight:800; color:#fff; line-height:1">{{ $students->total() }}</div>
            <div style="font-size:12px; font-weight:600; color:#9ca3af; margin-top:2px">Total alumnos</div>
        </div>
    </div>
</div>
@endif

{{-- Buscador + paginación --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; justify-content:space-between">
            <div style="flex:1; min-width:240px; max-width:460px">
                <input type="search" id="buscador" class="form-control" value="{{ $q }}" autocomplete="off"
                       placeholder="🔍 Buscar por nombre, apellido o usuario MEE...">
            </div>
            <form method="GET" style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-muted)">
                <input type="hidden" name="q" id="per_page_q" value="{{ $q }}">
                <label for="per_page" style="white-space:nowrap">Mostrar</label>
                <select name="per_page" id="per_page" class="form-control" style="width:auto; padding:6px 10px"
                        onchange="this.form.submit()">
                    @foreach([50, 100, 200] as $opcion)
                        <option value="{{ $opcion }}" @selected($perPage == $opcion)>{{ $opcion }}</option>
                    @endforeach
                </select>
                <span style="white-space:nowrap">por página</span>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">👨‍🎓 Alumnos — {{ $school->name }}</span>
        <span id="alumnos-resumen" style="font-size:13px; color:var(--text-muted)">
            {{ $students->total() }} {{ $q !== '' ? ($students->total() === 1 ? 'resultado' : 'resultados') : 'registrados' }}
            @if($students->lastPage() > 1)
                · página {{ $students->currentPage() }} de {{ $students->lastPage() }}
            @endif
        </span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Nivel</th>
                <th>Grado</th>
                <th>Usuario MEE</th>
                <th>Contraseña MEE</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="alumnos-tbody">
            @forelse($students as $student)
            <tr>
                <td><strong>{{ $student->name }} {{ $student->last_name }}</strong></td>
                <td>
                    @if($student->level)
                        <span class="badge badge-info">{{ $student->level }}</span>
                    @else
                        <span style="color:var(--text-muted)">—</span>
                    @endif
                </td>
                <td>{{ $student->grade ?? '—' }}</td>
                <td style="font-family:monospace; font-size:13px">{{ $student->mee_username }}</td>
                <td style="font-size:13px">
                    <div style="display:flex; align-items:center; gap:8px">
                        <span class="pwd-dots" style="font-family:monospace; letter-spacing:2px; color:var(--text-muted)">••••••••</span>
                        <span class="pwd-real" style="font-family:monospace; color:var(--text); display:none">{{ $student->mee_password }}</span>
                        <button type="button" class="pwd-toggle"
                                style="background:none; border:none; cursor:pointer; padding:2px; line-height:1; color:var(--text-muted)"
                                title="Mostrar / ocultar contraseña">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </td>
                <td>
                    @hasanyrole('admin|consultor_digital')
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('students.edit', $student) }}" class="btn btn-secondary btn-sm"
                           style="flex:1; text-align:center; white-space:nowrap">Editar</a>
                        <a href="https://adminservices.macmillaneducacion.com/" target="_blank" rel="noopener"
                           class="btn btn-sm"
                           style="flex:1; text-align:center; white-space:nowrap; background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">
                            Admin Servicios
                        </a>
                        @puedeEliminar($school)
                        <form method="POST" action="{{ route('students.destroy', $student) }}"
                              id="form-eliminar-alumno-{{ $student->id }}"
                              style="flex:1">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                    style="width:100%; white-space:nowrap"
                                    onclick="confirmarEliminar('Eliminar alumno', '¿Deseas eliminar a {{ addslashes($student->name) }} {{ addslashes($student->last_name) }}? Esta acción no se puede deshacer.', 'form-eliminar-alumno-{{ $student->id }}')">
                                Eliminar
                            </button>
                        </form>
                        @endpuedeEliminar
                    </div>
                    @endhasanyrole
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; color:var(--text-muted); padding:40px">
                    @if($q !== '')
                        No se encontró ningún alumno con "{{ $q }}" en este colegio.
                    @else
                        No hay alumnos registrados.
                        <a href="{{ route('schools.students.create', $school) }}">Agrega uno</a>,
                        usa la <strong>carga masiva Excel</strong> o la <strong>carga masiva PDF</strong>.
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div id="alumnos-paginador">
    @if($students->lastPage() > 1)
    <div style="display:flex; align-items:center; justify-content:center; gap:10px; padding:16px; border-top:1px solid var(--border, #e5e7eb)">
        @if($students->onFirstPage())
            <span class="btn btn-secondary btn-sm" style="opacity:.5; cursor:default">← Anterior</span>
        @else
            <a href="{{ $students->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Anterior</a>
        @endif

        <span style="font-size:13px; color:var(--text-muted)">
            Página {{ $students->currentPage() }} de {{ $students->lastPage() }}
        </span>

        @if($students->hasMorePages())
            <a href="{{ $students->nextPageUrl() }}" class="btn btn-secondary btn-sm">Siguiente →</a>
        @else
            <span class="btn btn-secondary btn-sm" style="opacity:.5; cursor:default">Siguiente →</span>
        @endif
    </div>
    @endif
    </div>
</div>


<script>
// ===== Buscador: consulta todos los alumnos del colegio en el servidor =====
const buscador = document.getElementById('buscador');
const perPageQ = document.getElementById('per_page_q');
let esperaBusqueda = null;
let peticionBusqueda = null;

async function buscarAlumnos() {
    const q = buscador.value.trim();
    const url = new URL(window.location.href);
    url.searchParams.delete('page');
    if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');

    peticionBusqueda?.abort();
    peticionBusqueda = new AbortController();

    try {
        const respuesta = await fetch(url, { credentials: 'same-origin', signal: peticionBusqueda.signal });
        const doc = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
        const partes = ['alumnos-tbody', 'alumnos-resumen', 'alumnos-paginador'];
        if (!respuesta.ok || partes.some(id => !doc.getElementById(id))) throw new Error('respuesta inesperada');

        partes.forEach(id => { document.getElementById(id).innerHTML = doc.getElementById(id).innerHTML; });
        history.replaceState(null, '', url);
        perPageQ.value = q;
    } catch (error) {
        if (error.name !== 'AbortError') window.location.href = url; // ej. la sesión expiró
    }
}

buscador.addEventListener('input', function () {
    clearTimeout(esperaBusqueda);
    esperaBusqueda = setTimeout(buscarAlumnos, 300);
});
buscador.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    clearTimeout(esperaBusqueda);
    buscarAlumnos();
});

buscador.focus();
buscador.setSelectionRange(buscador.value.length, buscador.value.length);

// ===== Mostrar / ocultar contraseñas =====
// Delegado en el tbody porque las filas se reemplazan al buscar
document.getElementById('alumnos-tbody').addEventListener('click', function (e) {
    const btn = e.target.closest('.pwd-toggle');
    if (!btn) return;

    const cont = btn.closest('div');
    const dots = cont.querySelector('.pwd-dots');
    const real = cont.querySelector('.pwd-real');

    const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
    const eyeOff  = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;

    if (real.style.display === 'none') {
        real.style.display = '';
        dots.style.display = 'none';
        btn.innerHTML = eyeOff;   // contraseña visible → mostrar ojo tachado
    } else {
        real.style.display = 'none';
        dots.style.display = '';
        btn.innerHTML = eyeOpen;  // contraseña oculta → mostrar ojo normal
    }
});

// ===== Modal: Descargar alumnos por clase =====
const modalClases = document.getElementById('modal-descargar-clases');

function abrirModalDescargarClases() {
    if (!modalClases) return;
    modalClases.classList.add('is-open');
    modalClases.setAttribute('aria-hidden', 'false');
    const filtro = document.getElementById('filtro-clases');
    if (filtro) setTimeout(() => filtro.focus(), 50);
    actualizarContadorClases();
}

function cerrarModalDescargarClases() {
    if (!modalClases) return;
    modalClases.classList.remove('is-open');
    modalClases.setAttribute('aria-hidden', 'true');
}

if (modalClases) {
    modalClases.addEventListener('click', function (e) {
        if (e.target === modalClases) cerrarModalDescargarClases();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalClases.classList.contains('is-open')) {
            cerrarModalDescargarClases();
        }
    });
}

function cambiarModoClases(modo, btn) {
    document.getElementById('descargar-clases-modo').value = modo;
    document.querySelectorAll('[data-clase-modo]').forEach(el => {
        const activo = el === btn;
        el.classList.toggle('is-active', activo);
        el.setAttribute('aria-selected', activo ? 'true' : 'false');
    });
    const desc = document.getElementById('descargar-clases-modo-desc');
    if (desc) {
        desc.textContent = modo === 'unica'
            ? 'Genera un archivo Excel con todos los alumnos seleccionados en una sola hoja.'
            : 'Genera un archivo Excel con una hoja independiente por cada clase seleccionada.';
    }
}

function actualizarContadorClases() {
    const cards = document.querySelectorAll('#clases-grid .clase-card');
    let seleccionadas = 0;
    let totalAlumnos = 0;

    cards.forEach(card => {
        const cb = card.querySelector('input[type="checkbox"]');
        const checked = cb && cb.checked;
        card.classList.toggle('is-selected', Boolean(checked));
        if (checked) {
            seleccionadas++;
            totalAlumnos += parseInt(card.dataset.claseTotal || '0', 10);
        }
    });

    document.querySelectorAll('#clases-grid .nivel-grupo').forEach(grupo => {
        const visibles = Array.from(grupo.querySelectorAll('.clase-card')).filter(c => c.style.display !== 'none');
        const marcadas = visibles.filter(c => c.querySelector('input[type="checkbox"]')?.checked).length;
        const badge = grupo.querySelector('.nivel-sel-badge');
        const btn = grupo.querySelector('.nivel-toggle-btn');
        if (badge) {
            badge.hidden = marcadas === 0;
            badge.textContent = `${marcadas} sel.`;
        }
        if (btn) {
            const todasMarcadas = visibles.length > 0 && marcadas === visibles.length;
            btn.textContent = todasMarcadas ? 'Quitar nivel' : 'Seleccionar nivel';
        }
    });

    const contador = document.getElementById('clases-contador');
    if (contador) {
        const labelClases = seleccionadas === 1 ? 'seleccionada' : 'seleccionadas';
        const labelAlumnos = totalAlumnos === 1 ? 'alumno' : 'alumnos';
        contador.textContent = `${seleccionadas} ${labelClases} · ${totalAlumnos} ${labelAlumnos}`;
    }

    const submitBtn = document.getElementById('btn-submit-descargar-clases');
    if (submitBtn) {
        submitBtn.disabled = seleccionadas === 0;
    }
}

function alternarNivelClases(btn) {
    const grupo = btn.closest('.nivel-grupo');
    if (!grupo) return;
    const visibles = Array.from(grupo.querySelectorAll('.clase-card')).filter(c => c.style.display !== 'none');
    const todasMarcadas = visibles.length > 0 && visibles.every(c => c.querySelector('input[type="checkbox"]')?.checked);
    visibles.forEach(card => {
        const cb = card.querySelector('input[type="checkbox"]');
        if (cb) cb.checked = !todasMarcadas;
    });
    if (!todasMarcadas) grupo.open = true;
    actualizarContadorClases();
}

function seleccionarTodasClases(marcar) {
    document.querySelectorAll('#clases-grid .clase-card').forEach(card => {
        if (card.style.display === 'none') return;
        const cb = card.querySelector('input[type="checkbox"]');
        if (cb) cb.checked = marcar;
    });
    actualizarContadorClases();
}

function filtrarClasesModal() {
    const q = (document.getElementById('filtro-clases')?.value || '').trim().toLowerCase();
    let visibles = 0;

    document.querySelectorAll('#clases-grid .nivel-grupo').forEach(grupo => {
        let visiblesEnNivel = 0;
        grupo.querySelectorAll('.clase-card').forEach(card => {
            const match = !q || (card.dataset.claseLabel || '').includes(q);
            card.style.display = match ? '' : 'none';
            if (match) {
                visibles++;
                visiblesEnNivel++;
            }
        });
        grupo.style.display = visiblesEnNivel > 0 ? '' : 'none';
        if (q && visiblesEnNivel > 0) grupo.open = true;
    });

    const vacio = document.getElementById('clases-sin-resultados');
    if (vacio) vacio.style.display = visibles === 0 ? '' : 'none';
    actualizarContadorClases();
}
</script>
@endsection