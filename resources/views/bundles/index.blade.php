@extends('layouts.app')

@section('title', 'Bundles SI')

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('bundles.create') }}" class="btn btn-primary">+ Nuevo Bundle</a>
</div>

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
                    @if($bundle->type == 'ELT')
                        <span class="badge badge-info">ELT</span>
                    @elseif($bundle->type == 'Imagina')
                        <span class="badge badge-success">Imagina</span>
                    @elseif($bundle->type == 'Wikids')
                        <span class="badge badge-warning">Wikids</span>
                    @elseif($bundle->type == 'Plan Lector')
                        <span class="badge badge-gray">Plan Lector</span>
                    @else
                        <span class="badge badge-danger">{{ $bundle->type }}</span>
                    @endif
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