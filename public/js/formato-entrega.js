const formatoScript = document.currentScript;
document.querySelectorAll('[data-formato-entrega]').forEach((button) => {
    const accesosUrl = formatoScript?.dataset.accesosUrl;
    const logoUrl = formatoScript?.dataset.logoUrl;
    const tabs = document.querySelector('[data-formato-tabs]');
    const loadingTitle = document.querySelector('#formato-entrega-overlay .formato-loading-title');
    let mode = 'excel';
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
        if (tabs) tabs.hidden = false;
        if (generateButton) generateButton.hidden = false;
        if (confirmButton) confirmButton.hidden = true;
        if (cancelButton) cancelButton.textContent = 'Cancelar';
        pendingAvailableType = null;
    };
    const showNotice = (title, message, availableType = null) => {
        if (options) options.hidden = true;
        if (tabs) tabs.hidden = true;
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

    const setMode = (next) => {
        mode = next;
        tabs?.querySelectorAll('[data-formato-tab]').forEach((tab) => {
            const active = tab.dataset.formatoTab === mode;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        dialog?.querySelectorAll('[data-excel][data-pdf]').forEach((el) => {
            el.textContent = el.dataset[mode];
        });
        if (generateButton) generateButton.textContent = mode === 'pdf' ? 'Generar tarjetas' : 'Generar formato';
    };
    tabs?.querySelectorAll('[data-formato-tab]').forEach((tab) => {
        tab.addEventListener('click', () => setMode(tab.dataset.formatoTab));
    });
    setMode('excel');

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

        const includePdf = mode === 'pdf';
        closeDialog();
        button.dataset.loading = 'true';
        if (loadingTitle) loadingTitle.textContent = includePdf ? 'Generando Tarjetas de Acceso' : 'Generando Reporte Completo';
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

        if (!includePdf) showSummary({
            studentReviewCount: Number(status.dataset.studentReviewCount || 0),
            studentClassCount: Number(status.dataset.studentClassCount || 0),
            teacherReviewCount: Number(status.dataset.teacherReviewCount || 0),
            teacherClassCount: Number(status.dataset.teacherClassCount || 0),
        }, 'Generando el formato:');

        let omitidos = 0;
        const downloadAccessCards = async (fileType) => {
            if (!accesosUrl || !window.generarAccesosPdf) throw new Error('No se pudo cargar el generador de tarjetas.');
            const url = new URL(accesosUrl, window.location.href);
            url.searchParams.set('tipo', fileType);
            const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!response.ok) {
                const err = await response.json().catch(() => null);
                throw new Error(err?.message || 'No se pudieron obtener los datos de las tarjetas.');
            }
            const data = await response.json();
            omitidos += data.omitidos || 0;
            const blob = await window.generarAccesosPdf(data, logoUrl);
            const safeName = data.school.replace(/[<>:"/\\|?*]/g, '-').trim();
            const label = fileType === 'docentes' ? 'Docentes' : 'Alumnos';
            const downloadUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `Tarjetas de acceso ${label}_${safeName}.pdf`;
            document.body.append(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
        };

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
            if (!includePdf) {
                for (const [index, fileType] of fileTypes.entries()) {
                    await downloadFile(fileType, index, fileTypes.length);
                    if (index < fileTypes.length - 1) {
                        await new Promise((resolve) => setTimeout(resolve, 500));
                    }
                }
            } else {
                status.textContent = 'Generando tarjetas de acceso...';
                for (const [index, fileType] of fileTypes.entries()) {
                    if (index > 0) await new Promise((resolve) => setTimeout(resolve, 500));
                    await downloadAccessCards(fileType);
                }
                status.textContent = fileTypes.length > 1 ? 'Tarjetas de acceso generadas (alumnos y docentes).' : 'Tarjetas de acceso generadas.';
                if (omitidos > 0) {
                    status.className = 'alert alert-warning';
                    status.textContent += `\n${omitidos} registro(s) sin usuario o contraseña no tienen tarjeta; revisa sus datos.`;
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