@extends('layouts.app')

@section('title', 'Alumnos — ' . $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    @hasanyrole('admin|consultor_digital')
    <a href="{{ route('schools.students.create', $school) }}" class="btn btn-primary">+ Nuevo Alumno</a>
    <button onclick="document.getElementById('modal-excel').style.display='flex'"
            class="btn btn-secondary">📊 Carga masiva Excel</button>
    <button onclick="document.getElementById('modal-pdf').style.display='flex'"
            class="btn btn-secondary">📄 Carga masiva PDF</button>
    @endhasanyrole
    @role('admin')
    <form method="POST" action="{{ route('schools.students.destroy-all', $school) }}"
          id="form-borrar-todos-alumnos">
        @csrf @method('DELETE')
        <button type="button" class="btn btn-danger"
                onclick="confirmarEliminar('Borrar todos los alumnos', '¿Estás seguro? Esto eliminará TODOS los alumnos de este colegio.', 'form-borrar-todos-alumnos')">
            🗑️ Borrar todos
        </button>
    </form>
    @endrole
</div>

{{-- Modal Excel --}}
<div id="modal-excel" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
     z-index:999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:32px; width:560px; max-width:95%; max-height:90vh; overflow-y:auto; position:relative">
        <button onclick="document.getElementById('modal-excel').style.display='none'"
                style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:20px; cursor:pointer; color:#888">✕</button>

        <h3 style="font-family:'Bricolage Grotesque',sans-serif; margin:0 0 6px">📊 Carga masiva desde Excel</h3>
        <p style="margin:0 0 20px; font-size:14px; color:var(--text-muted)">
            El archivo debe tener estas columnas: <strong>Nombre Completo · Usuario · Contraseña</strong>
            (+ opcionalmente <strong>Clase</strong> en columna D para asignar grado por fila).
        </p>

        <form method="POST" action="{{ route('schools.students.import-excel', $school) }}"
              enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label">Archivo Excel (.xlsx / .xls) *</label>
                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
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
                            <option value="Preparatoria">Preparatoria</option>
                            <option value="Licenciatura">Licenciatura</option>
                        @endforelse
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Grado <span style="font-size:11px; color:var(--text-muted)">(se aplica a todos si col. D está vacía)</span></label>
                    <input type="text" name="grade" class="form-control" placeholder="Ej: 1°A, 2°B">
                </div>
            </div>

            <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:12px; margin-bottom:20px; font-size:13px; color:#0369a1">
                💡 Si la columna D contiene la clase de cada alumno (ej. <em>1°A</em>), se usará como grado individual y sobreescribe el campo de arriba.
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
                            <option value="Preparatoria">Preparatoria</option>
                            <option value="Licenciatura">Licenciatura</option>
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

@if(session('error'))
    <div class="alert alert-danger">❌ {{ session('error') }}</div>
@endif

@if(session('excel_omitidos') && count(session('excel_omitidos')))
    <div style="background:#fffbeb; border:1px solid #fcd34d; color:#92400e; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px">
        <strong>Alumnos omitidos (usuario ya registrado):</strong>
        <ul style="margin:6px 0 0 16px">
            @foreach(session('excel_omitidos') as $msg)
                <li>{{ $msg }}</li>
            @endforeach
        </ul>
    </div>
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
            'Preparatoria'  => ['icon' => '🎓', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fca5a5'],
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
            <div style="font-size:22px; font-weight:800; color:#fff; line-height:1">{{ $students->count() }}</div>
            <div style="font-size:12px; font-weight:600; color:#9ca3af; margin-top:2px">Total alumnos</div>
        </div>
    </div>
</div>
@endif

{{-- Buscador --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <div style="display:flex; gap:10px; align-items:center">
            <input type="text" id="buscador" class="form-control" 
                   placeholder="🔍 Buscar por nombre, apellido o usuario MEE..."
                   style="max-width:400px">
            <span id="contador" style="font-size:13px; color:var(--text-muted)"></span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">👨‍🎓 Alumnos — {{ $school->name }}</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $students->count() }} registrados</span>
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
        <tbody>
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
                    </div>
                    @endhasanyrole
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay alumnos registrados.
                    <a href="{{ route('schools.students.create', $school) }}">Agrega uno</a>,
                    usa la <strong>carga masiva Excel</strong> o la <strong>carga masiva PDF</strong>.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>


<script>
// ===== Buscador (ignora la columna de contraseña) =====
const buscador = document.getElementById('buscador');
const contador = document.getElementById('contador');
const filas    = document.querySelectorAll('tbody tr');

buscador.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    let visibles = 0;

    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length < 4) return; // fila de "no hay alumnos"
        // Solo nombre, nivel, grado y usuario MEE (columnas 0 a 3)
        const texto = [celdas[0], celdas[1], celdas[2], celdas[3]]
            .map(c => c.innerText.toLowerCase()).join(' ');

        if (texto.includes(query)) {
            fila.style.display = '';
            visibles++;
        } else {
            fila.style.display = 'none';
        }
    });

    contador.textContent = query ? `${visibles} resultado(s)` : '';
});

buscador.focus();

// ===== Mostrar / ocultar contraseñas =====
document.querySelectorAll('.pwd-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
        const cont = this.closest('div');
        const dots = cont.querySelector('.pwd-dots');
        const real = cont.querySelector('.pwd-real');

        const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
        const eyeOff  = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;

        if (real.style.display === 'none') {
            real.style.display = '';
            dots.style.display = 'none';
            this.innerHTML = eyeOff;   // contraseña visible → mostrar ojo tachado
        } else {
            real.style.display = 'none';
            dots.style.display = '';
            this.innerHTML = eyeOpen;  // contraseña oculta → mostrar ojo normal
        }
    });
});
</script>
@endsection