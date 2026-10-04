@extends('layouts.app')

@section('title', 'Equipo SI')

@section('content')

@php
    $rolesResumen = [
        ['key' => 'consultor_digital',    'label' => 'Consultor Digital',       'icon' => '💻', 'color' => '#2563eb'],
        ['key' => 'consultor_eca',        'label' => 'Consultor Académico ECA', 'icon' => '📗', 'color' => '#16a34a'],
        ['key' => 'consultor_elt',        'label' => 'Consultor Académico ELT', 'icon' => '📘', 'color' => '#0ea5e9'],
        ['key' => 'representante_ventas', 'label' => 'Representante de Ventas', 'icon' => '🤝', 'color' => '#d97706'],
        ['key' => 'coordinador',          'label' => 'Coordinador',             'icon' => '🧭', 'color' => '#7c3aed'],
    ];
@endphp
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:14px; margin-bottom:20px">
    @foreach($rolesResumen as $r)
    <div style="background:var(--surface); border-radius:12px; padding:16px 18px;
                border-left:4px solid {{ $r['color'] }}; box-shadow:0 1px 4px rgba(0,0,0,0.06)">
        <div style="font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;
                    color:var(--text-muted); margin-bottom:8px">{{ $r['icon'] }} {{ $r['label'] }}</div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:30px; font-weight:800;
                    color:var(--text); line-height:1">{{ $conteoPorRol[$r['key']] ?? 0 }}</div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">👥 Equipo SI</span>
        @role('admin')
        <a href="{{ route('consultants.create') }}" class="btn btn-primary">+ Nuevo Miembro</a>
        @endrole
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Zona</th>
                <th>Rol</th>
                <th>Colegios</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($consultants as $consultant)
            <tr>
                <td><strong>{{ $consultant->user->name }}</strong></td>
                <td style="font-size:13px; color:var(--text-muted)">{{ $consultant->user->email }}</td>
                <td>{{ $consultant->phone ?? '—' }}</td>
                <td>{{ $consultant->zone ?? '—' }}</td>
                <td>
                    <span class="badge badge-info">{{ $consultant->user->getRoleNames()->first() }}</span>
                </td>
                <td>{{ $consultant->schoolConsultants->count() }}</td>
                <td>
                    <div style="display:flex; gap:6px">
                        <a href="{{ route('consultants.show', $consultant) }}" class="btn btn-secondary btn-sm">Ver</a>
                        @hasanyrole('admin|consultor_digital')
                        <a href="{{ route('consultants.edit', $consultant) }}" class="btn btn-secondary btn-sm">Editar</a>
                        <form method="POST" action="{{ route('consultants.destroy', $consultant) }}"
                              id="form-eliminar-consultor-{{ $consultant->id }}">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                    onclick="confirmarEliminar('Eliminar miembro', '¿Deseas eliminar a {{ addslashes($consultant->user->name) }} del equipo? Esta acción no se puede deshacer.', 'form-eliminar-consultor-{{ $consultant->id }}')">
                                Eliminar
                            </button>
                        </form>
                        @endhasanyrole
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay consultores registrados.
                    <a href="{{ route('consultants.create') }}">Registra el primero</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection