@extends('layouts.app')

@section('title', 'Colegios')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">🏫 Colegios registrados</span>
        <div style="display:flex; gap:8px; align-items:center">
            @role('admin')
            <button onclick="document.getElementById('modal-importar').style.display='flex'" class="btn btn-secondary">
                ⬆ Importar Excel
            </button>
            @endrole
            @hasanyrole('admin|consultor_digital')
            <a href="{{ route('schools.create') }}" class="btn btn-primary">+ Nuevo Colegio</a>
            @endhasanyrole
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Colegio</th>
                <th>Nexus ID</th>
                <th>Consultor Digital</th>
                <th>ECA</th>
                <th>ELT</th>
                <th>Rep. Ventas</th>
                <th>Niveles</th>
                <th>Status</th>
                <th>Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schools as $school)
            <tr>
                <td>
                    <strong>{{ $school->name }}</strong>
                    @if($school->state || $school->city)
                        <br><small style="color:var(--text-muted)">{{ $school->state ?? $school->city }}</small>
                    @endif
                </td>
                <td>{{ $school->nexus_id ?? '—' }}</td>
                @php
                    $responsables = $school->schoolConsultants->keyBy('role');
                @endphp
                <td style="font-size:13px">
                    {{ $responsables['digital']->consultant->user->name ?? '—' }}
                </td>
                <td style="font-size:13px">
                    {{ $responsables['eca']->consultant->user->name ?? '—' }}
                </td>
                <td style="font-size:13px">
                    {{ $responsables['elt']->consultant->user->name ?? '—' }}
                </td>
                <td style="font-size:13px">
                    {{ $responsables['ventas']->consultant->user->name ?? '—' }}
                </td>
                <td>
                    @foreach($school->levels as $level)
                        <span class="badge badge-info">{{ $level->name }}</span>
                    @endforeach
                </td>
                <td>
                    @if($school->status === 'activo')
                        <span class="badge badge-success">Activo</span>
                    @elseif($school->status === 'prospecto')
                        <span class="badge badge-warning">Prospecto</span>
                    @else
                        <span class="badge badge-gray">Inactivo</span>
                    @endif
                </td>
                <td style="color:var(--text-muted); font-size:13px">
                    {{ $school->created_at->format('d/m/Y') }}
                </td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">Ver</a>
                        @hasanyrole('admin|consultor_digital')
                        <a href="{{ route('schools.edit', $school) }}" class="btn btn-secondary btn-sm">Editar</a>
                        @endhasanyrole
                        @role('admin')
                        <form method="POST" action="{{ route('schools.destroy', $school) }}"
                              onsubmit="return confirm('¿Eliminar este colegio?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                        @endrole
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay colegios registrados aún.
                    @hasanyrole('admin|consultor_digital')
                    <a href="{{ route('schools.create') }}">Registra el primero</a>
                    @endhasanyrole
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@role('admin')
{{-- Modal: Importar colegios --}}
<div id="modal-importar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center">
    <div style="background:#fff; border-radius:12px; padding:32px; width:100%; max-width:520px; box-shadow:0 8px 32px rgba(0,0,0,.18); position:relative">
        <button onclick="document.getElementById('modal-importar').style.display='none'"
                style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:20px; cursor:pointer; color:#888">✕</button>

        <h3 style="margin:0 0 6px">⬆ Importar colegios desde Excel</h3>
        <p style="margin:0 0 12px; color:var(--text-muted); font-size:14px">
            El archivo debe tener cuatro columnas: <strong>Nombre del Colegio</strong>, <strong>Nexus ID</strong>, <strong>Status</strong> y <strong>Estado</strong> (estado de la república). Nexus ID y Estado son opcionales.
        </p>

        <details style="margin-bottom:16px; font-size:13px">
            <summary style="cursor:pointer; color:#C0392B; font-weight:600; user-select:none">
                📋 Ver estados válidos para la columna Estado
            </summary>
            <div style="margin-top:10px; padding:12px; background:#f9f9f9; border-radius:8px; line-height:1.8; color:#444">
                Aguascalientes · Baja California · Baja California Sur · Campeche · Chiapas · Chihuahua ·
                Ciudad de México <em>(o CDMX)</em> · Coahuila · Colima · Durango · Guanajuato · Guerrero ·
                Hidalgo · Jalisco · Estado de México <em>(o Edomex)</em> · Michoacán · Morelos · Nayarit ·
                Nuevo León · Oaxaca · Puebla · Querétaro · Quintana Roo · San Luis Potosí · Sinaloa ·
                Sonora · Tabasco · Tamaulipas · Tlaxcala · Veracruz · Yucatán · Zacatecas
            </div>
        </details>

        <a href="{{ route('schools.plantilla') }}"
           style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#C0392B; text-decoration:none; margin-bottom:20px; font-weight:600">
            📥 Descargar plantilla de ejemplo
        </a>

        <form method="POST" action="{{ route('schools.importar') }}" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom:16px">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text-muted)">
                    Archivo Excel (.xlsx)
                </label>
                <input type="file" name="archivo" accept=".xlsx,.xls" required
                       style="width:100%; padding:10px; border:1.5px dashed #ddd; border-radius:8px; font-size:14px; cursor:pointer">
                @error('archivo')
                    <p style="color:#e74c3c; font-size:12px; margin:4px 0 0">{{ $message }}</p>
                @enderror
            </div>

            <div style="background:#fffbeb; border:1px solid #f59e0b; border-radius:8px; padding:12px; margin-bottom:20px; font-size:13px; color:#92400e">
                <strong>Importante:</strong> Los colegios importados quedarán sin consultor asignado ni niveles.
                Completa esos datos editando cada colegio después.
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end">
                <button type="button"
                        onclick="document.getElementById('modal-importar').style.display='none'"
                        class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Importar colegios</button>
            </div>
        </form>
    </div>
</div>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('modal-importar').style.display = 'flex';
    });
</script>
@endif
@endrole

@endsection