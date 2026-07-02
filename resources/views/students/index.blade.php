@extends('layouts.app')

@section('title', 'Alumnos — ' . $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    @hasanyrole('admin|consultor_digital')
    <a href="{{ route('schools.students.create', $school) }}" class="btn btn-primary">+ Nuevo Alumno</a>
    <button onclick="document.getElementById('modal-pdf').style.display='flex'"
            class="btn btn-secondary">📄 Carga masiva PDF</button>
    @endhasanyrole
    @role('admin')
    <form method="POST" action="{{ route('schools.students.destroy-all', $school) }}"
          onsubmit="return confirm('⚠️ ¿Estás seguro? Esto eliminará TODOS los alumnos de este colegio.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger">🗑️ Borrar todos</button>
    </form>
    @endrole
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
                        <option value="Preescolar">Preescolar</option>
                        <option value="Primaria">Primaria</option>
                        <option value="Secundaria">Secundaria</option>
                        <option value="Preparatoria">Preparatoria</option>
                        <option value="Licenciatura">Licenciatura</option>
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
                        <a href="{{ route('students.edit', $student) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <form method="POST" action="{{ route('students.destroy', $student) }}"
                              onsubmit="return confirm('¿Eliminar este alumno?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                    @endhasanyrole
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay alumnos registrados. 
                    <a href="{{ route('schools.students.create', $school) }}">Agrega uno</a> 
                    o usa la <strong>carga masiva PDF</strong>.
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