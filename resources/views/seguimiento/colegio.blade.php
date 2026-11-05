@extends('layouts.app')

@section('title', $nombre . ' — ' . $school->name)

@section('content')
@php $total = count($acciones); @endphp

<div style="margin-bottom:20px">
    <a href="{{ route('seguimiento.show', $slug) }}" class="btn btn-secondary btn-sm">← Regresar</a>
</div>

<form method="POST" action="{{ route('seguimiento.guardar', [$slug, $school]) }}">
    @csrf
    <div class="card">
        <div class="card-header">
            <span class="card-title">{{ $icono }} {{ $nombre }} — {{ $school->name }}</span>
            <span style="font-size:13px; color:var(--text-muted)">{{ $hechas }}/{{ $total }} completadas</span>
        </div>

        <div style="padding:16px 24px 0; display:flex; flex-wrap:wrap; align-items:center; gap:6px">
            <span style="font-size:13px; color:var(--text-muted); margin-right:4px">Niveles:</span>
            @forelse($school->levels as $nivel)
                <span class="badge badge-info">{{ $nivel->name }}</span>
            @empty
                <span style="font-size:13px; color:var(--text-muted)">Sin niveles registrados</span>
            @endforelse
        </div>

        <div style="padding:16px 24px 0">
            <div style="background:var(--surface2); border-radius:20px; height:7px; overflow:hidden">
                <div style="height:100%; width:{{ round($hechas / $total * 100) }}%; border-radius:20px;
                            background:{{ $hechas === $total ? 'var(--success)' : 'var(--accent)' }}"></div>
            </div>
        </div>

        <div style="overflow-x:auto">
        <table class="table" style="margin-top:8px">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Acción</th>
                    <th style="width:170px">Estatus</th>
                    <th style="width:170px">Completado</th>
                    <th style="min-width:220px">Notas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($acciones as $clave => $etiqueta)
                @php $guardada = $guardadas[$clave] ?? null; @endphp
                <tr>
                    <td style="color:var(--text-muted); font-size:13px">{{ $loop->iteration }}</td>
                    <td><strong style="font-size:13.5px">{{ $etiqueta }}</strong></td>
                    <td>
                        <select name="acciones[{{ $clave }}][status]" class="form-control" style="padding:6px 8px; font-size:13px">
                            @foreach(\App\Models\SeguimientoAccion::STATUS as $valor => $texto)
                                <option value="{{ $valor }}" @selected(($guardada->status ?? 'pending') === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td style="font-size:12px; color:var(--text-muted)">
                        @if($guardada?->completed_at)
                            {{ $guardada->completed_at->format('d/m/Y') }}
                            <div>{{ $guardada->completedBy->name ?? '—' }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <textarea name="acciones[{{ $clave }}][notes]" rows="2" maxlength="2000" placeholder="Sin notas..."
                                  class="form-control" style="font-size:12.5px; min-height:38px; resize:vertical">{{ $guardada->notes ?? '' }}</textarea>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div style="display:flex; justify-content:flex-end; padding:16px 24px">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </div>
</form>
@endsection
