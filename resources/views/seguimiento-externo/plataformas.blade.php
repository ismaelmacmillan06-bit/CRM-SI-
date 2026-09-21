@extends('layouts.app')

@section('title', "Seguimiento Externo — {$school->name}")

@section('content')

<div style="display:flex; gap:10px; margin-bottom:20px; align-items:center">
    <a href="{{ route('seguimiento-externo.index') }}" class="btn btn-secondary btn-sm">← Otro colegio</a>
</div>

<div style="margin-bottom:24px">
    <h2 style="font-family:'Bricolage Grotesque',sans-serif; font-size:22px; font-weight:700; color:var(--text); margin:0; display:flex; align-items:center; gap:8px">
        🌐 {{ $school->name }}
        <span style="font-size:11px; font-weight:700; color:#fff; background:var(--accent); padding:2px 8px; border-radius:20px">BETA</span>
    </h2>
    <p style="font-size:13px; color:var(--text-muted); margin:6px 0 0">¿Qué plataforma deseas gestionar?</p>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px">
    @foreach($platforms as $p)
    @php $platform = $p['platform']; @endphp
    <a href="{{ route('seguimiento-externo.checklist', [$school, $platform]) }}"
       style="background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:22px;
              text-decoration:none; box-shadow:0 1px 3px rgba(0,0,0,0.06); transition:transform 0.15s, box-shadow 0.15s; display:block"
       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 18px rgba(0,0,0,0.1)'"
       onmouseout="this.style.transform='none'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.06)'">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px">
            <span style="font-size:26px">{{ $platform->icon }}</span>
            <span style="font-family:'Bricolage Grotesque',sans-serif; font-size:17px; font-weight:700; color:var(--text)">
                {{ $platform->name }}
            </span>
        </div>
        <div style="display:flex; align-items:baseline; gap:8px; margin-bottom:8px">
            <span style="font-family:'Bricolage Grotesque',sans-serif; font-size:28px; font-weight:800; color:var(--accent)">
                {{ $p['pct'] }}%
            </span>
            <span style="font-size:12.5px; color:var(--text-muted)">{{ $p['done'] }} / {{ $p['total'] }} pasos</span>
        </div>
        <div style="height:7px; border-radius:99px; background:var(--surface2); overflow:hidden">
            <div style="width:{{ $p['pct'] }}%; height:100%; border-radius:99px; background:var(--accent); transition:width .4s ease"></div>
        </div>
    </a>
    @endforeach
</div>

@if($platforms->isEmpty())
<div class="card" style="padding:48px; text-align:center; color:var(--text-muted)">
    <div style="font-size:40px; margin-bottom:12px">🌐</div>
    <p style="margin:0">Aún no hay plataformas configuradas.</p>
</div>
@endif

@endsection
