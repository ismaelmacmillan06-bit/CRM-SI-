document.querySelectorAll('[data-formato-entrega]').forEach((button) => {
    const dialog = document.getElementById('formato-entrega-dialog');
    const status = document.getElementById('formato-entrega-resultado');
    const loadingOverlay = document.getElementById('formato-entrega-overlay');
    const dots = loadingOverlay?.querySelector('[data-formato-dots]');
    const options = dialog?.querySelector('[data-formato-opciones]');
    const notice = dialog?.querySelector('[data-formato-aviso]');
    const noticeTitle = dialog?.querySelector('[data-formato-aviso-titulo]');
    const noticeMessage = dialog?.querySelector('[data-formato-aviso-mensaje]');
    const generateButton = dialog?.querySelector('[data-formato-generar]');
    const confirmButton = dialog?.querySelector('[data-formato-confirm]');
    const cancelButton = dialog?.querySelector('footer [data-formato-cancel]');
    let dotsInterval;
    let pendingAvailableType = null;
    const resetNotice = () => {
        if (notice) notice.hidden = true;
        if (options) options.hidden = false;
        if (generateButton) generateButton.hidden = false;
        if (confirmButton) confirmButton.hidden = true;
        if (cancelButton) cancelButton.textContent = 'Cancelar';
        pendingAvailableType = null;
    };
    const showNotice = (title, message, availableType = null) => {
        if (options) options.hidden = true;
        if (notice) notice.hidden = false;
        if (noticeTitle) noticeTitle.textContent = title;
        if (noticeMessage) noticeMessage.textContent = message;
        if (generateButton) generateButton.hidden = true;
        if (cancelButton) cancelButton.textContent = 'Volver a opciones';
        pendingAvailableType = availableType;
        if (confirmButton) {
            confirmButton.hidden = !availableType;
            confirmButton.textContent = availableType ? `Descargar solo ${availableType}` : '';
        }
    };
    const closeDialog = () => {
        resetNotice();
        dialog?.classList.remove('is-open');
        dialog?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        button.focus();
    };
    const showLoading = () => {
        loadingOverlay?.classList.add('is-open');
        loadingOverlay?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        dotsInterval = setInterval(() => {
            if (dots) dots.textContent = '.'.repeat((dots.textContent.length + 1) % 4);
        }, 500);
    };
    const hideLoading = () => {
        loadingOverlay?.classList.remove('is-open');
        loadingOverlay?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        clearInterval(dotsInterval);
    };

    button.addEventListener('click', (event) => {
        event.preventDefault();
        resetNotice();
        dialog?.classList.add('is-open');
        dialog?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        dialog?.querySelector('input[name="formato-tipo"]:checked')?.focus();
    });

    dialog?.querySelectorAll('[data-formato-cancel]').forEach((cancelButton) => {
        cancelButton.addEventListener('click', () => {
            if (cancelButton === dialog.querySelector('footer [data-formato-cancel]') && notice && !notice.hidden) {
                resetNotice();
                return;
            }
            closeDialog();
        });
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
    confirmButton?.addEventListener('click', () => {
        if (!pendingAvailableType) return;
        const availableType = pendingAvailableType;
        const availableOption = dialog.querySelector(`input[name="formato-tipo"][value="${availableType}"]`);
        if (!availableOption) return;
        availableOption.checked = true;
        availableOption.dispatchEvent(new Event('change', { bubbles: true }));
        generateButton?.click();
    });

    generateButton?.addEventListener('click', async () => {
        if (!status || button.dataset.loading) return;

        const tipo = dialog.querySelector('input[name="formato-tipo"]:checked')?.value || 'alumnos';
        const studentCount = Number(status.dataset.studentCount || 0);
        const teacherCount = Number(status.dataset.teacherCount || 0);
        let fileTypes = tipo === 'ambos' ? ['alumnos', 'docentes'] : [tipo];

        if (tipo === 'alumnos' && studentCount === 0) {
            showNotice('No hay alumnos cargados', 'No hay alumnos registrados en este colegio. Puedes volver y elegir otra opción.');
            return;
        }
        if (tipo === 'docentes' && teacherCount === 0) {
            showNotice('No hay docentes cargados', 'No hay docentes registrados en este colegio. Puedes volver y elegir otra opción.');
            return;
        }
        if (tipo === 'ambos' && studentCount === 0 && teacherCount === 0) {
            showNotice('No es posible generar el formato', 'Este colegio no tiene alumnos ni docentes cargados.');
            return;
        }
        if (tipo === 'ambos' && (studentCount === 0 || teacherCount === 0)) {
            const missingType = studentCount === 0 ? 'alumnos' : 'docentes';
            const availableType = studentCount === 0 ? 'docentes' : 'alumnos';
            showNotice(
                `No hay ${missingType} cargados`,
                `¿Deseas descargar únicamente el archivo de ${availableType}?`,
                availableType,
            );
            return;
        }

        closeDialog();
        button.dataset.loading = 'true';
        showLoading();
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
            hideLoading();
        }
    });
});