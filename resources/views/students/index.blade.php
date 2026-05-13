@extends('layouts.app')

@section('title', 'Alumnos — ' . $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <a href="{{ route('schools.students.create', $school) }}" class="btn btn-primary">+ Nuevo Alumno</a>
    <button onclick="document.getElementById('modal-pdf').style.display='flex'"
            class="btn btn-secondary">📄 Carga masiva PDF</button>

         <form method="POST" action="{{ route('schools.students.destroy-all', $school) }}"
      onsubmit="return confirm('⚠️ ¿Estás seguro? Esto eliminará TODOS los alumnos de este colegio.')">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-danger">🗑️ Borrar todos</button>
</form>   
</div>

{{-- Modal PDF --}}
<div id="modal-pdf" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
     z-index:999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:32px; width:500px; max-width:90%">
        <h3 style="font-family:'Space Grotesk',sans-serif; margin-bottom:20px">📄 Carga masiva desde PDF</h3>
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
                <td style="font-family:monospace; font-size:13px">{{ $student->mee_password }}</td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('students.edit', $student) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <form method="POST" action="{{ route('students.destroy', $student) }}"
                              onsubmit="return confirm('¿Eliminar este alumno?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
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
const buscador = document.getElementById('buscador');
const contador = document.getElementById('contador');
const filas    = document.querySelectorAll('tbody tr');

buscador.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    let visibles = 0;

    filas.forEach(fila => {
        const texto = fila.innerText.toLowerCase();
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
</script>
@endsection