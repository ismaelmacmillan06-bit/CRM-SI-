@extends('layouts.app')

@section('title', 'Bundles — ' . $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <button onclick="document.getElementById('modal-bundles').style.display='flex'"
            class="btn btn-primary">+ Agregar Bundles</button>
    <button onclick="document.getElementById('modal-import').style.display='flex'"
            style="display:inline-flex; align-items:center; gap:6px; padding:9px 18px;
                   background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;
                   border-radius:8px; font-size:13px; font-weight:500; cursor:pointer;
                   transition:all 0.2s"
            onmouseover="this.style.background='#16a34a';this.style.color='#fff'"
            onmouseout="this.style.background='#f0fdf4';this.style.color='#16a34a'">
        📥 Importación masiva Excel
    </button>
</div>

{{-- Modal importación masiva --}}
<div id="modal-import" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
     z-index:999; align-items:center; justify-content:center; padding:20px">
    <div style="background:#fff; border-radius:12px; padding:32px; width:520px; max-width:100%">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h3 style="font-family:'Bricolage Grotesque',sans-serif; font-size:18px; font-weight:600">
                📥 Importación masiva de Bundles
            </h3>
            <button onclick="document.getElementById('modal-import').style.display='none'"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#666">✕</button>
        </div>

        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;
                    padding:12px 16px; font-size:13px; color:#166534; margin-bottom:20px; line-height:1.6">
            Usa el archivo <strong>altaDeBundlesEnInstitucionesMEE</strong> tal como está.<br>
            Se leen las columnas <strong>B (nombre del bundle)</strong> y <strong>D (cantidad)</strong>.<br>
            Los bundles ya registrados en este colegio se omiten automáticamente.
        </div>

        <form method="POST" action="{{ route('schools.bundles.import', $school) }}"
              enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Archivo Excel (.xlsx / .xlsm)</label>
                <input type="file" name="archivo" accept=".xlsx,.xlsm,.xls"
                       class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Fecha de adopción</label>
                <input type="date" name="acquired_at" class="form-control"
                       value="{{ now()->toDateString() }}">
                <small style="color:var(--text-muted); font-size:11px">
                    Se registrará como la fecha en que el colegio adquirió los materiales.
                </small>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px">
                <button type="button"
                        onclick="document.getElementById('modal-import').style.display='none'"
                        class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">📥 Importar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal selección de bundles --}}
<div id="modal-bundles" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
     z-index:999; align-items:flex-start; justify-content:center; padding:40px 20px; overflow-y:auto">
    <div style="background:#fff; border-radius:12px; padding:32px; width:900px; max-width:100%">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px">
            <h3 style="font-family:'Bricolage Grotesque',sans-serif; font-size:18px; font-weight:600">
                📚 Selecciona los materiales
            </h3>
            <button onclick="document.getElementById('modal-bundles').style.display='none'"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#666">✕</button>
        </div>

        {{-- Paso 1: Seleccionar series --}}
        <div id="step-1">
            <p style="font-size:14px; color:var(--text-muted); margin-bottom:16px">
                Selecciona las series que adoptó el colegio:
            </p>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                {{-- Materiales Castillo --}}
                <div style="border:1px solid var(--border); border-radius:10px; padding:16px">
                    <div style="font-weight:600; font-size:13px; text-transform:uppercase;
                                letter-spacing:1px; color:var(--text-muted); margin-bottom:12px">
                        📗 Materiales Castillo
                    </div>
                    @foreach($series->whereIn('type', ['Imagina', 'Wikids', 'Pienso Contigo', 'Plan Lector']) as $serie)
                    <label style="display:flex; align-items:center; gap:8px; padding:8px 10px;
                                  border:1px solid var(--border); border-radius:8px; margin-bottom:6px;
                                  cursor:pointer; font-size:13px" class="serie-label">
                        <input type="checkbox" class="serie-check" value="{{ $serie->serie }}"
                               data-type="{{ $serie->type }}" style="accent-color:var(--accent)">
                        <span>{{ $serie->serie }}</span>
                        <small style="color:var(--text-muted); margin-left:auto">{{ $serie->type }}</small>
                    </label>
                    @endforeach
                </div>

                {{-- Materiales Macmillan ELT --}}
                <div style="border:1px solid var(--border); border-radius:10px; padding:16px">
                    <div style="font-weight:600; font-size:13px; text-transform:uppercase;
                                letter-spacing:1px; color:var(--text-muted); margin-bottom:12px">
                        📘 Materiales Macmillan ELT
                    </div>
                    @foreach($series->whereIn('type', ['ELT']) as $serie)
                    <label style="display:flex; align-items:center; gap:8px; padding:8px 10px;
                                  border:1px solid var(--border); border-radius:8px; margin-bottom:6px;
                                  cursor:pointer; font-size:13px" class="serie-label">
                        <input type="checkbox" class="serie-check" value="{{ $serie->serie }}"
                               data-type="{{ $serie->type }}" style="accent-color:var(--accent)">
                        <span>{{ $serie->serie }}</span>
                        <small style="color:var(--text-muted); margin-left:auto">{{ $serie->type }}</small>
                    </label>
                    @endforeach
                </div>

                {{-- Complementos --}}
                @if($series->where('type', 'Complemento')->count())
                <div style="border:1px solid var(--border); border-radius:10px; padding:16px; grid-column:1/-1">
                    <div style="font-weight:600; font-size:13px; text-transform:uppercase;
                                letter-spacing:1px; color:var(--text-muted); margin-bottom:12px">
                        🧩 Complementos
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px">
                    @foreach($series->where('type', 'Complemento') as $serie)
                    <label style="display:flex; align-items:center; gap:8px; padding:8px 10px;
                                  border:1px solid var(--border); border-radius:8px;
                                  cursor:pointer; font-size:13px" class="serie-label">
                        <input type="checkbox" class="serie-check" value="{{ $serie->serie }}"
                               data-type="{{ $serie->type }}" style="accent-color:var(--accent)">
                        <span>{{ $serie->serie }}</span>
                        <small style="color:var(--text-muted); margin-left:auto">{{ $serie->type }}</small>
                    </label>
                    @endforeach
                    </div>
                </div>
                @endif
            </div>

            <div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end">
                <button onclick="document.getElementById('modal-bundles').style.display='none'"
                        class="btn btn-secondary">Cancelar</button>
                <button onclick="loadBundles()" class="btn btn-primary">Ver títulos →</button>
            </div>
        </div>

        {{-- Paso 2: Seleccionar títulos --}}
        <div id="step-2" style="display:none">
            <button onclick="backToStep1()" class="btn btn-secondary btn-sm" style="margin-bottom:16px">
                ← Cambiar series
            </button>
            <form method="POST" action="{{ route('schools.bundles.store', $school) }}">
                @csrf
                <div style="display:flex; gap:16px; margin-bottom:16px; align-items:center">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Fecha de adopción</label>
                        <input type="date" name="acquired_at" class="form-control" style="width:200px">
                    </div>
                </div>

                <div id="bundles-container"></div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px">
                    <button type="button"
                            onclick="document.getElementById('modal-bundles').style.display='none'"
                            class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">✅ Agregar seleccionados</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Tabla de bundles del colegio --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">📚 Bundles — {{ $school->name }}</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $schoolBundles->count() }} registrados</span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Serie</th>
                <th>Título</th>
                <th>Tipo</th>
                <th>Nivel</th>
                <th>Grado</th>
                <th>Rol</th>
                <th>Cantidad</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schoolBundles as $bundle)
            @php $historial = $resurtidosPorBundle[$bundle->id] ?? collect(); @endphp
            <tr>
                <td style="font-size:13px; color:var(--text-muted)">{{ $bundle->serie }}</td>
                <td><strong style="font-size:13px">{{ $bundle->name }}</strong></td>
                <td><span class="badge badge-info">{{ $bundle->type }}</span></td>
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
                    {{ $bundle->pivot->quantity }}
                    @if($historial->count())
                        <button onclick="verHistorial({{ $bundle->id }}, {{ json_encode($bundle->name) }})"
                                style="background:none; border:none; cursor:pointer; font-size:11px;
                                       color:var(--accent); text-decoration:underline; padding:0; margin-left:4px">
                            ({{ $historial->count() }} resurtido{{ $historial->count() > 1 ? 's' : '' }})
                        </button>
                    @endif
                </td>
                <td style="display:flex; gap:6px; flex-wrap:wrap">
                    <button onclick="abrirResurtido({{ $bundle->id }}, {{ json_encode($bundle->name) }}, {{ $bundle->pivot->quantity }})"
                            style="padding:5px 10px; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;
                                   border-radius:6px; font-size:12px; font-weight:500; cursor:pointer; white-space:nowrap">
                        🔄 Resurtido
                    </button>
                    <form method="POST"
                          action="{{ route('schools.bundles.destroy', [$school, $bundle]) }}"
                          onsubmit="return confirm('¿Eliminar este bundle?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay bundles registrados. Usa el botón <strong>+ Agregar Bundles</strong>.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
