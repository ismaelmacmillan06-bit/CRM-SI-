@extends('layouts.app')

@section('title', $consultant->user->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('consultants.index') }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <a href="{{ route('consultants.edit', $consultant) }}" class="btn btn-primary btn-sm">✏️ Editar</a>
</div>

<div class="grid-2">
    {{-- Info general --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">👤 Información del consultor</span>
            <span class="badge badge-info">{{ ucfirst($consultant->user->getRoleNames()->first()) }}</span>
        </div>
        <div class="card-body">
            <table style="width:100%; font-size:14px; border-collapse:collapse">
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted); width:40%">Nombre</td>
                    <td style="padding:8px 0"><strong>{{ $consultant->user->name }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Email</td>
                    <td style="padding:8px 0">{{ $consultant->user->email }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Teléfono</td>
                    <td style="padding:8px 0">{{ $consultant->phone ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Zona</td>
                    <td style="padding:8px 0">{{ $consultant->zone ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Registro</td>
                    <td style="padding:8px 0">{{ $consultant->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Colegios asignados</td>
                    <td style="padding:8px 0"><strong>{{ $consultant->schools->count() }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Colegios asignados --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">🏫 Colegios asignados</span>
        </div>
        <div class="card-body" style="padding:0">
            @forelse($consultant->schools as $school)
            <div style="display:flex; align-items:center; justify-content:space-between;
                        padding:14px 24px; border-bottom:1px solid var(--border)">
                <div>
                    <strong style="font-size:14px">{{ $school->name }}</strong>
                    <br>
                    <small style="color:var(--text-muted)">{{ $school->city ?? 'Sin ciudad' }}</small>
                </div>
                <div style="display:flex; align-items:center; gap:8px">
                    @if($school->status === 'activo')
                        <span class="badge badge-success">Activo</span>
                    @elseif($school->status === 'prospecto')
                        <span class="badge badge-warning">Prospecto</span>
                    @else
                        <span class="badge badge-gray">Inactivo</span>
                    @endif
                    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">Ver</a>
                </div>
            </div>
            @empty
            <div style="padding:40px; text-align:center; color:var(--text-muted)">
                Sin colegios asignados
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection