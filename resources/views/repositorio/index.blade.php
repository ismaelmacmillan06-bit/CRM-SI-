@extends('layouts.app')

@section('title', 'Repositorio — ' . $school->name)

@section('content')
@if($school->status === 'inactivo')
<div style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:12px 16px;
            border-radius:8px; margin-bottom:16px; font-size:13.5px">
    ⚠️ Este colegio está <strong>inactivo</strong> — actívalo en
    <a href="{{ route('schools.edit', $school) }}" style="color:#92400e; text-decoration:underline">Editar colegio</a>
    para poder subir archivos aquí.
</div>
@endif

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px">✅ {{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px">❌ {{ $errors->first() }}</div>
@endif

<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
</div>

@hasanyrole('admin|consultor_digital')
@if($school->status !== 'inactivo')
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title">⬆️ Subir archivos</span>
        <span style="font-size:13px; color:var(--text-muted)">PDF, Word o Excel · máx. 10 MB c/u · hasta 10 a la vez</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('schools.repositorio.store', $school) }}" enctype="multipart/form-data"
              style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
            @csrf
            <input type="file" name="archivos[]" multiple required
                   accept=".pdf,.doc,.docx,.xls,.xlsx" class="form-control" style="max-width:420px">
            <button type="submit" class="btn btn-primary">📤 Subir</button>
        </form>
    </div>
</div>
@endif
@endhasanyrole

<div class="card">
    <div class="card-header">
        <span class="card-title">📁 Repositorio — {{ $school->name }}</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $files->count() }} archivo(s)</span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Tamaño</th>
                <th>Subido por</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($files as $file)
            @php
                $ext = strtolower(pathinfo($file->nombre, PATHINFO_EXTENSION));
                $icono = match(true) {
                    $ext === 'pdf'                 => '📕',
                    in_array($ext, ['doc', 'docx']) => '📘',
                    in_array($ext, ['xls', 'xlsx']) => '📗',
                    default                        => '📄',
                };
            @endphp
            <tr>
                <td><span style="margin-right:6px">{{ $icono }}</span><strong>{{ $file->nombre }}</strong></td>
                <td style="font-size:13px">{{ number_format($file->tamano / 1024, 0) }} KB</td>
                <td style="font-size:13px">{{ $file->uploader->name ?? '—' }}</td>
                <td style="font-size:13px">{{ $file->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('schools.repositorio.download', [$school, $file]) }}" class="btn btn-secondary btn-sm">⬇ Descargar</a>
                        @hasanyrole('admin|consultor_digital')
                        <form method="POST" action="{{ route('schools.repositorio.destroy', [$school, $file]) }}"
                              id="form-eliminar-archivo-{{ $file->id }}">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                    data-nombre="{{ $file->nombre }}"
                                    data-form="form-eliminar-archivo-{{ $file->id }}"
                                    onclick="confirmarEliminar('Eliminar archivo', '¿Eliminar ' + this.dataset.nombre + '? Esta acción no se puede deshacer.', this.dataset.form)">
                                Eliminar
                            </button>
                        </form>
                        @endhasanyrole
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay archivos en el repositorio de este colegio.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
