<div class="comunicado-card {{ $tipo === 'pasado' ? 'pasado' : '' }}">

    {{-- Título --}}
    <div class="comunicado-titulo">{{ $comunicado->titulo }}</div>

    {{-- Descripción --}}
    <div class="comunicado-desc">{{ $comunicado->descripcion }}</div>

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

    {{-- Footer --}}
    <div class="comunicado-footer">
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
                  onsubmit="return confirm('¿Eliminar este comunicado?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-eliminar">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3,6 5,6 21,6"/><path d="M19,6l-1,14a2,2,0,0,1-2,2H8a2,2,0,0,1-2-2L5,6"/><path d="M10,11v6"/><path d="M14,11v6"/><path d="M9,6V4a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1v2"/>
                    </svg>
                    Eliminar
                </button>
            </form>
        @endif
    </div>
</div>
