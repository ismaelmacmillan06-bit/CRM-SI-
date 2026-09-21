@extends('layouts.app')

@section('title', 'Mis Notas SI')

@section('content')
@php
    $isAdmin = auth()->user()->hasRole('admin');
    $colorMap = [
        'amber'  => ['bg' => '#FEF3C7', 'border' => '#FBBF24', 'ink' => '#92400E'],
        'red'    => ['bg' => '#FEE2E2', 'border' => '#F87171', 'ink' => '#991B1B'],
        'blue'   => ['bg' => '#DBEAFE', 'border' => '#60A5FA', 'ink' => '#1E40AF'],
        'green'  => ['bg' => '#D1FAE5', 'border' => '#34D399', 'ink' => '#065F46'],
        'purple' => ['bg' => '#EDE9FE', 'border' => '#A78BFA', 'ink' => '#5B21B6'],
        'gray'   => ['bg' => '#F3F4F6', 'border' => '#9CA3AF', 'ink' => '#374151'],
    ];
@endphp

<style>
    .notes-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
    .notes-head h2 { font-family: 'Bricolage Grotesque', sans-serif; font-size: 22px; font-weight: 700; color: var(--text); margin: 0; }
    .notes-head p { font-size: 13.5px; color: var(--text-muted); margin: 6px 0 0; }

    .notes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
    .note-card {
        border-radius: 14px; border: 1.5px solid; padding: 18px 18px 14px;
        display: flex; flex-direction: column; gap: 10px; min-height: 160px;
        box-shadow: 0 1px 3px rgba(24,19,17,.06); position: relative;
    }
    .note-title { font-family: 'Bricolage Grotesque', sans-serif; font-size: 15.5px; font-weight: 700; word-break: break-word; padding-right: 54px; }
    .note-content { font-size: 13.5px; line-height: 1.55; white-space: pre-line; word-break: break-word; flex: 1; }
    .note-actions { position: absolute; top: 14px; right: 14px; display: flex; gap: 4px; }
    .note-icon-btn {
        width: 26px; height: 26px; border-radius: 7px; border: none; cursor: pointer;
        display: grid; place-items: center; background: rgba(255,255,255,.55); color: inherit;
        opacity: .7; transition: opacity .15s, background .15s;
    }
    .note-icon-btn:hover { opacity: 1; background: rgba(255,255,255,.85); }
    .note-footer { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 11px; opacity: .75; padding-top: 8px; border-top: 1px solid rgba(0,0,0,.08); }
    .note-author { display: inline-flex; align-items: center; gap: 5px; font-weight: 600; }
    .note-avatar { width: 18px; height: 18px; border-radius: 50%; background: rgba(0,0,0,.12); display: inline-flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; flex-shrink: 0; }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .empty-state .e { font-size: 40px; margin-bottom: 10px; }

    /* Modal (mismo patrón usado en Herramientas SI) */
    .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(24,19,17,.55); z-index: 500; backdrop-filter: blur(3px); align-items: center; justify-content: center; }
    .modal-backdrop.open { display: flex; }
    .modal { background: var(--surface); border-radius: 16px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; padding: 30px; box-shadow: 0 24px 64px -12px rgba(24,19,17,.32); position: relative; }
    .modal-title { font-family: 'Bricolage Grotesque', sans-serif; font-size: 19px; font-weight: 700; color: var(--text); margin-bottom: 18px; }
    .modal-close { position: absolute; top: 16px; right: 16px; width: 30px; height: 30px; border-radius: 8px; border: none; background: var(--surface2); cursor: pointer; display: grid; place-items: center; color: var(--text-muted); }
    .modal-close:hover { background: var(--border); color: var(--text); }
    .field-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text-muted); margin: 14px 0 6px; }
    .field-label:first-child { margin-top: 0; }
    .color-picker { display: flex; gap: 8px; margin-top: 4px; }
    .color-dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; display: inline-block; }
    .color-dot.is-selected { border-color: var(--text); box-shadow: 0 0 0 2px var(--surface); }

    @media (max-width: 600px) { .modal { padding: 20px; } .notes-grid { grid-template-columns: 1fr; } }
</style>

<div class="notes-head">
    <div>
        <h2>📝 Mis Notas SI</h2>
        <p>
            @if($isAdmin)
                Tu bloc de pendientes — como administrador puedes ver las notas de todo el equipo.
            @else
                Tu bloc de pendientes personal. Solo tú puedes ver estas notas.
            @endif
        </p>
    </div>
    <button type="button" class="btn btn-primary" onclick="nuevaNota()">
        + Nueva nota
    </button>
</div>

@if($notes->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="e">🗒️</div>
            <p style="font-weight:600;color:var(--text);margin:0 0 4px">Aún no tienes notas</p>
            <p style="margin:0">Dale a "Nueva nota" para guardar tu primer pendiente.</p>
        </div>
    </div>
@else
    <div class="notes-grid">
        @foreach($notes as $note)
            @php $c = $colorMap[$note->color] ?? $colorMap['amber']; @endphp
            <div class="note-card" style="background:{{ $c['bg'] }}; border-color:{{ $c['border'] }}; color:{{ $c['ink'] }}">
                <div class="note-actions">
                    <button type="button" class="note-icon-btn"
                            onclick="editarNota({{ $note->id }}, {{ json_encode($note->title) }}, {{ json_encode($note->content) }}, {{ json_encode($note->color) }})"
                            title="Editar">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    </button>
                    <button type="button" class="note-icon-btn"
                            onclick="confirmarEliminar('Eliminar nota', '¿Seguro que quieres eliminar esta nota? No se puede deshacer.', 'form-del-nota-{{ $note->id }}')"
                            title="Eliminar">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                    </button>
                    <form id="form-del-nota-{{ $note->id }}" method="POST" action="{{ route('notes.destroy', $note) }}" style="display:none">
                        @csrf @method('DELETE')
                    </form>
                </div>

                @if($note->title)
                    <div class="note-title">{{ $note->title }}</div>
                @endif
                <div class="note-content">{{ $note->content }}</div>

                <div class="note-footer">
                    @if($isAdmin)
                        <span class="note-author">
                            <span class="note-avatar">{{ strtoupper(substr($note->user->name ?? '?', 0, 1)) }}</span>
                            {{ $note->user->name ?? 'Usuario eliminado' }}
                        </span>
                    @else
                        <span></span>
                    @endif
                    <span>{{ $note->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Modal Nueva/Editar nota --}}
<div class="modal-backdrop" id="modalNota" onclick="cerrarModalFuera(event,'modalNota')">
    <div class="modal">
        <button type="button" class="modal-close" onclick="cerrarModalNota()" aria-label="Cerrar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="modal-title" id="modalNotaTitulo">Nueva nota</div>

        <form id="formNota" method="POST" action="{{ route('notes.store') }}">
            @csrf
            <div id="formNotaMethod"></div>

            <label class="field-label" for="notaTitle">Título (opcional)</label>
            <input type="text" name="title" id="notaTitle" class="form-control" maxlength="120" placeholder="Ej. Seguimiento colegio X">

            <label class="field-label" for="notaContent">Contenido</label>
            <textarea name="content" id="notaContent" class="form-control" rows="6" maxlength="5000" required placeholder="Escribe tu pendiente..."></textarea>

            <label class="field-label">Color</label>
            <div class="color-picker" id="colorPicker">
                @foreach($colorMap as $key => $c)
                    <span class="color-dot" data-color="{{ $key }}" style="background:{{ $c['bg'] }};border-color:{{ $c['border'] }}" onclick="seleccionarColor('{{ $key }}')"></span>
                @endforeach
            </div>
            <input type="hidden" name="color" id="notaColor" value="amber">

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:22px">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalNota()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar nota</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalNota() { document.getElementById('modalNota').classList.add('open'); }
    function cerrarModalNota() { document.getElementById('modalNota').classList.remove('open'); }
    function cerrarModalFuera(e, id) { if (e.target === document.getElementById(id)) cerrarModalNota(); }

    function seleccionarColor(key) {
        document.getElementById('notaColor').value = key;
        document.querySelectorAll('#colorPicker .color-dot').forEach(function (d) {
            d.classList.toggle('is-selected', d.getAttribute('data-color') === key);
        });
    }

    function nuevaNota() {
        document.getElementById('modalNotaTitulo').textContent = 'Nueva nota';
        document.getElementById('formNota').action = '{{ route('notes.store') }}';
        document.getElementById('formNotaMethod').innerHTML = '';
        document.getElementById('notaTitle').value = '';
        document.getElementById('notaContent').value = '';
        seleccionarColor('amber');
        abrirModalNota();
        setTimeout(function () { document.getElementById('notaContent').focus(); }, 60);
    }

    function editarNota(id, title, content, color) {
        document.getElementById('modalNotaTitulo').textContent = 'Editar nota';
        document.getElementById('formNota').action = '{{ url('mis-notas') }}/' + id;
        document.getElementById('formNotaMethod').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('notaTitle').value = title || '';
        document.getElementById('notaContent').value = content || '';
        seleccionarColor(color || 'amber');
        abrirModalNota();
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrarModalNota();
    });

    seleccionarColor('amber');
</script>
@endsection
