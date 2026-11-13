@php
    $esAdmin = auth()->user()?->hasRole('admin');
    $etiquetaSingular = $categoria === 'formatos' ? 'formato' : 'manual';
    $etiquetaPlural = $categoria === 'formatos' ? 'Formatos' : 'Manuales';
@endphp

<div class="sr-toolbar">
    <div>
        <h2 class="sr-section-title">{{ $iconoCategoria ?? '📁' }} {{ $etiquetaPlural }} de {{ $nombre }}</h2>
        <p class="sr-section-sub">
            @if($esAdmin)
                Sube archivos y define qué roles pueden consultarlos y descargarlos.
            @else
                Archivos disponibles para consulta y descarga según tu rol.
            @endif
        </p>
    </div>
    <div class="sr-toolbar-actions">
        @if($recursos->isNotEmpty())
            <input type="search" id="sr-buscar-{{ $categoria }}" class="form-control sr-search" autocomplete="off"
                   placeholder="🔍 Buscar {{ $etiquetaSingular }}...">
        @endif
        @if($esAdmin)
            <button type="button" class="btn btn-primary" onclick="srAbrirUpload('{{ $categoria }}', '{{ $etiquetaSingular }}')" style="gap:7px; white-space:nowrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Subir {{ $etiquetaSingular }}
            </button>
        @endif
    </div>
</div>

@if($recursos->isEmpty())
    <div class="card sr-empty">
        <div class="sr-empty-ico">📂</div>
        <div class="sr-empty-title">No hay {{ strtolower($etiquetaPlural) }} disponibles todavía</div>
        <p class="sr-empty-text">
            @if($esAdmin)
                Sube el primer {{ $etiquetaSingular }} en Word, Excel, PDF o imagen para compartirlo con el equipo.
            @else
                Cuando un administrador suba {{ strtolower($etiquetaPlural) }} habilitados para tu rol, aparecerán aquí.
            @endif
        </p>
        @if($esAdmin)
            <button type="button" class="btn btn-primary" onclick="srAbrirUpload('{{ $categoria }}', '{{ $etiquetaSingular }}')" style="margin-top:14px">
                + Subir primer {{ $etiquetaSingular }}
            </button>
        @endif
    </div>
