@extends('layouts.app')

@section('title', $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('schools.index') }}" class="btn btn-secondary btn-sm">← Regresar</a>
    <a href="{{ route('schools.edit', $school) }}" class="btn btn-primary btn-sm">✏️ Editar</a>
    <a href="{{ route('schools.processes.index', $school) }}" class="btn btn-secondary btn-sm">⚙️ Ver Procesos</a>
    <a href="{{ route('schools.teachers.index', $school) }}" class="btn btn-secondary btn-sm">👨‍🏫 Docentes</a>
    <a href="{{ route('schools.tickets.index', $school) }}" class="btn btn-secondary btn-sm">🎫 Tickets</a>
    <a href="{{ route('schools.visits.index', $school) }}" class="btn btn-secondary btn-sm">📅 Visitas</a>
    <a href="{{ route('schools.students.index', $school) }}" class="btn btn-secondary btn-sm">👨‍🎓 Alumnos</a>
    <a href="{{ route('schools.bundles.index', $school) }}" class="btn btn-secondary btn-sm">📚 Bundles</a>
</div>

<div class="grid-2" style="margin-bottom:24px">
    {{-- Info general --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">📋 Información general</span>
            @if($school->status === 'activo')
                <span class="badge badge-success">Activo</span>
            @elseif($school->status === 'prospecto')
                <span class="badge badge-warning">Prospecto</span>
            @else
                <span class="badge badge-gray">Inactivo</span>
            @endif
        </div>
        <div class="card-body">
            <table style="width:100%; font-size:14px; border-collapse:collapse">
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted); width:40%">Nexus ID</td>
                    <td style="padding:8px 0"><strong>{{ $school->nexus_id ?? '—' }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Ciudad</td>
                    <td style="padding:8px 0">{{ $school->city ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Dirección</td>
                    <td style="padding:8px 0">{{ $school->address ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Teléfono</td>
                    <td style="padding:8px 0">{{ $school->phone ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Email</td>
                    <td style="padding:8px 0">{{ $school->email ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Consultor</td>
                    <td style="padding:8px 0">{{ $school->consultant->user->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Registro</td>
                    <td style="padding:8px 0">{{ $school->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:var(--text-muted)">Niveles</td>
                    <td style="padding:8px 0">
                        @foreach($school->levels as $level)
                            <span class="badge badge-info">{{ $level->name }}</span>
                        @endforeach
                    </td>
                </tr>
            </table>
            @if($school->notes)
                <div style="margin-top:16px; padding:12px; background:var(--surface2);
                            border-radius:8px; font-size:13px; color:var(--text-muted)">
                    {{ $school->notes }}
                </div>
            @endif
        </div>
    </div>

    {{-- Acciones de arranque --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">⚙️ Acciones de arranque</span>
            <a href="{{ route('schools.processes.index', $school) }}" class="btn btn-secondary btn-sm">Ver detalle</a>
        </div>
        <div class="card-body">
            @foreach($school->schoolLevels as $schoolLevel)
            <div style="margin-bottom:20px">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px">
                    <strong style="font-size:14px">{{ $schoolLevel->level->name }}</strong>
                    @php
                        $total = $schoolLevel->processes->count();
                        $done  = $schoolLevel->processes->where('status', 'done')->count();
                        $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
                    @endphp
                    <span style="font-size:12px; color:var(--text-muted)">{{ $done }}/{{ $total }}</span>
                </div>
                <div style="background:var(--surface2); border-radius:20px; height:8px; overflow:hidden">
                    <div style="height:100%; width:{{ $pct }}%; background:var(--accent);
                                border-radius:20px; transition:width 0.3s"></div>
                </div>
                <div style="font-size:11px; color:var(--text-muted); margin-top:4px">{{ $pct }}% completado</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection