{{--
    Buscador con chips para "Acudieron" — pensado para producción real (40+
    personas en Equipo SI), donde un listado de checkboxes sería impráctico.
    Espera: $consultants (colección con ->id y ->user->name) y
    $selectedIds (array de ids ya seleccionados, ej. old() o attendees actuales).
--}}
<div class="form-group">
    <label class="form-label">Acudieron</label>
    <div id="acudieron-picker" style="position:relative">
        <input type="text" id="acudieron-buscar" class="form-control" autocomplete="off"
               placeholder="🔍 Buscar persona de Equipo SI...">
        <div id="acudieron-resultados"
             style="display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:20;
                    background:var(--surface); border:1px solid var(--border); border-radius:8px;
                    box-shadow:0 8px 20px rgba(0,0,0,0.12); max-height:240px; overflow-y:auto;">
        </div>
        <div id="acudieron-chips" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;"></div>
        <div id="acudieron-inputs"></div>
    </div>
</div>

<script>
(function() {
    const PERSONAS = @json($consultants->map(fn($c) => ['id' => $c->id, 'nombre' => $c->user->name])->values());
    let seleccionados = @json(array_values($selectedIds ?? []));

    const input      = document.getElementById('acudieron-buscar');
    const resultados  = document.getElementById('acudieron-resultados');
    const chipsBox    = document.getElementById('acudieron-chips');
    const inputsBox   = document.getElementById('acudieron-inputs');

    function norm(s) {
        return (s || '').toString().toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    function render() {
        chipsBox.innerHTML  = '';
        inputsBox.innerHTML = '';
        seleccionados.forEach(function(id) {
            const persona = PERSONAS.find(p => p.id === id);
            if (!persona) return;

            const chip = document.createElement('span');
            chip.style.cssText = 'display:inline-flex; align-items:center; gap:6px; padding:5px 10px 5px 12px; ' +
                'background:var(--surface2); border:1px solid var(--border); border-radius:20px; font-size:13px;';
            chip.innerHTML = '<span>' + persona.nombre.replace(/[<>&]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c])) + '</span>';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = '✕';
            btn.style.cssText = 'background:none; border:none; cursor:pointer; color:var(--text-muted); ' +
                'font-size:12px; line-height:1; padding:0;';
            btn.addEventListener('click', function() {
                seleccionados = seleccionados.filter(x => x !== id);
                render();
            });
            chip.appendChild(btn);
            chipsBox.appendChild(chip);

            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'attendees[]';
            hidden.value = id;
            inputsBox.appendChild(hidden);
        });
    }

    function mostrarResultados() {
        const q = norm(input.value);
        const disponibles = PERSONAS
            .filter(p => !seleccionados.includes(p.id))
            .filter(p => q === '' || norm(p.nombre).includes(q))
            .sort((a, b) => a.nombre.localeCompare(b.nombre));

        if (disponibles.length === 0) {
            resultados.innerHTML = '<div style="padding:10px 14px; font-size:13px; color:var(--text-muted)">' +
                (PERSONAS.length === 0 ? 'No hay personal de Equipo SI registrado.' : 'Sin resultados.') + '</div>';
        } else {
            resultados.innerHTML = '';
            disponibles.forEach(function(persona) {
                const item = document.createElement('div');
                item.textContent = persona.nombre;
                item.style.cssText = 'padding:9px 14px; font-size:13px; cursor:pointer;';
                item.addEventListener('mouseenter', () => item.style.background = 'var(--surface2)');
                item.addEventListener('mouseleave', () => item.style.background = '');
                item.addEventListener('click', function() {
                    seleccionados.push(persona.id);
                    input.value = '';
                    render();
                    mostrarResultados();
                    resultados.style.display = 'none';
                    input.focus();
                });
                resultados.appendChild(item);
            });
        }
        resultados.style.display = 'block';
    }

    input.addEventListener('focus', mostrarResultados);
    input.addEventListener('input', mostrarResultados);

    // Expuesto para otros scripts de la página (ej. generador de PDF)
    window.getAcudieronNombres = function() {
        return seleccionados
            .map(id => PERSONAS.find(p => p.id === id))
            .filter(Boolean)
            .map(p => p.nombre);
    };

    document.addEventListener('click', function(e) {
        if (!document.getElementById('acudieron-picker').contains(e.target)) {
            resultados.style.display = 'none';
        }
    });

    render();
})();
</script>
