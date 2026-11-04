@extends('layouts.app')

@section('title', 'Configuración — Servicios')

@section('content')
<div style="max-width:680px">

    <div class="card">
        <div class="card-header">
            <span class="card-title">⚙️ Servicios contables por colegio</span>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">← Dashboard</a>
        </div>
        <div class="card-body">

            <p style="font-size:13.5px; color:var(--text-muted); margin-bottom:20px; line-height:1.6">
                Aquí defines qué servicios adicionales se pueden marcar por colegio (Global Educa, Lego, etc.).
                Los activos aparecerán en el formulario de edición de cada colegio y en el dashboard.
            </p>

            {{-- Lista de servicios --}}
            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:28px">
                @forelse($types as $type)
                @php $errEditar = $errors->getBag('editar_' . $type->id); @endphp
                <div class="servicio-item" data-editando="{{ $errEditar->any() ? '1' : '0' }}">
                <div class="servicio-vista" style="display:flex; align-items:center; gap:12px; padding:12px 16px;
                            border:1px solid var(--border); border-radius:10px; background:var(--surface)">
                    <span style="font-size:22px; width:32px; text-align:center">{{ $type->icon }}</span>
                    <div style="flex:1">
                        <div style="font-weight:600; font-size:14px; color:var(--text)">{{ $type->name }}</div>
                        <div style="font-size:11.5px; color:var(--text-muted); margin-top:1px">
                            {{ $type->schools()->count() }} colegio(s) asignados
                            @if($type->plataforma && isset($plataformas[$type->plataforma]))
                                · 🧭 {{ $plataformas[$type->plataforma]['nombre'] }}
                            @endif
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px">
                        {{-- Badge activo/inactivo --}}
                        <span style="font-size:11px; font-weight:600; padding:3px 10px; border-radius:999px;
                                     background:{{ $type->active ? '#f0fdf4' : '#f1f5f9' }};
                                     color:{{ $type->active ? '#15803d' : '#64748b' }}">
                            {{ $type->active ? 'Activo' : 'Inactivo' }}
                        </span>
                        {{-- Editar --}}
                        <button type="button" class="servicio-btn-editar"
                                style="padding:5px 10px; border-radius:6px; font-size:12px; font-weight:500;
                                       cursor:pointer; border:1px solid var(--border); background:var(--surface-alt)">
                            ✏️ Editar
                        </button>
                        {{-- Toggle activo --}}
                        <form method="POST" action="{{ route('configuracion.servicios.toggle', $type) }}" style="margin:0">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    style="padding:5px 10px; border-radius:6px; font-size:12px; font-weight:500;
                                           cursor:pointer; border:1px solid var(--border); background:var(--surface-alt)">
                                {{ $type->active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        {{-- Eliminar --}}
                        <form method="POST" action="{{ route('configuracion.servicios.destroy', $type) }}" style="margin:0"
                              onsubmit="return confirm('¿Eliminar el servicio \'{{ $type->name }}\'? Se quitará de todos los colegios que lo tengan.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    style="padding:5px 10px; border-radius:6px; font-size:12px; font-weight:500;
                                           cursor:pointer; border:1px solid #fecaca; background:#fff5f5; color:#dc2626">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Edición en línea --}}
                <form method="POST" action="{{ route('configuracion.servicios.update', $type) }}" class="servicio-editar"
                      style="padding:14px 16px; border:1px solid var(--accent); border-radius:10px; background:var(--surface)">
                    @csrf @method('PATCH')
                    <div style="font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:10px">
                        Editando: {{ $type->name }}
                    </div>
                    <div style="display:grid; grid-template-columns:1fr auto auto; gap:10px; align-items:end">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="name" class="form-control" required maxlength="100"
                                   value="{{ $errEditar->any() ? old('name', $type->name) : $type->name }}">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Ícono</label>
                            <input type="text" name="icon" class="form-control" maxlength="4"
                                   value="{{ $errEditar->any() ? old('icon', $type->icon) : $type->icon }}"
                                   style="width:64px; text-align:center; font-size:20px">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control"
                                   value="{{ $errEditar->any() ? old('color', $type->color) : $type->color }}"
                                   style="width:48px; height:42px; padding:3px; cursor:pointer">
                        </div>
                    </div>
                    <div class="form-group" style="margin:10px 0 0">
                        <label class="form-label">Gestor de plataformas</label>
                        @php $plataformaActual = $errEditar->any() ? old('plataforma', $type->plataforma) : $type->plataforma; @endphp
                        <select name="plataforma" class="form-control">
                            <option value="">Ninguna</option>
                            @foreach($plataformas as $clave => $plataforma)
                                <option value="{{ $clave }}" @selected($plataformaActual === $clave)>{{ $plataforma['nombre'] }}</option>
                            @endforeach
                        </select>
                        <small style="color:var(--text-muted)">Liga este servicio con su apartado de Seguimiento. Puedes cambiar el nombre sin perder la relación.</small>
                    </div>
                    @if($errEditar->any())
                        <small style="display:block; margin-top:6px; color:var(--danger)">{{ $errEditar->first() }}</small>
                    @endif
                    @if($type->schools()->count() > 0)
                        <div style="font-size:11.5px; color:var(--text-muted); margin-top:8px">
                            Los {{ $type->schools()->count() }} colegio(s) que ya lo tienen lo conservan con el nombre nuevo.
                        </div>
                    @endif
                    <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px">
                        <button type="button" class="btn btn-secondary btn-sm servicio-btn-cancelar">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar cambios</button>
                    </div>
                </form>
                </div>
                @empty
                <div style="text-align:center; padding:32px; color:var(--text-muted); font-size:14px">
                    Sin servicios configurados aún.
                </div>
                @endforelse
            </div>

            {{-- Formulario agregar nuevo --}}
            <div style="border-top:1px solid var(--border); padding-top:22px">
                <div style="font-size:13px; font-weight:700; color:var(--text); margin-bottom:14px">
                    + Agregar nuevo servicio
                </div>
                <form method="POST" action="{{ route('configuracion.servicios.store') }}">
                    @csrf
                    <div style="display:grid; grid-template-columns:1fr auto auto; gap:10px; align-items:end">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name') }}" placeholder="Ej: Coding Lab" required>
                            @error('name')
                            <small style="color:var(--danger)">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Ícono</label>
                            <input type="text" name="icon" class="form-control" maxlength="4"
                                   value="{{ old('icon', '📦') }}" style="width:64px; text-align:center; font-size:20px">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control"
                                   value="{{ old('color', '#6366f1') }}"
                                   style="width:48px; height:42px; padding:3px; cursor:pointer">
                        </div>
                    </div>
                    <div class="form-group" style="margin:12px 0 0">
                        <label class="form-label">Gestor de plataformas</label>
                        <select name="plataforma" class="form-control">
                            <option value="">Ninguna</option>
                            @foreach($plataformas as $clave => $plataforma)
                                <option value="{{ $clave }}" @selected(old('plataforma') === $clave)>{{ $plataforma['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('plataforma')
                        <small style="color:var(--danger)">{{ $message }}</small>
                        @enderror
                    </div>
                    <div style="margin-top:14px; text-align:right">
                        <button type="submit" class="btn btn-primary">Guardar servicio</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

</div>

<style>
    .servicio-item .servicio-editar { display: none; }
    .servicio-item[data-editando="1"] .servicio-vista { display: none !important; }
    .servicio-item[data-editando="1"] .servicio-editar { display: block; }
</style>
<script>
    document.querySelectorAll('.servicio-item').forEach(function (item) {
        const form = item.querySelector('.servicio-editar');
        item.querySelector('.servicio-btn-editar')?.addEventListener('click', function () {
            document.querySelectorAll('.servicio-item[data-editando="1"]').forEach(o => { if (o !== item) o.dataset.editando = '0'; });
            item.dataset.editando = '1';
            form.querySelector('input[name="name"]').focus();
        });
        item.querySelector('.servicio-btn-cancelar')?.addEventListener('click', function () {
            form.reset();
            item.dataset.editando = '0';
        });
    });
</script>
@endsection
