@extends('layouts.app')

@section('title', 'Editar Colegio')

@section('content')
<div style="max-width: 800px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar colegio</span>
            <a href="{{ route('schools.index') }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('schools.update', $school) }}">
                @csrf @method('PUT')

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nombre del colegio *</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $school->name) }}" required>
                        @error('name')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nexus ID</label>
                        <input type="text" name="nexus_id" class="form-control"
                               value="{{ old('nexus_id', $school->nexus_id) }}">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Estado *</label>
                        <select name="city" class="form-control">
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
                                    {{ old('city', $school->city) == $estado ? 'selected' : '' }}>
                                    {{ $estado }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="address" class="form-control"
                               value="{{ old('address', $school->address) }}">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $school->phone) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', $school->email) }}">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Consultor asignado *</label>
                        <select name="consultant_id" class="form-control" required>
                            <option value="">-- Selecciona --</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}"
                                    {{ old('consultant_id', $school->consultant_id) == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->user->name }} — {{ $consultant->zone ?? 'Sin zona' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Responsables del colegio --}}
<div style="margin-bottom:20px">
    <div style="font-family:'Space Grotesk',sans-serif; font-size:15px; font-weight:600;
                margin-bottom:16px; padding-bottom:8px; border-bottom:1px solid var(--border)">
        👥 Responsables del colegio
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label class="form-label">Consultor Digital</label>
            <select name="consultor_digital" class="form-control">
                <option value="">-- Sin asignar --</option>
                @foreach($digitales as $c)
                    <option value="{{ $c->id }}"
                        {{ old('consultor_digital', $responsables['digital']->consultant_id ?? '') == $c->id ? 'selected' : '' }}>
                        {{ $c->user->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Consultor Académico ECA</label>
            <select name="consultor_eca" class="form-control">
                <option value="">-- Sin asignar --</option>
                @foreach($ecas as $c)
                    <option value="{{ $c->id }}"
                        {{ old('consultor_eca', $responsables['eca']->consultant_id ?? '') == $c->id ? 'selected' : '' }}>
                        {{ $c->user->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label class="form-label">Consultor Académico ELT</label>
            <select name="consultor_elt" class="form-control">
                <option value="">-- Sin asignar --</option>
                @foreach($elts as $c)
                    <option value="{{ $c->id }}"
                        {{ old('consultor_elt', $responsables['elt']->consultant_id ?? '') == $c->id ? 'selected' : '' }}>
                        {{ $c->user->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Representante de Ventas</label>
            <select name="representante_ventas" class="form-control">
                <option value="">-- Sin asignar --</option>
                @foreach($representantes as $c)
                    <option value="{{ $c->id }}"
                        {{ old('representante_ventas', $responsables['ventas']->consultant_id ?? '') == $c->id ? 'selected' : '' }}>
                        {{ $c->user->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="prospecto" {{ old('status', $school->status) == 'prospecto' ? 'selected' : '' }}>Prospecto</option>
                            <option value="activo"    {{ old('status', $school->status) == 'activo'    ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo"  {{ old('status', $school->status) == 'inactivo'  ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Niveles educativos</label>
                    <div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:4px">
                        @foreach($levels as $level)
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;
                                      padding:10px 16px; border:1px solid var(--border);
                                      border-radius:8px; font-size:14px;
                                      {{ in_array($level->id, $selectedLevels) ? 'border-color:var(--accent); background:#fff5f6;' : '' }}">
                            <input type="checkbox" name="levels[]" value="{{ $level->id }}"
                                   {{ in_array($level->id, old('levels', $selectedLevels)) ? 'checked' : '' }}
                                   style="accent-color:var(--accent)">
                            {{ $level->name }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $school->notes) }}</textarea>
                </div>

                {{-- Administradores MEE --}}
                <div style="margin-bottom:20px">
                    <div style="font-family:'Space Grotesk',sans-serif; font-size:15px; font-weight:600;
                                margin-bottom:16px; padding-bottom:8px; border-bottom:1px solid var(--border)">
                        🔐 Administradores MEE
                    </div>
                    <div id="mee-admins">
                        @foreach($school->meeAdmins as $admin)
                        <div class="grid-2 mee-row" style="margin-bottom:12px">
                            <div class="form-group" style="margin-bottom:0">
                                <label class="form-label">Usuario</label>
                                <input type="text" name="mee_usernames[]" class="form-control"
                                       value="{{ $admin->username }}">
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label class="form-label">Contraseña</label>
                                <input type="text" name="mee_passwords[]" class="form-control"
                                       value="{{ $admin->password_plain }}">
                            </div>
                        </div>
                        @endforeach
                        @if($school->meeAdmins->isEmpty())
                        <div class="grid-2 mee-row" style="margin-bottom:12px">
                            <div class="form-group" style="margin-bottom:0">
                                <label class="form-label">Usuario</label>
                                <input type="text" name="mee_usernames[]" class="form-control">
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label class="form-label">Contraseña</label>
                                <input type="text" name="mee_passwords[]" class="form-control">
                            </div>
                        </div>
                        @endif
                    </div>
                    <button type="button" onclick="addMeeAdmin()" class="btn btn-secondary btn-sm" style="margin-top:8px">
                        + Agregar otro administrador
                    </button>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('schools.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addMeeAdmin() {
    const container = document.getElementById('mee-admins');
    const row = document.createElement('div');
    row.className = 'grid-2 mee-row';
    row.style.marginBottom = '12px';
    row.innerHTML = `
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Usuario</label>
            <input type="text" name="mee_usernames[]" class="form-control">
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Contraseña</label>
            <input type="text" name="mee_passwords[]" class="form-control">
        </div>
    `;
    container.appendChild(row);
}
</script>
@endsection