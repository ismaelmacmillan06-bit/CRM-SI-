@extends('layouts.app')

@section('title', 'Docentes — ' . $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <a href="{{ route('schools.teachers.create', $school) }}" class="btn btn-primary">+ Nuevo Docente</a>
    <button onclick="document.getElementById('modal-import-teachers').style.display='flex'"
            style="display:inline-flex; align-items:center; gap:6px; padding:9px 18px;
                   background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;
                   border-radius:8px; font-size:13px; font-weight:500; cursor:pointer; transition:all 0.2s"
            onmouseover="this.style.background='#16a34a';this.style.color='#fff'"
            onmouseout="this.style.background='#f0fdf4';this.style.color='#16a34a'">
        📥 Importación masiva
    </button>
    <a href="{{ route('teachers.template') }}"
       style="display:inline-flex; align-items:center; gap:6px; padding:9px 18px;
              background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;
              border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; transition:all 0.2s"
       onmouseover="this.style.background='#1d4ed8';this.style.color='#fff'"
       onmouseout="this.style.background='#eff6ff';this.style.color='#1d4ed8'">
        📋 Descargar plantilla
    </a>
</div>

{{-- Modal importación masiva --}}
<div id="modal-import-teachers" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
     z-index:999; align-items:center; justify-content:center; padding:20px">
    <div style="background:#fff; border-radius:12px; padding:32px; width:540px; max-width:100%">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h3 style="font-family:'Bricolage Grotesque',sans-serif; font-size:18px; font-weight:600">
                📥 Importación masiva de Docentes
            </h3>
            <button onclick="document.getElementById('modal-import-teachers').style.display='none'"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#666">✕</button>
        </div>

        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;
                    padding:12px 16px; font-size:13px; color:#1e40af; margin-bottom:20px; line-height:1.6">
            Usa la <strong>plantilla Excel</strong> o cualquier archivo con las columnas:<br>
            <strong>A:</strong> Nombre &nbsp;·&nbsp; <strong>B:</strong> Apellidos &nbsp;·&nbsp;
            <strong>C:</strong> Email &nbsp;·&nbsp; <strong>D:</strong> Grado<br>
            <strong>E:</strong> Rol (varios separados por coma) &nbsp;·&nbsp;
            <strong>F:</strong> Materia &nbsp;·&nbsp; <strong>G:</strong> Contraseña MEE
        </div>

        <form method="POST" action="{{ route('schools.teachers.import', $school) }}"
              enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Archivo Excel (.xlsx / .xls)</label>
                <input type="file" name="archivo" accept=".xlsx,.xls,.xlsm"
                       class="form-control" required>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px">
                <button type="button"
                        onclick="document.getElementById('modal-import-teachers').style.display='none'"
                        class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">📥 Importar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">👨‍🏫 Docentes — {{ $school->name }}</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $teachers->count() }} registrados</span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Grado</th>
                <th>Roles</th>
                <th>Materia</th>
                <th>Usuario MEE</th>
                <th>Contraseña MEE</th>
                <th>Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teachers as $teacher)
            <tr>
                <td><strong>{{ $teacher->name }} {{ $teacher->last_name }}</strong></td>
                <td style="font-size:13px; color:var(--text-muted)">{{ $teacher->email ?? '—' }}</td>
                <td>{{ $teacher->grade ?? '—' }}</td>

                <td>
                    @forelse($teacher->roles as $tr)
                        <span class="badge {{ \App\Models\Teacher::ROLE_BADGES[$tr->role] ?? 'badge-gray' }}"
                              style="margin-bottom:2px; display:inline-block">
                            {{ \App\Models\Teacher::ROLES[$tr->role] ?? $tr->role }}
                        </span>
                    @empty
                        <span style="color:var(--text-muted)">—</span>
                    @endforelse
                </td>

                <td>
                    @if($teacher->subject === 'ECA')
                        <span class="badge badge-info">ECA</span>
                    @elseif($teacher->subject === 'ELT')
                        <span class="badge badge-success">ELT</span>
                    @elseif($teacher->subject === 'ambos')
                        <span class="badge badge-warning">Ambos</span>
                    @else
                        <span class="badge badge-gray">—</span>
                    @endif
                </td>

                <td>
                    @if($teacher->mee_username)
                        <span style="font-family:monospace; font-size:13px">{{ $teacher->mee_username }}</span>
                    @else
                        <span style="color:var(--text-muted)">—</span>
                    @endif
                </td>
                <td>
                    @if($teacher->mee_password)
                        <span style="font-family:monospace; font-size:13px">{{ $teacher->mee_password }}</span>
                    @else
                        <span style="color:var(--text-muted)">—</span>
                    @endif
                </td>
                <td style="font-size:13px; color:var(--text-muted)">
                    {{ $teacher->created_at->format('d/m/Y') }}
                </td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <a href="https://adminservices.macmillaneducacion.com/" target="_blank" rel="noopener"
                           class="btn btn-sm"
                           style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">
                            🔗 Admin Servicios
                        </a>
                        <form method="POST" action="{{ route('teachers.destroy', $teacher) }}"
                              onsubmit="return confirm('¿Eliminar este docente?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay docentes registrados para este colegio.
                    <a href="{{ route('schools.teachers.create', $school) }}">Registra el primero</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
