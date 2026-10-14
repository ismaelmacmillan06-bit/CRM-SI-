document.querySelectorAll('[data-formato-entrega]').forEach((button) => {
    const dialog = document.getElementById('formato-entrega-dialog');
    const status = document.getElementById('formato-entrega-resultado');
    const generateButton = dialog?.querySelector('[data-formato-generar]');
    const closeDialog = () => {
        dialog?.classList.remove('is-open');
        dialog?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        button.focus();
    };

    button.addEventListener('click', (event) => {
        event.preventDefault();
        dialog?.classList.add('is-open');
        dialog?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        dialog?.querySelector('input[name="formato-tipo"]:checked')?.focus();
    });

    dialog?.querySelectorAll('[data-formato-cancel]').forEach((cancelButton) => {
        cancelButton.addEventListener('click', closeDialog);
    });
    dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) closeDialog();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && dialog?.classList.contains('is-open')) closeDialog();
    });
    dialog?.querySelectorAll('input[name="formato-tipo"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            dialog.querySelectorAll('.formato-opcion').forEach((option) => option.classList.remove('is-selected'));
            radio.closest('.formato-opcion')?.classList.add('is-selected');
        });
    });

    generateButton?.addEventListener('click', async () => {
        if (!status || button.dataset.loading) return;

        const tipo = dialog.querySelector('input[name="formato-tipo"]:checked')?.value || 'alumnos';
        closeDialog();
        button.dataset.loading = 'true';
        status.className = 'alert alert-success';
        status.style.display = 'block';

        const showSummary = (counts, prefix) => {
            const lines = [];
            if (tipo === 'alumnos' || tipo === 'ambos') {
                const studentsNoun = counts.studentReviewCount === 1 ? 'alumno' : 'alumnos';
                const classesNoun = counts.studentClassCount === 1 ? 'clase' : 'clases';
                lines.push(`Alumnos: ${counts.studentReviewCount} ${studentsNoun} por revisar; ${counts.studentClassCount} ${classesNoun}.`);
            }
            if (tipo === 'docentes' || tipo === 'ambos') {
                const teachersNoun = counts.teacherReviewCount === 1 ? 'docente' : 'docentes';
                const gradesNoun = counts.teacherClassCount === 1 ? 'grado/hoja' : 'grados/hojas';
                lines.push(`Docentes: ${counts.teacherReviewCount} ${teachersNoun} por revisar (datos incompletos o género por confirmar); ${counts.teacherClassCount} ${gradesNoun}.`);
            }
            status.textContent = `${prefix}\n${lines.join('\n')}`;
        };

        showSummary({
            studentReviewCount: Number(status.dataset.studentReviewCount || 0),
            studentClassCount: Number(status.dataset.studentClassCount || 0),
            teacherReviewCount: Number(status.dataset.teacherReviewCount || 0),
            teacherClassCount: Number(status.dataset.teacherClassCount || 0),
        }, 'Generando el formato:');

        const downloadFile = async (fileType, index, total) => {
            const url = new URL(button.href, window.location.href);
            url.searchParams.set('tipo', fileType);
            const response = await fetch(url, { credentials: 'same-origin' });
            const contentType = response.headers.get('Content-Type') || '';
            if (!response.ok || !contentType.includes('spreadsheetml')) {
                throw new Error('No se pudo generar el archivo. Actualiza la página e inténtalo de nuevo.');
            }

            const counts = {
                studentReviewCount: Number(response.headers.get('X-Alumnos-Por-Revisar') || 0),
                studentClassCount: Number(response.headers.get('X-Clases-Alumnos') || 0),
                teacherReviewCount: Number(response.headers.get('X-Docentes-Por-Revisar') || 0),
                teacherClassCount: Number(response.headers.get('X-Grados-Docentes') || 0),
            };
            status.dataset.studentReviewCount = counts.studentReviewCount;
            status.dataset.studentClassCount = counts.studentClassCount;
            status.dataset.teacherReviewCount = counts.teacherReviewCount;
            status.dataset.teacherClassCount = counts.teacherClassCount;
            showSummary(counts, total > 1 ? `Generado archivo ${index + 1} de ${total}:` : 'Formato generado:');

            const disposition = response.headers.get('Content-Disposition') || '';
            const encodedName = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
            const plainName = disposition.match(/filename="?([^";]+)"?/i)?.[1];
            const filename = encodedName ? decodeURIComponent(encodedName) : (plainName || 'Cuentas de Alumno.xlsx');
            const blob = await response.blob();
            const downloadUrl = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = downloadUrl;
            downloadLink.download = filename;
            document.body.append(downloadLink);
            downloadLink.click();
            downloadLink.remove();
            setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
        };

        try {
            const fileTypes = tipo === 'ambos' ? ['alumnos', 'docentes'] : [tipo];
            for (const [index, fileType] of fileTypes.entries()) {
                await downloadFile(fileType, index, fileTypes.length);
                if (index < fileTypes.length - 1) {
                    await new Promise((resolve) => setTimeout(resolve, 500));
                }
            }
        } catch (error) {
            status.className = 'alert alert-danger';
            status.textContent = error.message || 'No se pudo descargar el formato.';
        } finally {
            delete button.dataset.loading;
        }
    });
});