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

<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalConsultar()">🔎 CONSULTAR</button>
</div>

<div id="modal-consultar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
     z-index:999; align-items:center; justify-content:center; padding:20px">
    <div style="background:#fff; border-radius:12px; padding:28px; width:460px; max-width:100%; position:relative">
        <button type="button" onclick="cerrarModalConsultar()" aria-label="Cerrar"
                style="position:absolute; top:14px; right:16px; background:none; border:none; font-size:20px; cursor:pointer; color:#888">✕</button>

        <h3 style="font-family:'Bricolage Grotesque',sans-serif; margin:0 0 6px">🔎 Consultar repositorio</h3>
        <p style="margin:0 0 18px; font-size:14px; color:var(--text-muted)">
            Contacta al consultor digital de este colegio para pedirle apoyo con los archivos.
        </p>

        @if($consultor && $consultor->user)
            <div style="font-size:14px; margin-bottom:18px">
                <strong>{{ $consultor->user->name }}</strong>
                <div style="font-size:12.5px; color:var(--text-muted)">
                    {{ $consultor->user->email ?: 'Sin correo registrado' }}
                    · {{ $consultor->phone ?: 'Sin teléfono registrado' }}
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap">
                @if(!empty($contactoConsultor['email']))
                <button type="button" onclick="contactarConsultorPorCorreo()"
                        style="display:inline-flex; align-items:center; gap:6px; padding:9px 16px; background:#0078d4; color:#fff;
                               border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit">
                    ✉️ Contactar por Correo
                </button>
                @endif
                @if(!empty($contactoConsultor['phone']))
                <button type="button" onclick="contactarConsultorPorWhatsApp()"
                        style="display:inline-flex; align-items:center; gap:6px; padding:9px 16px; background:#25d366; color:#fff;
                               border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit">
                    💬 Contactar por WhatsApp
                </button>
                @endif
                @if(empty($contactoConsultor['email']) && empty($contactoConsultor['phone']))
                <div style="font-size:13px; color:#92400e">
                    Este consultor no tiene correo ni teléfono registrado.
                </div>
                @endif
            </div>
        @else
            <div style="font-size:14px; color:#92400e; background:#fffbeb; border:1px solid #fcd34d; padding:12px 14px; border-radius:8px">
                Este colegio no tiene consultor digital asignado.
            </div>
        @endif
    </div>
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
              onsubmit="if (!this.querySelector('input[type=file]').files.length) { alert('Selecciona al menos un archivo.'); return false; }"
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
    <div style="overflow-x:auto">
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
</div>

<script>
    const CONSULTOR_REPO = @json($contactoConsultor);
    const COLEGIO_REPO = @json($school->name);

    function mensajeConsultar() {
        return 'Que tal ' + CONSULTOR_REPO.nombre + ', espero que estés bien, voy a utilizar los datos de su ' +
               'repositorio del ' + COLEGIO_REPO + ' para armar otra plataforma de ese colegio. ' +
               '¿Me podrías apoyar a cargarlos? Quedo atento. Saludos.';
    }

    function abrirModalConsultar() {
        document.getElementById('modal-consultar').style.display = 'flex';
    }

    function cerrarModalConsultar() {
        document.getElementById('modal-consultar').style.display = 'none';
    }

    function contactarConsultorPorCorreo() {
        if (!CONSULTOR_REPO.email) {
            alert('El consultor digital no tiene correo registrado.');
            return;
        }
        window.open('mailto:' + CONSULTOR_REPO.email +
            '?subject=' + encodeURIComponent('Repositorio — ' + COLEGIO_REPO) +
            '&body=' + encodeURIComponent(mensajeConsultar()));
    }

    function contactarConsultorPorWhatsApp() {
        if (!CONSULTOR_REPO.phone) {
            alert('El consultor digital no tiene teléfono registrado.');
            return;
        }
        let phone = CONSULTOR_REPO.phone.replace(/\D/g, '');
        if (phone.length === 10) phone = '52' + phone;
        window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(mensajeConsultar()));
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModalConsultar(); });
    document.getElementById('modal-consultar').addEventListener('click', e => {
        if (e.target.id === 'modal-consultar') cerrarModalConsultar();
    });
</script>
@endsection
