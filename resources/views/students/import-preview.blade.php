@extends('layouts.app')

@section('title', 'Validar carga — ' . $school->name)

@section('content')
@php
    $nNuevos     = count($resumen['nuevos']);
    $nActualizar = count($resumen['actualizar']);
    $nSinCambios = count($resumen['sin_cambios']);
    $nDuplicados = count($resumen['duplicados']);
@endphp

<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('schools.students.index', $school) }}" class="btn btn-secondary btn-sm">← Regresar sin cargar</a>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title">🔍 Validación de carga — {{ $school->name }}</span>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:14px; margin-bottom:20px">
            <div style="border:1.5px solid #6ee7b7; background:#ecfdf5; border-radius:12px; padding:14px 18px">
                <div style="font-size:24px; font-weight:800; color:#059669">{{ $nNuevos }}</div>
                <div style="font-size:12px; color:#047857; font-weight:600">Nuevos (se crearán)</div>
            </div>
            <div style="border:1.5px solid #fcd34d; background:#fffbeb; border-radius:12px; padding:14px 18px">
                <div style="font-size:24px; font-weight:800; color:#b45309">{{ $nActualizar }}</div>
                <div style="font-size:12px; color:#92400e; font-weight:600">Ya existen con cambios</div>
            </div>
            <div style="border:1.5px solid #e5e7eb; background:#f9fafb; border-radius:12px; padding:14px 18px">
                <div style="font-size:24px; font-weight:800; color:#6b7280">{{ $nSinCambios }}</div>
                <div style="font-size:12px; color:#6b7280; font-weight:600">Ya existen sin cambios</div>
            </div>
            @if($nDuplicados > 0)
            <div style="border:1.5px solid #e5e7eb; background:#f9fafb; border-radius:12px; padding:14px 18px">
                <div style="font-size:24px; font-weight:800; color:#6b7280">{{ $nDuplicados }}</div>
                <div style="font-size:12px; color:#6b7280; font-weight:600">Repetidos en el archivo</div>
            </div>
            @endif
        </div>

        <form method="POST" action="{{ route('schools.students.import-confirm', $school) }}"
              style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <a href="{{ route('schools.students.index', $school) }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" name="modo" value="solo_nuevos" class="btn btn-primary"
                    {{ $nNuevos === 0 ? 'disabled' : '' }}>
                Solo cargar nuevos ({{ $nNuevos }})
            </button>
            <button type="submit" name="modo" value="nuevos_y_actualizar" class="btn btn-danger"
                    {{ ($nNuevos + $nActualizar) === 0 ? 'disabled' : '' }}>
                Cargar nuevos y actualizar existentes ({{ $nNuevos + $nActualizar }})
            </button>
        </form>
    </div>
</div>

@if($nActualizar > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title">✏️ Alumnos que ya existen y se actualizarían</span>
        <span style="font-size:13px; color:var(--text-muted)">Solo se actualiza lo que cambió</span>
    </div>
    <div style="overflow-x:auto; max-height:420px; overflow-y:auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Fila</th>
                    <th>Usuario MEE</th>
                    <th>Alumno</th>
                    <th>Cambios</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumen['actualizar'] as $item)
                <tr>
                    <td style="font-size:13px">{{ $item['fila'] }}</td>
                    <td style="font-family:monospace; font-size:12.5px">{{ $item['usuario'] }}</td>
                    <td style="font-size:13px">{{ $item['nombre'] }}</td>
                    <td style="font-size:12.5px">
                        @foreach($item['cambios'] as $cambio)
                            <div>
                                <strong>{{ $cambio['campo'] }}:</strong>
                                <span style="color:var(--text-muted)">{{ $cambio['antes'] !== null && $cambio['antes'] !== '' ? $cambio['antes'] : '—' }}</span>
                                → <span style="color:#059669; font-weight:600">{{ $cambio['despues'] }}</span>
                            </div>
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($nNuevos > 0)
<div class="card">
    <div class="card-header">
        <span class="card-title">🆕 Alumnos nuevos</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $nNuevos }} registro(s)</span>
    </div>
    <div style="overflow-x:auto; max-height:360px; overflow-y:auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Fila</th>
                    <th>Nombre</th>
                    <th>Usuario MEE</th>
                    <th>Nivel</th>
                    <th>Grado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumen['nuevos'] as $f)
                <tr>
                    <td style="font-size:13px">{{ $f['fila'] }}</td>
                    <td style="font-size:13px">{{ trim($f['nombre'] . ' ' . $f['apellido']) }}</td>
                    <td style="font-family:monospace; font-size:12.5px">{{ $f['usuario'] }}</td>
                    <td style="font-size:13px">{{ $f['nivel'] ?: '—' }}</td>
                    <td style="font-size:13px">{{ $f['grado'] ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
