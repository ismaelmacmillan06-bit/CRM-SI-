// Tarjetas de acceso recortables (Global Educa). Dibujo vectorial con jsPDF.
(function () {
    const VENDOR = new URL('../vendor/', document.currentScript.src).href;

    const NAVY = [17, 17, 17];
    const GREEN = [227, 14, 14];
    const GREEN_SOFT = [236, 236, 236];
    const LINE = [190, 190, 190];

    const L = { pageW: 215.9, pageH: 279.4, margin: 14, colGap: 6, rowGap: 6, pad: 4.5, hHeader: 15, hName: 6.5, hClase: 4.5, hBrand: 9, gap: 2, headTop: 20, qr: 11 };
    L.cardW = (L.pageW - L.margin * 2 - L.colGap) / 2;
    // 3 filas x 2 columnas = 6 tarjetas que llenan la hoja completa
    L.cardH = (L.pageH - L.margin * 2 - L.headTop - L.rowGap * 2) / 3;

    const scripts = {};
    function loadScript(file) {
        scripts[file] ??= new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = VENDOR + file;
            s.onload = resolve;
            s.onerror = () => reject(new Error('No se pudo cargar la librería ' + file + '.'));
            document.head.append(s);
        });
        return scripts[file];
    }

    async function loadLogo(url) {
        if (!url) return null;
        try {
            const res = await fetch(url, { credentials: 'same-origin' });
            if (!res.ok) return null;
            const blob = await res.blob();
            const dataURL = await new Promise((resolve) => {
                const r = new FileReader();
                r.onload = () => resolve(r.result);
                r.readAsDataURL(blob);
            });
            const size = await new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve({ w: img.naturalWidth, h: img.naturalHeight });
                img.onerror = () => resolve(null);
                img.src = dataURL;
            });
            return size ? { dataURL, ...size } : null;
        } catch {
            return null;
        }
    }

    // Reduce la fuente hasta que quepa; nunca trunca el texto.
    function fitText(doc, text, maxWidth, size, minSize) {
        const t = String(text || '—');
        let s = size;
        doc.setFontSize(s);
        while (s > minSize && doc.getTextWidth(t) > maxWidth) {
            s -= 0.5;
            doc.setFontSize(s);
        }
        return t;
    }

    function qrMatrix(text) {
        if (!text || !window.qrcode) return null;
        const qr = window.qrcode(0, 'M');
        qr.addData(text);
        qr.make();
        const n = qr.getModuleCount();
        return { n, dark: (r, c) => qr.isDark(r, c) };
    }

    function drawQr(doc, qr, x, y, size) {
        const m = size / qr.n;
        doc.setFillColor(...NAVY);
        for (let r = 0; r < qr.n; r++) {
            for (let c = 0; c < qr.n; c++) {
                if (qr.dark(r, c)) doc.rect(x + c * m, y + r * m, m + 0.02, m + 0.02, 'F');
            }
        }
    }

    function drawCard(doc, x, y, h, p, clase, ctx) {
        const { logo, link, qr } = ctx;
        const w = L.cardW;
        doc.setLineDashPattern([1, 1], 0);
        doc.setDrawColor(...LINE);
        doc.setLineWidth(0.25);
        doc.roundedRect(x, y, w, h, 2, 2, 'D');
        doc.setLineDashPattern([], 0);

        const ix = x + L.pad;
        const iw = w - L.pad * 2;
        const fixed = L.pad * 2 + L.hHeader + L.gap * 3 + L.hName + L.hClase + (link ? L.gap + L.hBrand : 0);
        const hField = Math.min(14, (h - fixed) / 2);
        const content = fixed + hField * 2;
        let cy = y + (h - content) / 2 + L.pad;

        const logoMaxW = qr ? iw - L.qr - 4 : iw * 0.6;
        if (logo) {
            const maxH = L.hHeader - 2;
            let dh = maxH;
            let dw = dh * (logo.w / logo.h);
            if (dw > logoMaxW) { dw = logoMaxW; dh = dw / (logo.w / logo.h); }
            try { doc.addImage(logo.dataURL, 'PNG', ix, cy + (L.hHeader - 2 - dh) / 2, dw, dh, 'ge-logo', 'FAST'); } catch { /* logo inválido */ }
        } else {
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(10);
            doc.setTextColor(...NAVY);
            doc.text('GLOBAL', ix, cy + 5);
            doc.setTextColor(...GREEN);
            doc.text('EDUCA', ix + doc.getTextWidth('GLOBAL '), cy + 5);
        }
        if (qr) drawQr(doc, qr, ix + iw - L.qr, cy - 1, L.qr);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        const pillText = 'DATOS DE ACCESO';
        const pillW = doc.getTextWidth(pillText) + 6;
        const pillH = 5.4;
        const pillX = ix + iw - (qr ? L.qr + 3 : 0) - pillW;
        const pillY = cy + (L.hHeader - 2 - pillH) / 2;
        doc.setFillColor(...GREEN);
        doc.roundedRect(pillX, pillY, pillW, pillH, pillH / 2, pillH / 2, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text(pillText, pillX + pillW / 2, pillY + pillH / 2 + 0.9, { align: 'center' });
        cy += L.hHeader;

        doc.setDrawColor(220, 220, 220);
        doc.setLineWidth(0.2);
        doc.line(ix, cy, x + w - L.pad, cy);
        cy += L.gap;

        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...NAVY);
        const name = fitText(doc, p.name, iw, 13, 7);
        doc.text(name, ix, cy + L.hName * 0.62);
        cy += L.hName + L.gap;

        doc.setTextColor(...GREEN);
        const claseTxt = fitText(doc, String(clase).toUpperCase(), iw, 7.5, 5.5);
        doc.text(claseTxt, ix, cy + 3.4);
        cy += L.hClase + L.gap;

        const credsH = hField * 2;
        doc.setDrawColor(...LINE);
        doc.setLineWidth(0.25);
        doc.roundedRect(ix, cy, iw, credsH, 1.5, 1.5, 'D');
        [['Usuario', p.username], ['Contraseña', p.password]].forEach(([label, value], i) => {
            const fy = cy + hField * i;
            if (i > 0) { doc.setLineWidth(0.2); doc.line(ix, fy, ix + iw, fy); }
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(7.5);
            doc.setTextColor(...GREEN);
            doc.text(label.toUpperCase(), ix + 2.5, fy + 4);
            doc.setFont('courier', 'bold');
            doc.setTextColor(...NAVY);
            const val = fitText(doc, value, iw - 5, 11, 4.5);
            doc.text(val, ix + 2.5, fy + hField - 3);
        });
        cy += credsH + L.gap;

        if (link) {
            doc.setFillColor(...GREEN_SOFT);
            doc.roundedRect(ix, cy, iw, L.hBrand, 1.5, 1.5, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(7);
            doc.setTextColor(...NAVY);
            doc.text('Liga de acceso:', ix + 2.5, cy + 3.3);
            doc.setTextColor(...GREEN);
            const url = fitText(doc, link, iw - 5, 8, 5);
            doc.text(url, ix + 2.5, cy + 6.6);
        }
    }

    // data: { school, link, groups: [{ clase, alumnos: [{ name, username, password }] }] }
    async function generarAccesosPdf(data, logoUrl) {
        await Promise.all([loadScript('jspdf.umd.min.js'), data.link ? loadScript('qrcode.js') : null]);
        const logo = await loadLogo(logoUrl);
        const qr = qrMatrix(data.link);
        const ctx = { logo, link: data.link, qr };
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'mm', format: 'letter', orientation: 'portrait' });
        const h = L.cardH;
        let started = false;
        let col = 0;
        let y = 0;

        const newPage = (clase) => {
            if (started) doc.addPage();
            started = true;
            col = 0;
            y = L.margin + L.headTop;
            const top = L.margin;
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...NAVY);
            doc.text(fitText(doc, clase, L.pageW - L.margin * 2, 18, 10), L.pageW / 2, top + 6, { align: 'center' });
            doc.setFillColor(...GREEN);
            doc.roundedRect(L.pageW / 2 - 9, top + 8, 18, 1.2, 0.6, 0.6, 'F');
            doc.setTextColor(...GREEN);
            doc.text(fitText(doc, String(data.school).toUpperCase(), L.pageW - L.margin * 2, 9.5, 6), L.pageW / 2, top + 13.5, { align: 'center' });
            doc.setFontSize(8);
            doc.setTextColor(...NAVY);
            doc.text('DATOS DE ACCESO  \u2022  RECORTA Y ENTREGA', L.pageW / 2, top + 18, { align: 'center' });
        };

        data.groups.forEach((g) => {
            newPage(g.clase);
            g.alumnos.forEach((p) => {
                if (col === 2) { col = 0; y += h + L.rowGap; }
                if (y + h > L.pageH - L.margin + 0.5) newPage(g.clase);
                drawCard(doc, L.margin + col * (L.cardW + L.colGap), y, h, p, g.clase, ctx);
                col++;
            });
        });

        return doc.output('blob');
    }

    window.generarAccesosPdf = generarAccesosPdf;
})();
