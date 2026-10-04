@extends('layouts.app')

@section('title', 'Editar Visita')

@section('content')
<div style="max-width: 700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar visita</span>
            <a href="{{ route('schools.visits.index', $visit->school) }}" class="btn btn-secondary btn-sm">← Regresar</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('visits.update', $visit) }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Consultor *</label>
                        <select name="consultant_id" class="form-control" required>
                            <option value="">-- Selecciona --</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}"
                                    {{ old('consultant_id', $visit->consultant_id) == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="pendiente" {{ old('status', $visit->status) == 'pendiente' ? 'selected' : '' }}>⏳ Pendiente</option>
                            <option value="en_curso"  {{ old('status', $visit->status) == 'en_curso'  ? 'selected' : '' }}>🔄 En curso</option>
                            <option value="terminada" {{ old('status', $visit->status) == 'terminada' ? 'selected' : '' }}>✅ Terminada</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Fecha programada *</label>
                        <input type="date" name="scheduled_date" class="form-control"
                               value="{{ old('scheduled_date', $visit->scheduled_date) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="label-visit-date">Fecha realizada</label>
                        <input type="date" name="visit_date" id="input-visit-date" class="form-control"
                               value="{{ old('visit_date', $visit->visit_date) }}">
                        @error('visit_date')<small style="color:var(--danger)">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Próxima visita</label>
                    <input type="date" name="next_visit_date" class="form-control"
                           value="{{ old('next_visit_date', $visit->next_visit_date) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo de visita</label>
                    <input type="text" name="motivo" class="form-control"
                           placeholder="Ej: Seguimiento de arranque, capacitación, entrega de bundles..."
                           value="{{ old('motivo', $visit->motivo) }}">
                </div>

                @include('visits.partials.acudieron-picker', [
                    'selectedIds' => old('attendees', $visit->attendees->pluck('id')->all()),
                ])

                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $visit->notes) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Resumen de visita</label>
                    <textarea name="summary" class="form-control" rows="4">{{ old('summary', $visit->summary) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Evidencia (foto)</label>
                    @if($visit->evidence)
                        <div style="margin-bottom:10px">
                            <img src="{{ asset('storage/' . $visit->evidence) }}"
                                 style="width:120px; height:120px; object-fit:cover; border-radius:8px;">
                            <br>
                            <small style="color:var(--text-muted)">Evidencia actual — sube una nueva para reemplazarla</small>
                        </div>
                    @endif
                    <input type="file" name="evidence" class="form-control" accept="image/*">
                    <small style="color:var(--text-muted)">Máximo 2MB. Formatos: JPG, PNG, GIF</small>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <button type="button" id="btn-generar-pdf" class="btn btn-secondary">📄 Generar PDF</button>
                    <a href="{{ route('schools.visits.index', $visit->school) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // La "Fecha realizada" solo es obligatoria si la visita ya está en curso o terminada
    const statusSelectVisita = document.querySelector('select[name="status"]');
    const inputVisitDate     = document.getElementById('input-visit-date');
    const labelVisitDate     = document.getElementById('label-visit-date');

    function actualizarFechaRealizadaRequerida() {
        const requerida = ['en_curso', 'terminada'].includes(statusSelectVisita.value);
        inputVisitDate.required = requerida;
        labelVisitDate.textContent = requerida ? 'Fecha realizada *' : 'Fecha realizada';
    }

    statusSelectVisita.addEventListener('change', actualizarFechaRealizadaRequerida);
    actualizarFechaRealizadaRequerida();
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/4.2.1/jspdf.umd.min.js" integrity="sha512-plOdviVmws4Y3JAvbnpfKb2hVxKM1lCwsi3vmElYRj+tiDLffZ4FVUj5a8vyKJ9pIgl8JCAHEJ4D1iUKBecswg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function() {
    const DATOS = {
        colegio: @json($visit->school->name),
        evidenceUrl: @json($visit->evidence ? asset('storage/' . $visit->evidence) : null),
        logoUrl: @json(asset('images/logo-si-pdf.png')),
        visitaId: @json($visit->id),
    };

    function cargarImagenComoDataURL(url) {
        return fetch(url)
            .then(resp => resp.blob())
            .then(blob => new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload  = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            }))
            .then(dataURL => new Promise((resolve, reject) => {
                const img = new Image();
                img.onload  = () => resolve({
                    dataURL,
                    format: dataURL.startsWith('data:image/png') ? 'PNG' : 'JPEG',
                    w: img.naturalWidth,
                    h: img.naturalHeight,
                });
                img.onerror = reject;
                img.src = dataURL;
            }));
    }

    function formatearFechaInput(value) {
        if (!value) return '—';
        const [y, m, d] = value.split('-');
        return `${d}/${m}/${y}`;
    }

    async function generarVisitaPDF() {
        const btn = document.getElementById('btn-generar-pdf');
        const textoOriginal = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Generando...';

        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ unit: 'mm', format: 'letter', orientation: 'portrait' });

            const INK        = [43, 33, 27];
            const MUTED       = [140, 124, 105];
            const BRICK       = [178, 58, 44];
            const BRICK_SOFT  = [246, 227, 220];
            const LINE        = [218, 201, 177];

            const pageW    = doc.internal.pageSize.getWidth();
            const pageH    = doc.internal.pageSize.getHeight();
            const margin   = 18;
            const contentW = pageW - margin * 2;
            let y = margin;

            let logo = null;
            try { logo = await cargarImagenComoDataURL(DATOS.logoUrl); } catch (e) { /* sin logo, se omite */ }

            function ensureSpace(needed) {
                if (y + needed > pageH - 18) {
                    doc.addPage();
                    y = margin;
                }
            }

            function header() {
                doc.setFont('helvetica', 'bold'); doc.setFontSize(20);
                doc.setTextColor(...INK);
                doc.text('REPORTE DE VISITA', margin, y + 7);

                doc.setFont('helvetica', 'normal'); doc.setFontSize(11);
                doc.setTextColor(...MUTED);
                doc.text(DATOS.colegio, margin, y + 14);

                if (logo) {
                    const maxW = 34, maxH = 16;
                    let w = maxW, h = w * (logo.h / logo.w);
                    if (h > maxH) { h = maxH; w = h * (logo.w / logo.h); }
                    doc.addImage(logo.dataURL, logo.format, pageW - margin - w, y, w, h);
                }

                y += 20;
                doc.setFillColor(...BRICK);
                doc.rect(margin, y, contentW, 2.2, 'F');
                y += 9;
            }

            header();

            const consultantSelect = document.querySelector('select[name="consultant_id"]');
            const statusSelect     = document.querySelector('select[name="status"]');
            const statusLabels     = { pendiente: 'Pendiente', en_curso: 'En curso', terminada: 'Terminada' };

            const consultorNombre = (consultantSelect.options[consultantSelect.selectedIndex]?.text || '—').trim();
            const estatusLabel    = statusLabels[statusSelect.value] || statusSelect.value;
            const fechaGeneracion = new Date().toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const fechaProxima    = formatearFechaInput(document.querySelector('input[name="next_visit_date"]').value);

            const boxH = 20;
            doc.setFillColor(...BRICK_SOFT);
            doc.roundedRect(margin, y, contentW, boxH, 2, 2, 'F');
            const colW = contentW / 2;
            const metaRows = [
                [['Consultor responsable', consultorNombre], ['Fecha de generación', fechaGeneracion]],
                [['Estatus', estatusLabel], ['Próxima visita', fechaProxima]],
            ];
            metaRows.forEach((fila, ri) => {
                fila.forEach((celda, ci) => {
                    const cx = margin + 6 + ci * colW;
                    const cy = y + 6 + ri * 9;
                    doc.setFont('helvetica', 'bold'); doc.setFontSize(7.5);
                    doc.setTextColor(...MUTED);
                    doc.text(celda[0].toUpperCase(), cx, cy);
                    doc.setFont('helvetica', 'normal'); doc.setFontSize(10);
                    doc.setTextColor(...INK);
                    doc.text(String(celda[1]), cx, cy + 4.6);
                });
            });
            y += boxH + 10;

            function seccion(titulo, contenido) {
                ensureSpace(14);
                doc.setFont('helvetica', 'bold'); doc.setFontSize(11.5);
                doc.setTextColor(...BRICK);
                doc.text(titulo, margin, y);
                y += 2;
                doc.setDrawColor(...LINE); doc.setLineWidth(0.3);
                doc.line(margin, y, margin + contentW, y);
                y += 6;

                doc.setFont('helvetica', 'normal'); doc.setFontSize(10);
                doc.setTextColor(...INK);
                const texto  = (contenido && String(contenido).trim() !== '') ? String(contenido) : '—';
                const lineas = doc.splitTextToSize(texto, contentW);
                lineas.forEach(linea => {
                    ensureSpace(6);
                    doc.text(linea, margin, y);
                    y += 5.6;
                });
                y += 6;
            }

            seccion('Motivo de la visita', document.querySelector('input[name="motivo"]').value);
            seccion('Fecha de la visita (fecha realizada)', formatearFechaInput(document.querySelector('input[name="visit_date"]').value));

            const nombresAcudieron = window.getAcudieronNombres ? window.getAcudieronNombres() : [];
            seccion('Acudieron', nombresAcudieron.length ? nombresAcudieron.join(', ') : '—');

            seccion('Notas', document.querySelector('textarea[name="notes"]').value);
            seccion('Resumen de la visita', document.querySelector('textarea[name="summary"]').value);

            // Evidencia en hoja(s) aparte, al ser imagen
            if (DATOS.evidenceUrl) {
                doc.addPage();
                y = margin;
                doc.setFont('helvetica', 'bold'); doc.setFontSize(14);
                doc.setTextColor(...INK);
                doc.text('Evidencia', margin, y);
                y += 3;
                doc.setFillColor(...BRICK);
                doc.rect(margin, y, contentW, 1.4, 'F');
                y += 10;

                try {
                    const foto = await cargarImagenComoDataURL(DATOS.evidenceUrl);
                    const maxW = contentW, maxH = pageH - y - 24;
                    let w = maxW, h = w * (foto.h / foto.w);
                    if (h > maxH) { h = maxH; w = h * (foto.w / foto.h); }
                    const x = margin + (contentW - w) / 2;
                    doc.addImage(foto.dataURL, foto.format, x, y, w, h);
                } catch (e) {
                    doc.setFont('helvetica', 'normal'); doc.setFontSize(10);
                    doc.setTextColor(...MUTED);
                    doc.text('No se pudo cargar la imagen de evidencia.', margin, y + 10);
                }
            }

            // Pie de página en todas las hojas
            const totalPaginas = doc.internal.getNumberOfPages();
            for (let i = 1; i <= totalPaginas; i++) {
                doc.setPage(i);
                doc.setFillColor(...INK);
                doc.rect(0, pageH - 12, pageW, 12, 'F');
                doc.setFont('helvetica', 'bold'); doc.setFontSize(8);
                doc.setTextColor(255, 255, 255);
                doc.text('REPORTE CONFIDENCIAL  |  MACMILLAN CASTILLO', pageW / 2, pageH - 6.5, { align: 'center' });
                doc.setFont('helvetica', 'normal'); doc.setFontSize(7.5);
                doc.text(`Página ${i} de ${totalPaginas}`, pageW - margin, pageH - 6.5, { align: 'right' });
            }

            const slug = DATOS.colegio.normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-zA-Z0-9]+/g, '-').toLowerCase();
            doc.save(`reporte-visita-${slug}-${DATOS.visitaId}.pdf`);
        } catch (e) {
            console.error(e);
            alert('No se pudo generar el PDF. Intenta de nuevo.');
        } finally {
            btn.disabled = false;
            btn.textContent = textoOriginal;
        }
    }

    document.getElementById('btn-generar-pdf').addEventListener('click', generarVisitaPDF);
})();
</script>
@endsection