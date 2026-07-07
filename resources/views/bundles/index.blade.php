@extends('layouts.app')

@section('title', 'Bundles SI')

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('bundles.create') }}" class="btn btn-primary">+ Nuevo Bundle</a>
    @role('admin')
    <button onclick="document.getElementById('modal-importar-bundles').style.display='flex'" class="btn btn-secondary">
        ⬆ Importar Excel
    </button>
    @endrole
</div>

@if(session('success'))
    <div style="background:#f0fff4; border:1px solid #bbf7d0; color:#166534; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px">
        {{ session('success') }}
    </div>
@endif

@if(session('importar_omitidos') && count(session('importar_omitidos')))
    <div style="background:#fffbeb; border:1px solid #fcd34d; color:#92400e; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px">
        <strong>Filas con errores u omisiones:</strong>
        <ul style="margin:6px 0 0 16px">
            @foreach(session('importar_omitidos') as $msg)
                <li>{{ $msg }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Filtros --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
            <input type="text" id="buscador" class="form-control"
                   placeholder="🔍 Buscar por nombre o serie..."
                   style="max-width:300px">
            <select id="filtro-tipo" class="form-control" style="max-width:180px">
                <option value="">Todos los tipos</option>
                @foreach($tipos as $tipo)
                    <option value="{{ $tipo }}">{{ $tipo }}</option>
                @endforeach
            </select>
            <select id="filtro-nivel" class="form-control" style="max-width:160px">
                <option value="">Todos los niveles</option>
                <option value="Preescolar">Preescolar</option>
                <option value="Primaria">Primaria</option>
                <option value="Secundaria">Secundaria</option>
                <option value="Preparatoria">Preparatoria</option>
            </select>
            <select id="filtro-rol" class="form-control" style="max-width:150px">
                <option value="">Alumno y Docente</option>
                <option value="student">Solo Alumno</option>
                <option value="teacher">Solo Docente</option>
            </select>
            <span id="contador" style="font-size:13px; color:var(--text-muted)"></span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">📚 Catálogo de Bundles SI</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $bundles->count() }} registrados</span>
    </div>
    <table class="table" id="tabla-bundles">
        <thead>
            <tr>
                <th>Serie</th>
                <th>Título</th>
                <th>Tipo</th>
                <th>Nivel</th>
                <th>Grado</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bundles as $bundle)
            <tr data-tipo="{{ $bundle->type }}"
                data-nivel="{{ $bundle->level }}"
                data-rol="{{ $bundle->role }}">
                <td style="font-size:13px; color:var(--text-muted)">{{ $bundle->serie }}</td>
                <td><strong style="font-size:13px">{{ $bundle->name }}</strong></td>
                <td>
                    @php
                        $badgeMap = [
                            'ELT'           => 'badge-info',
                            'Imagina'       => 'badge-success',
                            'Wikids'        => 'badge-warning',
                            'Plan Lector'   => 'badge-gray',
                            'Pienso Contigo'=> 'badge-gray',
                            'Complemento'   => 'badge-gray',
                            'Entrelineas'   => 'badge-success',
                            'Palabrario'    => 'badge-warning',
                        ];
                    @endphp
                    <span class="badge {{ $badgeMap[$bundle->type] ?? 'badge-danger' }}">{{ $bundle->type }}</span>
                </td>
                <td>{{ $bundle->level ?? '—' }}</td>
                <td>{{ $bundle->grade ?? '—' }}</td>
                <td>
                    @if($bundle->role === 'teacher')
                        <span class="badge badge-warning">Docente</span>
                    @else
                        <span class="badge badge-gray">Alumno</span>
                    @endif
                </td>
                <td>
                    <form method="POST" action="{{ route('bundles.destroy', $bundle) }}"
                          onsubmit="return confirm('¿Eliminar este bundle?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay bundles registrados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@role('admin')
{{-- Modal: Importar bundles --}}
<div id="modal-importar-bundles" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center">
    <div style="background:#fff; border-radius:12px; padding:32px; width:100%; max-width:560px; box-shadow:0 8px 32px rgba(0,0,0,.18); position:relative; max-height:90vh; overflow-y:auto">
        <button onclick="document.getElementById('modal-importar-bundles').style.display='none'"
                style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:20px; cursor:pointer; color:#888">✕</button>

        <h3 style="margin:0 0 6px">⬆ Importar bundles desde Excel</h3>
        <p style="margin:0 0 16px; color:var(--text-muted); font-size:14px">
            El archivo debe usar el formato de la plantilla: <strong>Serie / Nombre del Bundle / Grado / Nivel / Rol / Tipo</strong>.
            Los bundles que ya existen (por nombre) se omiten automáticamente.
        </p>

        <a href="{{ route('bundles.plantilla') }}"
           style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#C0392B; text-decoration:none; margin-bottom:20px; font-weight:600">
            📥 Descargar plantilla de ejemplo
        </a>

        @error('archivo')
            <p style="color:#e74c3c; font-size:13px; margin-bottom:12px">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('bundles.importar') }}" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom:16px">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text-muted)">
                    Archivo Excel (.xlsx)
                </label>
                <input type="file" name="archivo" accept=".xlsx,.xls" required
                       style="width:100%; padding:10px; border:1.5px dashed #ddd; border-radius:8px; font-size:14px; cursor:pointer">
            </div>

            <div style="background:#fffbeb; border:1px solid #f59e0b; border-radius:8px; padding:12px; margin-bottom:20px; font-size:13px; color:#92400e">
                <strong>Tipos válidos:</strong> ELT · Plan Lector · Imagina · Wikids · Pienso Contigo · Complemento · Entrelineas · Palabrario<br>
                <strong>Roles válidos:</strong> Alumno · Docente
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end">
                <button type="button"
                        onclick="document.getElementById('modal-importar-bundles').style.display='none'"
                        class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Importar bundles</button>
            </div>
        </form>
    </div>
</div>

@if($errors->has('archivo'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('modal-importar-bundles').style.display = 'flex';
    });
</script>
@endif
@endrole

<script>
const buscador    = document.getElementById('buscador');
const filtroTipo  = document.getElementById('filtro-tipo');
const filtroNivel = document.getElementById('filtro-nivel');
const filtroRol   = document.getElementById('filtro-rol');
const contador    = document.getElementById('contador');
const filas       = document.querySelectorAll('#tabla-bundles tbody tr');

function filtrar() {
    const query = buscador.value.toLowerCase().trim();
    const tipo  = filtroTipo.value;
    const nivel = filtroNivel.value;
    const rol   = filtroRol.value;
    let visibles = 0;

    filas.forEach(fila => {
        const texto     = fila.innerText.toLowerCase();
        const filaTipo  = fila.dataset.tipo;
        const filaNivel = fila.dataset.nivel;
        const filaRol   = fila.dataset.rol;

        const matchTexto  = !query || texto.includes(query);
        const matchTipo   = !tipo  || filaTipo === tipo;
        const matchNivel  = !nivel || filaNivel?.includes(nivel);
        const matchRol    = !rol   || filaRol === rol;

        if (matchTexto && matchTipo && matchNivel && matchRol) {
            fila.style.display = '';
            visibles++;
        } else {
            fila.style.display = 'none';
        }
    });

    contador.textContent = `${visibles} resultado(s)`;
}

buscador.addEventListener('input', filtrar);
filtroTipo.addEventListener('change', filtrar);
filtroNivel.addEventListener('change', filtrar);
filtroRol.addEventListener('change', filtrar);
</script>
@endsection