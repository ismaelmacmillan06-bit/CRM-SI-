@extends('layouts.app')

@section('title', 'Nuevo Consultor')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">👤 Registrar nuevo consultor</span>
            <a href="{{ route('consultants.index') }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('consultants.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name') }}" required>
                        @error('name')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email') }}" required>
                        @error('email')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="password" class="form-control" required>
                        @error('password')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar contraseña *</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone') }}">
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
                                <option value="{{ $estado }}"
                                    {{ old('zone') == $estado ? 'selected' : '' }}>
                                    {{ $estado }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Rol *</label>
                        <select name="role" class="form-control" required>
                            <option value="">-- Selecciona un rol --</option>
                            <option value="admin"                {{ old('role') == 'admin'                ? 'selected' : '' }}>⚙️ Administrador</option>
                            <option value="consultor_digital"    {{ old('role') == 'consultor_digital'    ? 'selected' : '' }}>💻 Consultor Digital</option>
                            <option value="consultor_eca"        {{ old('role') == 'consultor_eca'        ? 'selected' : '' }}>📗 Consultor Académico ECA</option>
                            <option value="consultor_elt"        {{ old('role') == 'consultor_elt'        ? 'selected' : '' }}>📘 Consultor Académico ELT</option>
                            <option value="representante_ventas" {{ old('role') == 'representante_ventas' ? 'selected' : '' }}>🤝 Representante de Ventas</option>
                        </select>
                        @error('role')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Foto de perfil</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small style="color:var(--text-muted)">Máximo 2MB. Formatos: JPG, PNG</small>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('consultants.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Consultor</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection