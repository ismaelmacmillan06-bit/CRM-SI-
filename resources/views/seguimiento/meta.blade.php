@extends('layouts.app')

@section('title', 'Seguimiento · ' . $nombre)

@section('content')
@php
    $proximasCap = $filas->where('capacitacion_proxima', true)->count();
    $proximasApl = $filas->where('aplicacion_proxima', true)->count();
    $pendientes  = $filas->where('pendiente', true)->count();
    // Si una edición regresó con errores, se vuelve a abrir el modal de ese colegio
    $colegioConError = $filas->first(fn ($f) => $errors->getBag('meta_' . $f['colegio']->id)->any())['colegio'] ?? null;
@endphp

<style>
    .mt-cards { display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:12px; margin-bottom:20px; }
    .mt-card { background:#fff; border:1px solid var(--border); border-left:4px solid var(--accent); border-radius:12px;
               padding:14px 18px; text-align:left; cursor:pointer; font:inherit; color:inherit; transition:box-shadow .15s, transform .15s; }
    .mt-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.07); transform:translateY(-1px); }
    .mt-card.activa { background:#fff5f5; box-shadow:0 0 0 2px rgba(226,35,26,.25); }
    .mt-card .num { font-size:24px; font-weight:800; line-height:1; color:var(--accent); }
    .mt-card .lbl { font-size:12.5px; font-weight:600; color:var(--text-muted); margin-top:6px; }
    .mt-card.verde { border-left-color:#16a34a; } .mt-card.verde .num { color:#16a34a; }
    .mt-card.azul  { border-left-color:#2563eb; } .mt-card.azul .num  { color:#2563eb; }
    .mt-card.ambar { border-left-color:#d97706; } .mt-card.ambar .num { color:#d97706; }

    .mt-table td { vertical-align:middle; font-size:13.5px; }
    .mt-table th { white-space:nowrap; }
    .mt-vacio { color:#b8b2aa; }
    .mt-pwd { display:inline-flex; align-items:center; gap:6px; font-family:ui-monospace,Consolas,monospace; white-space:nowrap; }
    .mt-pwd button { background:none; border:none; cursor:pointer; padding:2px; color:var(--text-muted); line-height:0; }
    .mt-fecha { white-space:nowrap; }
    .mt-chip { display:inline-block; font-size:11px; font-weight:600; border-radius:20px; padding:1px 8px; margin-top:3px; }
    .mt-chip.prox { background:#dbeafe; color:#1d4ed8; }
    .mt-chip.hoy  { background:#fee2e2; color:#b91c1c; }
    .mt-chip.pas  { background:var(--surface2); color:var(--text-muted); }
    .mt-notas { max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text-muted); }
    .mt-acc { display:inline-flex; gap:6px; flex-wrap:nowrap; }
    /* La columna de botones queda fija a la derecha aunque la tabla se deslice de lado */
    .mt-table th:last-child, .mt-table td:last-child { position:sticky; right:0; background:var(--surface);
                                                       box-shadow:-8px 0 10px -8px rgba(0,0,0,.18); z-index:1; }
    .mt-table thead th:last-child { background:var(--surface2); }

    .mt-overlay { display:none; position:fixed; inset:0; background:rgba(24,19,17,.55); z-index:999;
                  align-items:center; justify-content:center; padding:20px; }
    .mt-overlay.abierto { display:flex; }
    .mt-modal { background:var(--surface); border-radius:16px; width:620px; max-width:100%; max-height:90vh;
                display:flex; flex-direction:column; box-shadow:0 20px 50px rgba(0,0,0,.25); overflow:hidden; }
    .mt-head { display:flex; align-items:center; gap:10px; padding:16px 22px; border-bottom:1px solid var(--border); }
    .mt-head h3 { font-family:'Bricolage Grotesque',sans-serif; font-size:17px; font-weight:600; margin:0; }
    .mt-head small { display:block; font-size:12px; color:var(--text-muted); font-weight:500; margin-top:2px; }
    .mt-close { margin-left:auto; background:none; border:none; width:34px; height:34px; border-radius:8px; font-size:18px;
                cursor:pointer; color:var(--text-muted); }
    .mt-close:hover { background:var(--surface2); }
    .mt-body { padding:18px 22px; overflow-y:auto; display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .mt-body .full { grid-column:1 / -1; }
    .mt-body label { display:block; font-size:12.5px; font-weight:600; color:var(--text-muted); margin-bottom:5px; }
    .mt-sec { grid-column:1 / -1; font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
              color:var(--text-muted); border-bottom:1px solid var(--border); padding-bottom:6px; margin-top:4px; }
    .mt-foot { display:flex; justify-content:flex-end; gap:8px; padding:14px 22px; border-top:1px solid var(--border); }
    .mt-err { grid-column:1 / -1; background:#fee2e2; color:#991b1b; border-radius:8px; padding:10px 12px; font-size:13px; }

    @media (max-width: 600px) {
        .mt-overlay { align-items:flex-end; padding:0; }
        .mt-modal { width:100%; border-radius:18px 18px 0 0; max-height:92vh; }
        .mt-body { grid-template-columns:1fr; padding:16px; }
        .mt-foot { padding:12px 16px calc(12px + env(safe-area-inset-bottom)); }
        .mt-foot .btn { flex:1; justify-content:center; }
    }
</style>

<div style="margin-bottom:24px">
    <h1 style="font-family:'Bricolage Grotesque',sans-serif; font-size:24px; font-weight:700; margin:0">
        {{ $icono }} Agenda de exámenes {{ $nombre }}
    </h1>
    <p style="font-size:13px; color:var(--text-muted); margin:4px 0 0">
        Capacitaciones y fechas de aplicación del examen de los colegios con {{ $nombre }}.
    </p>
</div>

@unless($servicioLigado)
<div style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:12px 16px;
            border-radius:8px; margin-bottom:20px; font-size:13.5px">
    ⚠️ Ningún servicio está ligado a {{ $nombre }}.
    @role('admin')
        Elígelo en el campo <strong>Gestor de plataformas</strong> de
        <a href="{{ route('configuracion.servicios.index') }}" style="color:#92400e; text-decoration:underline">Configuración → Servicios</a>.
    @else
        Pide a un administrador que lo configure.
    @endrole
</div>
@endunless

{{-- Cards: también sirven de filtro --}}
<div class="mt-cards">
    <button type="button" class="mt-card" data-filtro-card="">
        <div class="num">{{ $filas->count() }}</div>
        <div class="lbl">🏫 Colegios</div>
    </button>
    <button type="button" class="mt-card azul" data-filtro-card="cap">
        <div class="num">{{ $proximasCap }}</div>
        <div class="lbl">👩‍🏫 Capacitaciones próximas (30 días)</div>
    </button>
    <button type="button" class="mt-card verde" data-filtro-card="apl">
        <div class="num">{{ $proximasApl }}</div>
        <div class="lbl">📝 Aplicaciones próximas (30 días)</div>
    </button>
    <button type="button" class="mt-card ambar" data-filtro-card="pend">
        <div class="num">{{ $pendientes }}</div>
        <div class="lbl">⏳ Con fechas pendientes</div>
    </button>
</div>

<div class="card">
    <div class="card-header" style="gap:10px; flex-wrap:wrap">
        <span class="card-title">🏫 Colegios con {{ $nombre }}</span>
        @if($filas->isNotEmpty())
        <div style="display:flex; gap:8px; flex-wrap:wrap; flex:1; justify-content:flex-end">
            <input type="search" id="mt-buscar" class="form-control" autocomplete="off"
                   placeholder="🔍 Buscar colegio, ID, administrador, capacitación..." style="flex:1 1 240px; max-width:380px">
            <select id="mt-filtro" class="form-control" style="max-width:240px">
                <option value="">Todos</option>
                <option value="cap">Capacitación próxima (30 días)</option>
                <option value="apl">Aplicación próxima (30 días)</option>
                <option value="pend">Con fechas pendientes</option>
                <option value="listo">Con ambas fechas</option>
            </select>
        </div>
        @endif
    </div>
    <div style="overflow-x:auto">
    <table class="table mt-table">
        <thead>
            <tr>
                <th>ID Colegio</th>
                <th>Colegio</th>
                <th>Administrador</th>
                <th>Usuario</th>
                <th>Contraseña</th>
                <th>Capacitación</th>
                <th>Fecha capacitación</th>
                <th>Fecha aplicación</th>
                <th>Notas</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="mt-tbody">
            @forelse($filas as $fila)
            @php
                $c = $fila['colegio'];
                $m = $fila['meta'];
                $estado = collect([
                    $fila['capacitacion_proxima'] ? 'cap' : null,
                    $fila['aplicacion_proxima'] ? 'apl' : null,
                    $fila['pendiente'] ? 'pend' : 'listo',
                ])->filter()->implode(' ');
                $texto = mb_strtolower(implode(' ', [$c->nexus_id, $c->name, $m->administrador, $m->usuario, $m->capacitacion, $m->notas]));
                $datos = [
                    'id'                 => $c->id,
                    'nombre'             => $c->name,
                    'administrador'      => $m->administrador,
                    'usuario'            => $m->usuario,
                    'contrasena'         => $m->contrasena,
                    'capacitacion'       => $m->capacitacion,
                    'fecha_capacitacion' => $m->fecha_capacitacion?->format('Y-m-d'),
                    'fecha_aplicacion'   => $m->fecha_aplicacion?->format('Y-m-d'),
                    'notas'              => $m->notas,
                    'action'             => route('seguimiento.guardar', [$slug, $c]),
                    'editado'            => $m->updated_at ? 'Última edición: ' . $m->updated_at->format('d/m/Y H:i') : '',
                ];
            @endphp
            <tr data-estado="{{ $estado }}" data-texto="{{ $texto }}">
                <td style="white-space:nowrap">
                    @if($c->nexus_id)<strong>{{ $c->nexus_id }}</strong>@else<span class="mt-vacio" title="Sin Nexus ID">—</span>@endif
                </td>
                <td style="min-width:160px">
                    <a href="{{ route('schools.show', $c) }}" style="font-weight:600; color:var(--text); text-decoration:none">{{ $c->name }}</a>
                    @if($c->state)<div style="font-size:12px; color:var(--text-muted)">{{ $c->state }}</div>@endif
                </td>
                <td>{!! $m->administrador ? e($m->administrador) : '<span class="mt-vacio">—</span>' !!}</td>
                <td style="font-family:ui-monospace,Consolas,monospace; font-size:12.5px">{!! $m->usuario ? e($m->usuario) : '<span class="mt-vacio" style="font-family:inherit">—</span>' !!}</td>
                <td>
                    @if($m->contrasena)
                        <span class="mt-pwd">
                            <span data-pwd="{{ $m->contrasena }}">••••••••</span>
                            <button type="button" onclick="mtTogglePwd(this)" title="Mostrar / ocultar">👁</button>
                        </span>
                    @else
                        <span class="mt-vacio">—</span>
                    @endif
                </td>
                <td>{!! $m->capacitacion ? e($m->capacitacion) : '<span class="mt-vacio">—</span>' !!}</td>
                @foreach(['fecha_capacitacion', 'fecha_aplicacion'] as $campoFecha)
                    @php $f = $m->{$campoFecha}; @endphp
                    <td class="mt-fecha">
                        @if($f)
                            {{ $f->format('d/m/Y') }}<br>
                            @if($f->isToday())
                                <span class="mt-chip hoy">Hoy</span>
                            @elseif($f->isPast())
                                <span class="mt-chip pas">Realizada</span>
                            @elseif($f->lte(today()->addDays(30)))
                                <span class="mt-chip prox">En {{ (int) today()->diffInDays($f) }} día{{ (int) today()->diffInDays($f) === 1 ? '' : 's' }}</span>
                            @endif
                        @else
                            <span class="badge badge-warning">Pendiente</span>
                        @endif
                    </td>
                @endforeach
                <td><div class="mt-notas" title="{{ $m->notas }}">{{ $m->notas ?: '—' }}</div></td>
                <td style="text-align:right">
                    <div class="mt-acc">
                        <a href="{{ route('schools.tickets.index', $c) }}" class="btn btn-secondary btn-sm" title="Tickets de {{ $c->name }}">🎫</a>
                        <a href="{{ route('schools.visits.index', $c) }}" class="btn btn-secondary btn-sm" title="Sesiones de {{ $c->name }}">📅</a>
                        <button type="button" class="btn btn-primary btn-sm" style="white-space:nowrap"
                                data-colegio="{{ $c->id }}" data-meta="{{ json_encode($datos) }}" onclick="mtEditar(this)">✏️ Editar</button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center; color:var(--text-muted); padding:40px">
                    No hay colegios con {{ $nombre }}. Agrega el servicio en la ficha del colegio (📦 Servicios adicionales).
                </td>
            </tr>
            @endforelse
            <tr id="mt-sin-resultados" style="display:none">
                <td colspan="10" style="text-align:center; color:var(--text-muted); padding:30px">Ningún colegio coincide con la búsqueda.</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

{{-- Modal de edición (uno solo, se llena con los datos del colegio) --}}
<div id="mt-modal" class="mt-overlay" role="dialog" aria-modal="true" aria-labelledby="mt-titulo">
    <form method="POST" id="mt-form" class="mt-modal">
        @csrf
        <div class="mt-head">
            <div>
                <h3 id="mt-titulo">✏️ Editar Meta</h3>
                <small id="mt-editado"></small>
            </div>
            <button type="button" class="mt-close" onclick="mtCerrar()" aria-label="Cerrar">✕</button>
        </div>
        <div class="mt-body">
            @if($colegioConError)
                <div class="mt-err">
                    @foreach($errors->getBag('meta_' . $colegioConError->id)->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="mt-sec">🔐 Acceso a la plataforma</div>
            <div class="full">
                <label for="mt-administrador">Administrador</label>
                <input type="text" name="administrador" id="mt-administrador" class="form-control" maxlength="255" placeholder="Nombre de quien administra Meta en el colegio">
            </div>
            <div>
                <label for="mt-usuario">Usuario</label>
                <input type="text" name="usuario" id="mt-usuario" class="form-control" maxlength="255" autocomplete="off">
            </div>
            <div>
                <label for="mt-contrasena">Contraseña</label>
                <input type="text" name="contrasena" id="mt-contrasena" class="form-control" maxlength="255" autocomplete="off">
            </div>

            <div class="mt-sec">📅 Capacitación y examen</div>
            <div class="full">
                <label for="mt-capacitacion">Capacitación</label>
                <input type="text" name="capacitacion" id="mt-capacitacion" class="form-control" maxlength="255" placeholder="Ej. Capacitación a docentes de inglés">
            </div>
            <div>
                <label for="mt-fecha_capacitacion">Fecha de capacitación</label>
                <input type="date" name="fecha_capacitacion" id="mt-fecha_capacitacion" class="form-control">
            </div>
            <div>
                <label for="mt-fecha_aplicacion">Fecha de aplicación</label>
                <input type="date" name="fecha_aplicacion" id="mt-fecha_aplicacion" class="form-control">
            </div>
            <div class="full">
                <label for="mt-notas">Notas</label>
                <textarea name="notas" id="mt-notas" class="form-control" rows="3" maxlength="2000"></textarea>
            </div>
        </div>
        <div class="mt-foot">
            <button type="button" class="btn btn-secondary" onclick="mtCerrar()">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>

<script>
(function () {
    const CAMPOS = ['administrador', 'usuario', 'contrasena', 'capacitacion', 'fecha_capacitacion', 'fecha_aplicacion', 'notas'];
    const modal = document.getElementById('mt-modal');
    const form  = document.getElementById('mt-form');

    function abrir(d) {
        form.action = d.action;
        document.getElementById('mt-titulo').textContent = '✏️ ' + d.nombre;
        document.getElementById('mt-editado').textContent = d.editado || 'Sin datos capturados todavía';
        CAMPOS.forEach(c => { document.getElementById('mt-' + c).value = d[c] || ''; });
        modal.classList.add('abierto');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('mt-administrador').focus(), 50);
    }

    window.mtEditar = btn => abrir(JSON.parse(btn.dataset.meta));
    window.mtCerrar = () => { modal.classList.remove('abierto'); document.body.style.overflow = ''; };
    modal.addEventListener('click', e => { if (e.target === modal) mtCerrar(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('abierto')) mtCerrar(); });

    window.mtTogglePwd = btn => {
        const s = btn.previousElementSibling;
        const oculta = s.textContent === '••••••••';
        s.textContent = oculta ? s.dataset.pwd : '••••••••';
        btn.textContent = oculta ? '🙈' : '👁';
    };

    // Búsqueda + filtro (select y cards)
    const buscar = document.getElementById('mt-buscar');
    const filtro = document.getElementById('mt-filtro');
    const cards  = document.querySelectorAll('[data-filtro-card]');
    const filas  = document.querySelectorAll('#mt-tbody tr[data-estado]');
    const sinRes = document.getElementById('mt-sin-resultados');

    function aplicar() {
        const q = (buscar?.value || '').trim().toLowerCase();
        const f = filtro?.value || '';
        let visibles = 0;
        filas.forEach(tr => {
            const ok = (!q || tr.dataset.texto.includes(q)) && (!f || tr.dataset.estado.split(' ').includes(f));
            tr.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });
        sinRes.style.display = filas.length && !visibles ? '' : 'none';
        cards.forEach(c => c.classList.toggle('activa', c.dataset.filtroCard === f && f !== ''));
    }
    buscar?.addEventListener('input', aplicar);
    filtro?.addEventListener('change', aplicar);
    cards.forEach(c => c.addEventListener('click', () => {
        if (!filtro) return;
        filtro.value = filtro.value === c.dataset.filtroCard ? '' : c.dataset.filtroCard;
        aplicar();
    }));

    @if($colegioConError)
        // Regresó con errores: reabrir con lo que se había escrito
        abrir(Object.assign(
            JSON.parse(document.querySelector('[data-colegio="{{ $colegioConError->id }}"]').dataset.meta),
            {!! json_encode((object) collect(old())->only(array_keys(\App\Models\SeguimientoMeta::CAMPOS))->all(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        ));
    @endif
})();
</script>
@endsection