function loadBundles() {
    const checks = document.querySelectorAll('.serie-check:checked');
    if (checks.length === 0) {
        alert('Selecciona al menos una serie');
        return;
    }

    const series = Array.from(checks).map(c => c.value);

    fetch('{{ route("api.bundles.by.series") }}?series[]=' + series.join('&series[]='))
        .then(r => r.json())
        .then(data => {
            let html = '';
            for (const [serie, bundles] of Object.entries(data)) {
                html += `<div style="margin-bottom:20px">
                    <div style="font-family:'Bricolage Grotesque',sans-serif; font-weight:600; font-size:14px;
                                padding:8px 12px; background:var(--surface2); border-radius:8px; margin-bottom:8px;
                                display:flex; align-items:center; gap:8px">
                        <input type="checkbox" class="serie-all-check" onclick="toggleSerie(this)"
                               style="accent-color:var(--accent)" checked>
                        ${serie}
                    </div>
                    <div style="padding-left:12px">`;

                // Agrupar por role
                const students = bundles.filter(b => b.role === 'student');
                const teachers = bundles.filter(b => b.role === 'teacher');

                if (students.length) {
                    html += `<div style="font-size:11px; font-weight:600; text-transform:uppercase;
                                         letter-spacing:1px; color:var(--text-muted); margin:8px 0 4px">
                                 👨‍🎓 Alumno</div>`;
                    students.forEach(b => {
                        html += bundleRow(b);
                    });
                }

                if (teachers.length) {
                    html += `<div style="font-size:11px; font-weight:600; text-transform:uppercase;
                                         letter-spacing:1px; color:var(--text-muted); margin:8px 0 4px">
                                 👨‍🏫 Docente</div>`;
                    teachers.forEach(b => {
                        html += bundleRow(b);
                    });
                }

                html += `</div></div>`;
            }

            document.getElementById('bundles-container').innerHTML = html;
            document.getElementById('step-1').style.display = 'none';
            document.getElementById('step-2').style.display = 'block';
        });
}

