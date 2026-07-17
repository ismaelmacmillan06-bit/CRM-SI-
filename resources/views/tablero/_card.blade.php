<div class="comunicado-card {{ $tipo === 'pasado' ? 'pasado' : '' }}" style="position:relative">

    {{-- Botón ver --}}
    <button class="btn-ver-comunicado" title="Ver completo"
        data-titulo="{{ $comunicado->titulo }}"
        data-descripcion="{{ $comunicado->descripcion }}"
        data-enlace="{{ $comunicado->enlace }}"
        data-enlace-texto="{{ $comunicado->enlace_texto }}"
        data-archivo-url="{{ $comunicado->archivo ? asset('storage/' . $comunicado->archivo) : '' }}"
        data-archivo-tipo="{{ $comunicado->archivo_tipo }}"
        data-archivo-nombre="{{ $comunicado->archivo_nombre }}"
        data-autor="{{ $comunicado->user->name ?? '—' }}"
        data-fecha="{{ $comunicado->created_at->format('d/m/Y H:i') }}"
        onclick="verComunicado(this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
        </svg>
    </button>

    {{-- Título --}}
    <div class="comunicado-titulo" style="padding-right:36px">{{ $comunicado->titulo }}</div>

    {{-- Descripción (truncada en card) --}}
    <div class="comunicado-desc" style="display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden">{{ $comunicado->descripcion }}</div>

    {{-- Adjunto --}}
    @if($comunicado->archivo)
        @if($comunicado->archivo_tipo === 'image')
            <img src="{{ asset('storage/' . $comunicado->archivo) }}"
                 alt="{{ $comunicado->archivo_nombre }}"
                 class="adjunto-img">
        @else
            <a href="{{ asset('storage/' . $comunicado->archivo) }}"
               target="_blank" class="adjunto-link">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                </svg>
                {{ $comunicado->archivo_nombre }}
            </a>
        @endif
    @endif

    {{-- Enlace + Footer siempre al fondo juntos --}}
    <div style="margin-top:auto; display:flex; flex-direction:column; gap:12px">

        @if($comunicado->enlace)
            <a href="{{ $comunicado->enlace }}" target="_blank" rel="noopener noreferrer" class="adjunto-link" style="background:var(--accent); color:#fff; border-color:var(--accent)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                    <polyline points="15,3 21,3 21,9"/>
                    <line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
                {{ $comunicado->enlace_texto ?: 'Abrir enlace' }}
            </a>
        @endif

        {{-- Footer --}}
        <div class="comunicado-footer" style="margin-top:0">
        <div style="display:flex; flex-direction:column; gap:3px">
            <span>
                <strong style="color:var(--text)">{{ $comunicado->user->name ?? '—' }}</strong>
                · {{ $comunicado->created_at->diffForHumans() }}
            </span>
            @if($comunicado->fecha_termino)
                @if($tipo === 'pasado')
                    <span class="badge-vencido">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        Venció el {{ $comunicado->fecha_termino->format('d/m/Y') }}
                    </span>
                @else
                    <span class="badge-expira">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                        Vence el {{ $comunicado->fecha_termino->format('d/m/Y') }}
                    </span>
                @endif
            @else
                <span class="badge-sin-expira">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>
                    Permanente
                </span>
            @endif
        </div>

        {{-- Eliminar (solo admin) --}}
        @if(auth()->user()->hasRole('admin'))
            <form method="POST" action="{{ route('tablero.destroy', $comunicado) }}"
                  id="form-eliminar-comunicado-{{ $comunicado->id }}">
                @csrf @method('DELETE')
                <button type="button" class="btn-eliminar"
                        onclick="confirmarEliminar('Eliminar comunicado', '¿Deseas eliminar este comunicado? Esta acción no se puede deshacer.', 'form-eliminar-comunicado-{{ $comunicado->id }}')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3,6 5,6 21,6"/><path d="M19,6l-1,14a2,2,0,0,1-2,2H8a2,2,0,0,1-2-2L5,6"/><path d="M10,11v6"/><path d="M14,11v6"/><path d="M9,6V4a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1v2"/>
                    </svg>
                    Eliminar
                </button>
            </form>
        @endif
        </div>{{-- fin footer --}}

    </div>{{-- fin bloque fondo --}}
</div>
