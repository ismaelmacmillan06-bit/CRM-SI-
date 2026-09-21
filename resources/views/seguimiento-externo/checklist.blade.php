@extends('layouts.app')

@section('title', "{$platform->name} — {$school->name}")

@section('content')

<div style="display:flex; gap:10px; margin-bottom:20px; align-items:center; flex-wrap:wrap">
    <a href="{{ route('seguimiento-externo.school', $school) }}" class="btn btn-secondary btn-sm">← Otra plataforma</a>
</div>

<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-title">{{ $platform->icon }} {{ $platform->name }} — {{ $school->name }}</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $done }}/{{ $total }} completados</span>
    </div>

    <div style="padding:0 24px; margin-top:16px">
        <div style="background:var(--surface2); border-radius:20px; height:6px; overflow:hidden">
            <div style="height:100%; width:{{ $total > 0 ? round($done / $total * 100) : 0 }}%; background:var(--accent);
                        border-radius:20px; transition:width 0.3s"></div>
        </div>
    </div>

    <div style="overflow-x:auto">
    <table class="table" style="margin-top:8px; table-layout:fixed; width:100%">
        <colgroup>
            <col style="width:4%">
            <col style="width:22%">
            <col style="width:12%">
            <col style="width:14%">
            <col style="width:10%">
            <col style="width:22%">
            <col style="width:16%">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Paso</th>
                <th>Status</th>
                <th>Completado por</th>
                <th>Fecha</th>
                <th>Notas</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td style="color:var(--text-muted); font-size:13px">{{ $item->step->order }}</td>

                <td style="overflow:hidden; text-overflow:ellipsis">
                    <strong>{{ $item->step->name }}</strong>
                </td>

                <td>
                    @if($item->status === 'done')
                        <span class="badge badge-success">✅ Completado</span>
                    @else
                        <span class="badge badge-gray">⏳ Pendiente</span>
                    @endif
                </td>

                <td style="font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">
                    {{ $item->completedBy->user->name ?? '—' }}
                </td>

                <td style="font-size:12px; color:var(--text-muted); white-space:nowrap">
                    {{ $item->completed_at ? $item->completed_at->format('d/m/Y') : '—' }}
                </td>

                <td>
                    <textarea name="notes"
                              form="form-step-{{ $item->id }}"
                              rows="2"
                              placeholder="Sin notas..."
                              style="width:100%; font-size:12px; padding:5px 8px; border-radius:6px;
                                     background:var(--surface2); border:1px solid var(--border);
                                     color:var(--text); resize:vertical; min-height:36px;
                                     font-family:inherit; line-height:1.4; box-sizing:border-box">{{ $item->notes }}</textarea>
                </td>

                <td>
                    @hasanyrole('admin|consultor_digital')
                    <form method="POST"
                          action="{{ route('seguimiento-externo.update', [$school, $platform, $item]) }}"
                          id="form-step-{{ $item->id }}"
                          style="display:flex; gap:4px; align-items:center; flex-wrap:wrap">
                        @csrf

                        <select name="status" class="form-control" style="flex:1; min-width:0; padding:6px 6px; font-size:12px">
                            <option value="pending" {{ $item->status === 'pending' ? 'selected' : '' }}>⏳ Pendiente</option>
                            <option value="done"    {{ $item->status === 'done'    ? 'selected' : '' }}>✅ Completado</option>
                        </select>

                        <button type="submit" class="btn btn-primary btn-sm"
                                style="flex-shrink:0; padding:6px 10px; font-size:12px; white-space:nowrap">
                            Guardar
                        </button>
                    </form>
                    @endhasanyrole
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>

@endsection
