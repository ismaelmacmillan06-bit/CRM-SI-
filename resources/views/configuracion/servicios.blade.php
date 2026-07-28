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
                <div style="display:flex; align-items:center; gap:12px; padding:12px 16px;
                            border:1px solid var(--border); border-radius:10px; background:var(--surface)">
                    <span style="font-size:22px; width:32px; text-align:center">{{ $type->icon }}</span>
                    <div style="flex:1">
                        <div style="font-weight:600; font-size:14px; color:var(--text)">{{ $type->name }}</div>
                        <div style="font-size:11.5px; color:var(--text-muted); margin-top:1px">
                            {{ $type->schools()->count() }} colegio(s) asignados
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px">
                        {{-- Badge activo/inactivo --}}
                        <span style="font-size:11px; font-weight:600; padding:3px 10px; border-radius:999px;
                                     background:{{ $type->active ? '#f0fdf4' : '#f1f5f9' }};
                                     color:{{ $type->active ? '#15803d' : '#64748b' }}">
                            {{ $type->active ? 'Activo' : 'Inactivo' }}
                        </span>
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
                    <div style="margin-top:14px; text-align:right">
                        <button type="submit" class="btn btn-primary">Guardar servicio</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

</div>
@endsection
