@extends('layouts.app')

@section('title', 'Seguimiento · ' . $nombre)

@section('content')
<div style="margin-bottom:24px">
    <h1 style="font-family:'Bricolage Grotesque',sans-serif; font-size:24px; font-weight:700; margin:0">
        {{ $icono }} Seguimiento {{ $nombre }}
    </h1>
</div>

<div class="card">
    <div class="card-body" style="text-align:center; padding:48px 24px">
        <div style="font-size:40px; margin-bottom:12px">🚧</div>
        <div style="font-size:16px; font-weight:600; margin-bottom:6px">Sección en construcción</div>
        <div style="font-size:13px; color:var(--text-muted)">
            Aquí irá el seguimiento de {{ $nombre }} por colegio.
        </div>
    </div>
</div>
@endsection
