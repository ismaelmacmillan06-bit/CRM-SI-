@extends('layouts.app')

@section('title', 'Editar Consultor')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar consultor</span>
            <a href="{{ route('consultants.index') }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('consultants.update', $consultant) }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $consultant->user->name) }}" required>
                        @error('name')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control"
                               value="{{ $consultant->user->email }}" disabled
                               style="background:var(--surface2); cursor:not-allowed">
                        <small style="color:var(--text-muted)">El email no se puede modificar</small>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $consultant->phone) }}">
                    </div>
                    <div class="form-group">
    <label class="form-label">Estado</label>
    <select name="zone" class="form-control">
        <option value="">-- Selecciona un estado --</option>
        @foreach([
            'Aguascalientes', 'Baja California', 'Baja California Sur',
            'Campeche', 'Chiapas', 'Chihuahua', 'Ciudad de México',
            'Coahuila', 'Colima', 'Durango', 'Guanajuato', 'Guerrero',
            'Hidalgo', 'Jalisco', 'México', 'Michoacán',
            'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla',
            'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa',
            'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz',
            'Yucatán', 'Zacatecas'
        ] as $estado)
            <option value="{{ $estado }}" {{ old('zone', $consultant->zone) == $estado ? 'selected' : '' }}>
                {{ $estado }}
            </option>
        @endforeach
    </select>
</div>
                </div>

                <div class="form-group">
    <label class="form-label">Rol *</label>
    <select name="role" class="form-control" required>
        <option value="">-- Selecciona un rol --</option>
        <option value="admin"                {{ $consultant->user->getRoleNames()->first() == 'admin'                ? 'selected' : '' }}>⚙️ Administrador</option>
        <option value="consultor_digital"    {{ $consultant->user->getRoleNames()->first() == 'consultor_digital'    ? 'selected' : '' }}>💻 Consultor Digital</option>
        <option value="consultor_eca"        {{ $consultant->user->getRoleNames()->first() == 'consultor_eca'        ? 'selected' : '' }}>📗 Consultor Académico ECA</option>
        <option value="consultor_elt"        {{ $consultant->user->getRoleNames()->first() == 'consultor_elt'        ? 'selected' : '' }}>📘 Consultor Académico ELT</option>
        <option value="representante_ventas" {{ $consultant->user->getRoleNames()->first() == 'representante_ventas' ? 'selected' : '' }}>🤝 Representante de Ventas</option>
    </select>
</div>
<div class="form-group">
    <label class="form-label">Foto de perfil</label>
    @if($consultant->photo)
        <div style="margin-bottom:10px">
            <img src="{{ asset('storage/' . $consultant->photo) }}"
                 style="width:60px; height:60px; border-radius:50%; object-fit:cover;">
            <small style="color:var(--text-muted); display:block; margin-top:4px">
                Sube una nueva para reemplazarla
            </small>
        </div>
    @endif
    <input type="file" name="photo" class="form-control" accept="image/*">
</div>
                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('consultants.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection