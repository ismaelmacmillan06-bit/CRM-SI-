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
                        @if(auth()->user()->hasRole('admin'))
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $consultant->user->email) }}" required>
                            @error('email')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                        @else
                            <input type="email" class="form-control"
                                   value="{{ $consultant->user->email }}" disabled
                                   style="background:var(--surface2); cursor:not-allowed">
                            <small style="color:var(--text-muted)">El email solo puede modificarlo el administrador</small>
                        @endif
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
    <label class="form-label">Rol</label>
    @if(auth()->user()->hasRole('admin'))
        <select name="role" class="form-control" required>
            <option value="">-- Selecciona un rol --</option>
            <option value="admin"                {{ $consultant->user->getRoleNames()->first() == 'admin'                ? 'selected' : '' }}>⚙️ Administrador</option>
            <option value="consultor_digital"    {{ $consultant->user->getRoleNames()->first() == 'consultor_digital'    ? 'selected' : '' }}>💻 Consultor Digital</option>
            <option value="consultor_eca"        {{ $consultant->user->getRoleNames()->first() == 'consultor_eca'        ? 'selected' : '' }}>📗 Consultor Académico ECA</option>
            <option value="consultor_elt"        {{ $consultant->user->getRoleNames()->first() == 'consultor_elt'        ? 'selected' : '' }}>📘 Consultor Académico ELT</option>
            <option value="representante_ventas" {{ $consultant->user->getRoleNames()->first() == 'representante_ventas' ? 'selected' : '' }}>🤝 Representante de Ventas</option>
        </select>
    @else
        <input type="text" class="form-control"
               value="{{ $consultant->user->getRoleNames()->first() }}" disabled
               style="background:var(--surface2); cursor:not-allowed">
        <small style="color:var(--text-muted)">El rol solo puede modificarlo el administrador</small>
    @endif
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

    {{-- Sección contraseña (solo admin) --}}
    @if(auth()->user()->hasRole('admin'))
    <div class="card" style="margin-top:24px">
        <div class="card-header">
            <span class="card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     style="vertical-align:-2px; margin-right:6px">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Cambiar contraseña
            </span>
        </div>
        <div class="card-body">

            @if(session('success_password'))
                <div style="background:#f0fff4; border:1px solid #bbf7d0; color:#166534;
                            border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:13px">
                    {{ session('success_password') }}
                </div>
            @endif

            @if($errors->has('password'))
                <div style="background:#fff0f2; border:1px solid #f8c4cb; color:#c81a30;
                            border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:13px">
                    {{ $errors->first('password') }}
                </div>
            @endif

            <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px">
                La contraseña actual está cifrada y no puede mostrarse. Ingresa una nueva para reemplazarla.
            </p>

            <form method="POST" action="{{ route('consultants.password', $consultant) }}">
                @csrf @method('PATCH')

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nueva contraseña</label>
                        <div style="position:relative">
                            <input type="password" name="password" id="new_password" class="form-control"
                                   placeholder="Mínimo 8 caracteres" style="padding-right:42px">
                            <button type="button" onclick="togglePass('new_password', this)"
                                    style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                                           background:none; border:none; cursor:pointer; color:var(--text-muted); padding:0; display:flex">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar contraseña</label>
                        <div style="position:relative">
                            <input type="password" name="password_confirmation" id="confirm_password" class="form-control"
                                   placeholder="Repite la contraseña" style="padding-right:42px">
                            <button type="button" onclick="togglePass('confirm_password', this)"
                                    style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                                           background:none; border:none; cursor:pointer; color:var(--text-muted); padding:0; display:flex">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end">
                    <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>

<script>
function togglePass(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword
        ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
        : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
}
</script>
@endsection