function bundleRow(b) {
    return `<label style="display:flex; align-items:center; gap:10px; padding:8px 10px;
                           border:1px solid var(--border); border-radius:8px; margin-bottom:4px;
                           cursor:pointer; font-size:13px">
        <input type="checkbox" name="bundle_ids[]" value="${b.id}"
               style="accent-color:var(--accent)" checked>
        <span style="flex:1">${b.name}</span>
        <span style="color:var(--text-muted); font-size:12px">${b.grade ?? ''} ${b.level ?? ''}</span>
        <input type="number" name="quantities[${b.id}]" value="1" min="1"
               style="width:60px; padding:4px 8px; border:1px solid var(--border);
                      border-radius:6px; font-size:12px" onclick="e => e.stopPropagation()">
    </label>`;
}

function toggleSerie(checkbox) {
    const container = checkbox.closest('div').nextElementSibling;
    container.querySelectorAll('input[name="bundle_ids[]"]').forEach(c => {
        c.checked = checkbox.checked;
    });
}

function backToStep1() {
    document.getElementById('step-1').style.display = 'block';
    document.getElementById('step-2').style.display = 'none';
}
</script>

{{-- Modal Resurtido --}}
<div id="modal-resurtido" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
     z-index:1000; align-items:center; justify-content:center; padding:20px">
    <div style="background:#fff; border-radius:12px; padding:32px; width:480px; max-width:100%">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px">
            <h3 style="font-family:'Bricolage Grotesque',sans-serif; font-size:17px; font-weight:600">
                🔄 Registrar Resurtido
            </h3>
            <button onclick="cerrarResurtido()"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#666">✕</button>
        </div>
        <p id="resurtido-bundle-nombre" style="font-size:13px; color:var(--text-muted); margin-bottom:20px"></p>

        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;
                    padding:12px 16px; font-size:13px; color:#166534; margin-bottom:20px">
            Cantidad actual: <strong id="resurtido-cantidad-actual"></strong> unidades.
            El resurtido se sumará al total existente.
        </div>

        <form id="form-resurtido" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="form-label">Cantidad a resuritir <span style="color:red">*</span></label>
                <input type="number" name="cantidad_resurtido" class="form-control"
                       min="1" required placeholder="ej. 5">
            </div>
            <div class="form-group">
                <label class="form-label">Autorizado por</label>
                <input type="text" name="autorizado_por" class="form-control"
                       placeholder="Nombre del director o responsable">
            </div>
            <div class="form-group">
                <label class="form-label">Fecha <span style="color:red">*</span></label>
                <input type="date" name="fecha" class="form-control"
                       value="{{ now()->toDateString() }}" required>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px">
                <button type="button" onclick="cerrarResurtido()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">✅ Confirmar Resurtido</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Historial de Resurtidos --}}
<div id="modal-historial" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
     z-index:1000; align-items:center; justify-content:center; padding:20px">
    <div style="background:#fff; border-radius:12px; padding:32px; width:660px; max-width:100%; max-height:80vh; overflow-y:auto">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px">
            <h3 style="font-family:'Bricolage Grotesque',sans-serif; font-size:17px; font-weight:600">
                📋 Historial de Resurtidos
            </h3>
            <button onclick="document.getElementById('modal-historial').style.display='none'"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#666">✕</button>
        </div>
        <p id="historial-bundle-nombre" style="font-size:13px; color:var(--text-muted); margin-bottom:20px"></p>
        <table class="table" style="font-size:13px">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cantidad anterior</th>
                    <th>Resurtido</th>
                    <th>Nueva cantidad</th>
                    <th>Autorizado por</th>
                    <th>Registrado por</th>
                </tr>
            </thead>
            <tbody id="historial-tbody"></tbody>
        </table>
    </div>
</div>

@php
$resurtidosJson = $resurtidosPorBundle->map(fn($items) => $items->map(fn($r) => [
    'fecha'             => $r->fecha->format('d/m/Y'),
    'cantidad_anterior' => $r->cantidad_anterior,
    'cantidad_resurtido'=> $r->cantidad_resurtido,
    'cantidad_nueva'    => $r->cantidad_nueva,
    'autorizado_por'    => $r->autorizado_por ?? '—',
    'registrado_por'    => $r->user?->name ?? '—',
]))->toJson();
@endphp

<script>
const resurtidosData = @json(json_decode($resurtidosJson));

const baseResurtidoUrl = '{{ url("schools/{$school->id}/bundles") }}';

function abrirResurtido(bundleId, nombre, cantidadActual) {
    document.getElementById('resurtido-bundle-nombre').textContent = nombre;
    document.getElementById('resurtido-cantidad-actual').textContent = cantidadActual;
    document.getElementById('form-resurtido').action = baseResurtidoUrl + '/' + bundleId + '/resurtido';
    document.getElementById('modal-resurtido').style.display = 'flex';
}

function cerrarResurtido() {
    document.getElementById('modal-resurtido').style.display = 'none';
    document.getElementById('form-resurtido').reset();
    document.querySelector('[name="fecha"]').value = '{{ now()->toDateString() }}';
}

function verHistorial(bundleId, nombre) {
    document.getElementById('historial-bundle-nombre').textContent = nombre;
    const registros = resurtidosData[bundleId] || [];
    let html = '';
    registros.forEach(r => {
        html += `<tr>
            <td>${r.fecha}</td>
            <td>${r.cantidad_anterior}</td>
            <td style="color:#16a34a; font-weight:600">+${r.cantidad_resurtido}</td>
            <td><strong>${r.cantidad_nueva}</strong></td>
            <td>${r.autorizado_por}</td>
            <td style="color:var(--text-muted)">${r.registrado_por}</td>
        </tr>`;
    });
    document.getElementById('historial-tbody').innerHTML = html;
    document.getElementById('modal-historial').style.display = 'flex';
}
</script>

@endsection