@extends('layouts.app')

@section('title', 'Seguimiento Externo')

@section('content')

<div style="margin-bottom:24px">
    <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0; display:flex; align-items:center; gap:8px">
        🌐 Seguimiento Externo
        <span style="font-size:11px; font-weight:700; color:#fff; background:var(--accent); padding:2px 8px; border-radius:20px">BETA</span>
    </h2>
    <p style="font-size:13px; color:var(--text-muted); margin:6px 0 0">
        Control de arranque en plataformas externas al MEE: <strong>Meta</strong>, <strong>Read Roo Reads</strong> y <strong>Espacevirtuel</strong>.
        Selecciona un colegio para comenzar.
    </p>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">🏫 Selecciona un colegio</span>
        <span style="font-size:13px; color:var(--text-muted)">{{ $schools->count() }} colegio(s)</span>
    </div>
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" style="margin-bottom:16px">
            <input type="text" name="q" value="{{ $search }}" class="form-control"
                   placeholder="🔍 Buscar colegio..." style="max-width:320px">
        </form>

        @forelse($schools as $school)
        <a href="{{ route('seguimiento-externo.school', $school) }}"
           style="display:flex; align-items:center; justify-content:space-between; gap:12px;
                  padding:14px 16px; border-radius:10px; text-decoration:none;
                  border-bottom:1px solid var(--border); transition:background 0.15s"
           onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background='transparent'">
            <div style="display:flex; align-items:center; gap:12px; min-width:0">
                <span style="font-size:20px">🏫</span>
                <div style="min-width:0">
                    <div style="font-size:14px; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap">
                        {{ $school->name }}
                    </div>
                    @if($school->state || $school->city)
                    <div style="font-size:12px; color:var(--text-muted); margin-top:1px">
                        {{ $school->state ?? $school->city }}
                    </div>
                    @endif
                </div>
            </div>
            <span style="font-size:13px; color:var(--accent); font-weight:600; white-space:nowrap">Gestionar →</span>
        </a>
        @empty
        <div style="padding:40px 20px; text-align:center; color:var(--text-muted)">
            <div style="font-size:36px; margin-bottom:10px">🔎</div>
            <p style="margin:0">No se encontraron colegios{{ $search ? ' para "' . $search . '"' : '' }}.</p>
        </div>
        @endforelse
    </div>
</div>

@endsection