@else
    <div class="sr-grid" id="sr-grid-{{ $categoria }}">
        @foreach($recursos as $recurso)
            @php
                $fmt = $recurso->formatoInfo();
                $previewUrl = route('seguimiento.recursos.preview', $recurso);
                $downloadUrl = route('seguimiento.recursos.download', $recurso);
                $fechaExacta = $recurso->created_at->format('d/m/Y H:i');
                $haceTiempo = $recurso->created_at->locale('es')->diffForHumans();
                $autor = $recurso->user->name ?? '—';
                $textoBusqueda = mb_strtolower($recurso->titulo . ' ' . ($recurso->descripcion ?? '') . ' ' . $recurso->archivo_nombre . ' ' . $fmt['etiqueta']);
            @endphp
            <div class="sr-card" data-sr-card data-search="{{ $textoBusqueda }}">
                {{-- Header de card: ícono por formato + título + botón ojo --}}
                <div class="sr-card-head">
                    <div class="sr-fmt-icon" style="background:{{ $fmt['bg'] }}; color:{{ $fmt['color'] }}; border-color:{{ $fmt['border'] }}" title="{{ $fmt['etiqueta'] }}">
                        @switch($fmt['tipo'])
                            @case('pdf')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15v-2h1.5a1.5 1.5 0 0 0 0-3H9v5"/><path d="M15 15h-1.5a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1H15"/></svg>
                                @break
                            @case('excel')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8"/><path d="M8 17h8"/><path d="M12 10v9"/></svg>
                                @break
                            @case('word')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                                @break
                            @case('ppt')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/><path d="M8 10h4a1.5 1.5 0 0 0 0-3H8v6"/></svg>
                                @break
                            @case('image')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                @break
                            @case('archive')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                                @break
                            @default
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        @endswitch
                    </div>

                    <div class="sr-card-title-wrap">
                        <div class="sr-card-titulo">{{ $recurso->titulo }}</div>
                        <div class="sr-card-ext-row">
                            <span class="sr-ext-pill" style="background:{{ $fmt['bg'] }}; color:{{ $fmt['color'] }}; border-color:{{ $fmt['border'] }}">{{ $fmt['etiqueta'] }}</span>
                            <span class="sr-size-pill">{{ $recurso->tamanoFormateado() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Audiencia --}}
                <div class="sr-audiencias">
                    @foreach($recurso->audiencias as $aud)
                        <span class="sr-aud-pill">{{ \App\Models\SeguimientoRecurso::AUDIENCIAS[$aud->rol] ?? $aud->rol }}</span>
                    @endforeach
                </div>

                {{-- Descripción --}}
                @if($recurso->descripcion)
                    <div class="sr-card-desc">{{ $recurso->descripcion }}</div>
                @endif

                {{-- Vista previa embebida en card --}}
                @if($fmt['tipo'] === 'image')
                    <button type="button" class="sr-img-preview-btn"
                            onclick="srAbrirPreview(this)"
                            data-titulo="{{ $recurso->titulo }}"
                            data-descripcion="{{ $recurso->descripcion ?? '' }}"
                            data-tipo="{{ $fmt['tipo'] }}"
                            data-ext="{{ $fmt['etiqueta'] }}"
                            data-color="{{ $fmt['color'] }}"
                            data-bg="{{ $fmt['bg'] }}"
                            data-nombre-archivo="{{ $recurso->archivo_nombre }}"
                            data-tamano="{{ $recurso->tamanoFormateado() }}"
                            data-autor="{{ $autor }}"
                            data-fecha="{{ $fechaExacta }}"
                            data-hace="{{ $haceTiempo }}"
                            data-preview-url="{{ $previewUrl }}"
                            data-download-url="{{ $downloadUrl }}"
                            title="Ampliar vista previa">
                        <img src="{{ $previewUrl }}" alt="{{ $recurso->titulo }}" class="sr-card-img" loading="lazy">
                    </button>
                @else
                    <div class="sr-doc-preview" style="border-color:{{ $fmt['border'] }}; background:{{ $fmt['bg'] }}">
                        <div class="sr-doc-preview-info">
                            <span class="sr-doc-filename" title="{{ $recurso->archivo_nombre }}">{{ $recurso->archivo_nombre }}</span>
                            <span class="sr-doc-hint">
                                @if($fmt['tipo'] === 'pdf')
                                    Documento PDF · Vista previa interactiva disponible
                                @elseif($fmt['tipo'] === 'excel')
                                    Hoja de cálculo · Vista previa de tabla disponible
                                @elseif($fmt['tipo'] === 'text')
                                    Archivo de texto · Vista previa disponible
                                @else
                                    Archivo {{ $fmt['etiqueta'] }} · Listo para descargar
                                @endif
                            </span>
                        </div>
                    </div>
                @endif

                {{-- Botones de Vista previa + Descarga + Footer --}}
                <div class="sr-card-bottom">
                    <div class="sr-card-actions">
                        <button type="button" class="btn btn-secondary btn-sm sr-btn-action"
                                onclick="srAbrirPreview(this)"
                                data-titulo="{{ $recurso->titulo }}"
                                data-descripcion="{{ $recurso->descripcion ?? '' }}"
                                data-tipo="{{ $fmt['tipo'] }}"
                                data-ext="{{ $fmt['etiqueta'] }}"
                                data-color="{{ $fmt['color'] }}"
                                data-bg="{{ $fmt['bg'] }}"
                                data-nombre-archivo="{{ $recurso->archivo_nombre }}"
                                data-tamano="{{ $recurso->tamanoFormateado() }}"
                                data-autor="{{ $autor }}"
                                data-fecha="{{ $fechaExacta }}"
                                data-hace="{{ $haceTiempo }}"
                                data-preview-url="{{ $previewUrl }}"
                                data-download-url="{{ $downloadUrl }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            Vista previa
                        </button>
                        <a href="{{ $downloadUrl }}" class="btn btn-primary btn-sm sr-btn-action">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Descargar
                        </a>
                    </div>

                    <div class="sr-card-footer">
                        <div class="sr-meta" title="Subido el {{ $fechaExacta }}">
                            <strong>{{ $autor }}</strong> · {{ $haceTiempo }}
                        </div>

                        @if($esAdmin)
                            <div class="sr-admin-btns">
                                <button type="button"
                                        class="sr-mini-btn"
                                        data-url="{{ route('seguimiento.recursos.update', $recurso) }}"
                                        data-titulo="{{ $recurso->titulo }}"
                                        data-descripcion="{{ $recurso->descripcion ?? '' }}"
                                        data-archivo-nombre="{{ $recurso->archivo_nombre }}"
                                        data-audiencias="{{ implode(',', $recurso->audienciaRoles()) }}"
                                        onclick="srAbrirEditar(this)"
                                        title="Editar nombre, descripción, archivo o roles">
                                    ✏️ Editar
                                </button>
                                <form method="POST" action="{{ route('seguimiento.recursos.destroy', $recurso) }}"
                                      id="form-eliminar-recurso-{{ $recurso->id }}" style="margin:0">
                                    @csrf @method('DELETE')
                                    <button type="button" class="sr-mini-btn danger"
                                            onclick="srConfirmarEliminar('form-eliminar-recurso-{{ $recurso->id }}', '{{ addslashes($recurso->titulo) }}')"
                                            title="Eliminar archivo">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div id="sr-sin-resultados-{{ $categoria }}" class="card sr-empty" style="display:none">
        <div class="sr-empty-ico">🔍</div>
        <div class="sr-empty-title">Sin coincidencias</div>
        <p class="sr-empty-text">Ningún {{ $etiquetaSingular }} coincide con tu búsqueda.</p>
    </div>
@endif
