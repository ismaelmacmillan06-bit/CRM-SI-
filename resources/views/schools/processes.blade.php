@extends('layouts.app')

@section('title', 'Acciones de arranque — ' . $school->name)

@section('content')
<div style="display:flex; gap:10px; margin-bottom:24px; align-items:center">
    <a href="{{ route('schools.show', $school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
</div>

@foreach($school->schoolLevels as $schoolLevel)
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-title">🎓 {{ $schoolLevel->level->name }}</span>
        @php
            $total = $schoolLevel->processes->count();
            $done  = $schoolLevel->processes->where('status', 'done')->count();
            $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
        @endphp
        <span style="font-size:13px; color:var(--text-muted)">{{ $done }}/{{ $total }} completados</span>
    </div>

    {{-- Barra de progreso --}}
    <div style="padding:0 24px 0; margin-top:16px">
        <div style="background:var(--surface2); border-radius:20px; height:6px; overflow:hidden">
            <div style="height:100%; width:{{ $pct }}%; background:var(--accent);
                        border-radius:20px; transition:width 0.3s"></div>
        </div>
    </div>

    <table class="table" style="margin-top:8px">
        <thead>
            <tr>
                <th>#</th>
                <th>Proceso</th>
                <th>Status</th>
                <th>Completado por</th>
                <th>Fecha</th>
                <th>Notas</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($schoolLevel->processes->sortBy('process.order') as $slp)
            <tr>
                <td style="color:var(--text-muted); font-size:13px">{{ $slp->process->order }}</td>
                <td><strong>{{ $slp->process->name }}</strong></td>
                <td>
                    @if($slp->status === 'done')
                        <span class="badge badge-success">✅ Completado</span>
                    @elseif($slp->status === 'in_progress')
                        <span class="badge badge-warning">🔄 En progreso</span>
                    @else
                        <span class="badge badge-gray">⏳ Pendiente</span>
                    @endif
                </td>
                <td style="font-size:13px">{{ $slp->completedBy->user->name ?? '—' }}</td>
                <td style="font-size:13px; color:var(--text-muted)">
                    {{ $slp->completed_at ? \Carbon\Carbon::parse($slp->completed_at)->format('d/m/Y') : '—' }}
                </td>
                <td style="font-size:13px; color:var(--text-muted)">{{ $slp->notes ?? '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('schools.processes.update', [$school, $slp]) }}"
                          style="display:flex; gap:6px; align-items:center">
                        @csrf @method('POST')
                        <select name="status" class="form-control" style="width:140px; padding:6px 10px; font-size:12px">
                            <option value="pending"     {{ $slp->status === 'pending'     ? 'selected' : '' }}>⏳ Pendiente</option>
                            <option value="in_progress" {{ $slp->status === 'in_progress' ? 'selected' : '' }}>🔄 En progreso</option>
                            <option value="done"        {{ $slp->status === 'done'        ? 'selected' : '' }}>✅ Completado</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endforeach
@endsection