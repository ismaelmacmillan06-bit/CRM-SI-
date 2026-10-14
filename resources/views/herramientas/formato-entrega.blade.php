@extends('layouts.app')

@section('title', 'Formato de Entrega Global Educa')

@section('content')
<style>
    .fe-intro { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: 0 0 22px; }
    .fe-intro-copy { min-width: 0; }
    .fe-intro h2 { font-family: 'Bricolage Grotesque', sans-serif; font-size: 21px; font-weight: 700; }
    .fe-intro p { color: var(--text-muted); font-size: 13px; margin-top: 4px; }
    .fe-back { display: inline-flex; align-items: center; justify-content: center; gap: 8px; flex-shrink: 0; padding: 9px 13px; border: 1px solid var(--border); border-radius: 7px; background: var(--surface); color: var(--text); font-size: 12px; font-weight: 600; text-decoration: none; transition: background .15s, border-color .15s; }
    .fe-back:hover { background: var(--surface2); border-color: var(--text-muted); color: var(--text); }
    .fe-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(380px, 100%), 1fr)); gap: 20px; margin-bottom: 20px; }
    .fe-card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
    .fe-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); }
    .fe-card-title { font-family: 'Bricolage Grotesque', sans-serif; font-size: 16px; font-weight: 600; }
    .fe-card-body { padding: 20px; }
    .fe-tag { border: 1px solid var(--border); border-radius: 20px; padding: 4px 9px; color: var(--text-muted); background: var(--surface2); font-size: 11px; white-space: nowrap; }
    #fe-drop { display: block; border: 2px dashed var(--border); border-radius: 9px; padding: 30px 16px; text-align: center; cursor: pointer; color: var(--text-muted); font-size: 13px; line-height: 1.7; transition: .2s; }
    #fe-drop:hover, #fe-drop.is-over { border-color: var(--accent); background: rgba(226,35,26,.04); }
    #fe-drop strong { color: var(--text); font-weight: 600; }
    #fe-file { display: none; }
    .fe-help { color: var(--text-muted); font-size: 12px; margin-bottom: 14px; line-height: 1.55; }
    .fe-row { display: grid; grid-template-columns: minmax(120px, 200px) 1fr; gap: 12px; align-items: center; margin-bottom: 12px; }
    .fe-id { display: flex; flex-direction: column; gap: 3px; min-width: 0; color: var(--text-muted); font-size: 11px; }
    .fe-id strong { color: var(--text); font-size: 13px; overflow-wrap: anywhere; }
    .fe-message { margin-top: 14px; padding: 11px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface2); color: var(--text-muted); font-size: 13px; line-height: 1.5; }
    .fe-message:empty { display: none; }
    .fe-message.is-error { border-color: #fecaca; background: #fee2e2; color: #991b1b; }
    .fe-message.is-success { border-color: #a7f3d0; background: #d1fae5; color: #065f46; }
    .fe-results { display: flex; flex-direction: column; gap: 10px; }
    .fe-result { display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid var(--border); border-left: 4px solid var(--accent); border-radius: 8px; }
    .fe-file-icon { flex: 0 0 42px; height: 42px; display: grid; place-items: center; border-radius: 8px; background: #fdecea; color: var(--accent); font-size: 10px; font-weight: 700; }
    .fe-result-info { min-width: 0; flex: 1; }
    .fe-result-name { font-size: 13px; font-weight: 600; overflow-wrap: anywhere; }
    .fe-result-meta { margin-top: 3px; color: var(--text-muted); font-size: 11px; }
    .fe-result-status { display: inline-flex; margin-top: 7px; padding: 3px 8px; border-radius: 20px; background: #d1fae5; color: #065f46; font-size: 10px; }
    .fe-result-status.needs-review { background: #fee2e2; color: #991b1b; }
    .fe-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .fe-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 15px; border: 0; border-radius: 7px; background: var(--accent); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; }
    .fe-btn:hover { background: var(--accent2); }
    .fe-btn:disabled { opacity: .5; cursor: default; }
    .fe-btn-secondary { border: 1px solid var(--border); background: var(--surface2); color: var(--text); }
    .fe-btn-secondary:hover { background: var(--border); }
    @media(max-width:700px) { .fe-intro { align-items: flex-start; } .fe-back { padding: 8px 10px; } .fe-back-label { display: none; } .fe-row { grid-template-columns: 1fr; gap: 6px; } .fe-result { flex-wrap: wrap; } .fe-result-info { flex-basis: calc(100% - 56px); } .fe-result .fe-btn { width: 100%; } .fe-card-head, .fe-card-body { padding: 14px; } }
</style>

<div class="fe-intro" id="fe-intro" data-logo-url="{{ asset('images/logo-formato-entrega.png') }}">
    <div class="fe-intro-copy">
        <h2>Formato de Entrega Global Educa</h2>
        <p>Genera archivos de alumnos organizados por colegio y clase.</p>
    </div>
    <a href="{{ route('herramientas.index') }}" class="fe-back" aria-label="Volver a Herramientas SI" title="Volver a Herramientas SI">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
        <span class="fe-back-label">Volver a herramientas</span>
    </a>
</div>

<div class="fe-grid">
    <section class="fe-card">
        <div class="fe-card-head"><h3 class="fe-card-title">1. Archivos de alumnos</h3><span class="fe-tag">.xlsx · .xls · .csv</span></div>
        <div class="fe-card-body">
            <label id="fe-drop" for="fe-file"><strong id="fe-filename">Haz clic o arrastra tus archivos aquí</strong><br>Puedes seleccionar varios archivos. Se requieren: Nombre (s), Apellidos, Usuario, Password, Nivel, Grado, Grupo e ID Colegio.</label>
            <input id="fe-file" type="file" accept=".xlsx,.xls,.csv" multiple>
        </div>
    </section>
    <section class="fe-card" id="fe-schools" hidden>
        <div class="fe-card-head"><h3 class="fe-card-title">2. Nombre de cada colegio</h3></div>
        <div class="fe-card-body">
            <p class="fe-help">El nombre se incluye en el archivo y en el encabezado de cada hoja. Es opcional.</p>
            <div id="fe-school-list"></div>
        </div>
    </section>
</div>

<section class="fe-card" style="margin-bottom:20px">
    <div class="fe-card-body">
        <div class="fe-actions">
            <button type="button" id="fe-generate" class="fe-btn" disabled>Generar Excel</button>
            <div id="fe-message" class="fe-message" role="status" aria-live="polite"></div>
        </div>
    </div>
</section>

<section class="fe-card" id="fe-output" hidden>
    <div class="fe-card-head"><h3 class="fe-card-title">Archivos generados</h3><button type="button" id="fe-download-all" class="fe-btn fe-btn-secondary" hidden>Descargar todos</button></div>
    <div class="fe-card-body"><div id="fe-results" class="fe-results"></div></div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const HEAD = ['Nombre', 'Apellido paterno', 'Apellido materno', 'Usuario', 'Contraseña', 'Nivel', 'Grado', 'Grupo', 'Género'];
    const FEM = new Set('maria ana sofia valentina camila regina ximena valeria mariana daniela fernanda renata victoria isabella luciana paula andrea natalia gabriela alejandra jimena emily karla carla paola mia lucia julieta samantha danna dana sara elena emma alondra angela ariana barbara brenda bianca carolina catalina cecilia claudia cristina diana fatima jazmin jessica julia karen laura michelle monica naomi nicole olivia patricia rebeca rosa sandra teresa vanessa veronica zoe'.split(' '));
    const MASC = new Set('jose juan luis carlos jesus miguel angel francisco antonio pedro manuel javier jorge alejandro daniel david eduardo fernando ricardo roberto sergio rafael mario oscar victor hector raul arturo alberto adrian andres diego emiliano santiago mateo sebastian leonardo matias nicolas samuel gael thiago tadeo iker dylan bruno axel christian kevin brian jonathan joshua josue isaac ismael ivan aaron abraham alan alexis alfredo alvaro benjamin cesar damian edgar elias enrique erick ernesto esteban fabian felipe gabriel gerardo gustavo hugo ignacio israel jaime jair jason joaquin julian julio leo lorenzo lucas marco marcos mauricio maximo nathan noah noe omar pablo patricio ramiro rodrigo ruben salvador saul tomas ulises uriel vicente xavier martin liam'.split(' '));
    const PART = new Set('de del la las los da das dos di van von san santa'.split(' '));
    const COLOR = { red: 'DA0920', charcoal: '3A3939', alternate: 'F6F6F7', line: 'E3E3E5', review: 'FCE9EB' };
    const fileInput = $('fe-file');
    const dropZone = $('fe-drop');
    let groups = new Map();
    let outputs = [];

    function showMessage(text, type = '') {
        const message = $('fe-message');
        message.textContent = text;
        message.className = 'fe-message' + (type ? ' is-' + type : '');
    }

    const normalize = (value) => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase();
    const keyText = (value) => normalize(value).replace(/\s+/g, ' ');
    const total = (count) => count === 1 ? '1 alumno' : count + ' alumnos';

    function parseCsv(text) {
        if (text.charCodeAt(0) === 0xFEFF) text = text.slice(1);
        const first = text.split(/\r?\n/, 1)[0];
        const separator = [',', ';', '\t'].map((char) => [char, first.split(char).length]).sort((a, b) => b[1] - a[1])[0][0];
        const rows = [];
        let row = [], value = '', quoted = false;
        for (let i = 0; i < text.length; i++) {
            const char = text[i];
            if (quoted) {
                if (char === '"' && text[i + 1] === '"') { value += '"'; i++; }
                else if (char === '"') quoted = false;
                else value += char;
            } else if (char === '"') quoted = true;
            else if (char === separator) { row.push(value); value = ''; }
            else if (char === '\n' || char === '\r') {
                if (char === '\r' && text[i + 1] === '\n') i++;
                row.push(value); rows.push(row); row = []; value = '';
            } else value += char;
        }
        if (value !== '' || row.length) { row.push(value); rows.push(row); }
        return rows;
    }

    async function readRows(file) {
        const buffer = await file.arrayBuffer();
        if (/\.csv$/i.test(file.name)) {
            let text;
            try { text = new TextDecoder('utf-8', { fatal: true }).decode(buffer); }
            catch { text = new TextDecoder('windows-1252').decode(buffer); }
            return parseCsv(text);
        }
        const workbook = XLSX.read(buffer, { type: 'array' });
        const sheet = workbook.SheetNames.includes('Sheet1') ? 'Sheet1' : workbook.SheetNames[0];
        return XLSX.utils.sheet_to_json(workbook.Sheets[sheet], { header: 1, defval: '', raw: false });
    }

    function splitSurnames(value) {
        const tokens = String(value ?? '').trim().replace(/\s+/g, ' ').split(' ').filter(Boolean);
        if (!tokens.length) return ['', ''];
        const surnames = [];
        let pending = '', joinNext = false;
        for (const token of tokens) {
            const lower = token.toLowerCase();
            if (PART.has(lower)) pending += token + ' ';
            else if ((lower === 'y' || lower === 'e') && surnames.length && !pending) { surnames[surnames.length - 1] += ' ' + token; joinNext = true; }
            else {
                const surname = pending + token;
                pending = '';
                if (joinNext) { surnames[surnames.length - 1] += ' ' + surname; joinNext = false; }
                else surnames.push(surname);
            }
        }
        if (pending) {
            if (surnames.length) surnames[surnames.length - 1] += ' ' + pending.trim();
            else surnames.push(pending.trim());
        }
        return [surnames[0] || '', surnames.slice(1).join(' ')];
    }

    function gender(name) {
        const first = normalize(name).replace(/[.'-]/g, ' ').split(/\s+/).find(Boolean) || '';
        if (first.length < 2) return 'Revisar';
        if (FEM.has(first)) return 'Femenino';
        if (MASC.has(first) || /(ander|rick)$/.test(first)) return 'Masculino';
        if (/(lyn|lynn|elle|ette)$/.test(first)) return 'Femenino';
        if (first.endsWith('a')) return 'Femenino';
        if (first.endsWith('o')) return 'Masculino';
        return 'Revisar';
    }

    function mapRows(rows, fileName) {
        const headers = rows[0] || [];
        const find = (aliases) => headers.findIndex((header) => aliases.includes(normalize(header)));
        const columns = {
            name: find(['nombre (s)', 'nombre(s)', 'nombre']), surname: find(['apellidos', 'apellido']),
            user: find(['usuario']), password: find(['password', 'contrasena']), level: find(['nivel']),
            grade: find(['grado', 'gardo']), group: find(['grupo']),
            id: find(['id colegio', 'id de colegio', 'idcolegio', 'id_colegio', 'id del colegio', 'id escuela', 'id']),
            school: find(['colegio', 'nombre colegio', 'nombre del colegio', 'nombre de colegio', 'escuela'])
        };
        const required = { name: 'Nombre (s)', surname: 'Apellidos', user: 'Usuario', password: 'Password', level: 'Nivel', grade: 'Grado', group: 'Grupo', id: 'ID Colegio' };
        const missing = Object.entries(required).filter(([key]) => columns[key] < 0).map(([, label]) => label);
        if (missing.length) throw new Error(fileName + ': faltan columnas en la fila 1: ' + missing.join(', '));
        const result = [];
        for (let i = 1; i < rows.length; i++) {
            const cell = (key) => columns[key] < 0 ? '' : String(rows[i][columns[key]] ?? '').trim();
            const name = cell('name'), surname = cell('surname'), user = cell('user');
            const level = cell('level'), grade = cell('grade'), group = cell('group');
            if (!(name || surname || user) || !(level || grade || group)) continue;
            const [paternal, maternal] = splitSurnames(surname);
            result.push({
                id: cell('id').replace(/\.0+$/, '') || 'SIN ID', school: cell('school'),
                classroom: (level + ' ' + grade + '°' + group).trim(),
                values: [name, paternal, maternal, user, cell('password'), level, grade, group, gender(name)]
            });
        }
        return result;
    }

    function addRows(rows) {
        for (const item of rows) {
            if (!groups.has(item.id)) groups.set(item.id, { school: '', classes: new Map(), students: [], seen: new Map(), duplicates: 0, conflicts: 0 });
            const school = groups.get(item.id);
            if (item.school && !school.school) school.school = item.school;
            const values = item.values;
            const key = values[3] ? 'u:' + keyText(values[3]) : 'n:' + keyText([values[0], values[1], values[2], values[5], values[6], values[7]].join('|'));
            const previous = school.seen.get(key);
            if (previous) {
                school.duplicates++;
                if (keyText(previous.slice(0, 8).join('|')) !== keyText(values.slice(0, 8).join('|'))) school.conflicts++;
                continue;
            }
            school.seen.set(key, values);
            if (!school.classes.has(item.classroom)) school.classes.set(item.classroom, []);
            school.classes.get(item.classroom).push(values);
            school.students.push(values);
        }
    }

    async function setFiles(files) {
        if (!files.length) return;
        $('fe-generate').disabled = true;
        $('fe-results').replaceChildren(); $('fe-output').hidden = true; $('fe-schools').hidden = true;
        $('fe-filename').textContent = files.length === 1 ? files[0].name : files.length + ' archivos seleccionados';
        showMessage('Leyendo archivos…');
        groups = new Map();
        try {
            for (const file of files) addRows(mapRows(await readRows(file), file.name));
            if (!groups.size) throw new Error('No se encontraron alumnos con datos de nivel, grado o grupo.');
            const list = $('fe-school-list'); list.replaceChildren();
            for (const [id, school] of groups) {
                let name = school.school;
                try { name = name || localStorage.getItem('colegioSI_' + id) || ''; } catch {}
                const row = document.createElement('div'); row.className = 'fe-row';
                const info = document.createElement('div'); info.className = 'fe-id';
                const label = document.createElement('strong'); label.textContent = 'ID ' + id;
                const count = document.createElement('span'); count.textContent = total(school.students.length) + (school.duplicates ? ' · ' + school.duplicates + ' repetidos omitidos' : '');
                info.append(label, count);
                const input = document.createElement('input'); input.type = 'text'; input.className = 'form-control'; input.value = name; input.placeholder = 'Nombre del colegio (opcional)'; school.input = input;
                row.append(info, input); list.append(row);
            }
            $('fe-schools').hidden = false; $('fe-generate').disabled = false;
            const duplicates = [...groups.values()].reduce((sum, school) => sum + school.duplicates, 0);
            const conflicts = [...groups.values()].reduce((sum, school) => sum + school.conflicts, 0);
            showMessage('Se generará ' + groups.size + (groups.size === 1 ? ' archivo.' : ' archivos, uno por ID de colegio.') + (duplicates ? ' Se omitieron ' + duplicates + ' registros repetidos.' : '') + (conflicts ? ' En ' + conflicts + ' duplicados había datos distintos; se conservó el primero.' : ''));
        } catch (error) {
            console.error(error); showMessage(error.message || String(error), 'error');
        }
    }

    const excelColor = (hex) => ({ argb: 'FF' + hex });
    const font = (options = {}) => ({ name: 'Segoe UI', size: 10, color: excelColor(COLOR.charcoal), ...options });

    function sheetName(value, used) {
        const base = value.replace(/[:\\/?*\[\]]/g, '-').trim().replace(/^'+|'+$/g, '').slice(0, 31) || 'Clase';
        let name = base, index = 2;
        while (used.has(name.toLowerCase())) {
            const suffix = ' (' + index++ + ')'; name = base.slice(0, 31 - suffix.length) + suffix;
        }
        used.add(name.toLowerCase()); return name;
    }

    function drawSheet(workbook, imageId, name, title, subtitle, students, tabColor, schoolName) {
        const hasSchool = Boolean(schoolName);
        const lastRow = Math.max(6 + students.length, 6);
        const sheet = workbook.addWorksheet(name, {
            properties: { tabColor: excelColor(tabColor) }, views: [{ state: 'frozen', ySplit: 6, showGridLines: false }],
            pageSetup: { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0, horizontalCentered: true, margins: { left: .5, right: .5, top: .6, bottom: .7, header: .3, footer: .3 }, printArea: 'A1:I' + lastRow, printTitlesRow: '6:6' }
        });
        sheet.headerFooter = { oddFooter: '&LMacmillan Castillo  |  Solución integral&RPágina &P de &N' };
        const widths = [20, 20, 20, 20, 16, 14, 9, 9, 14].map((minimum, col) => {
            let length = HEAD[col].length;
            for (const student of students) length = Math.max(length, String(student[col]).length);
            return Math.min(45, Math.max(minimum, length * 1.1 + 4));
        });
        sheet.columns = widths.map((width) => ({ width }));
        [8, 30, 24, 4, 12, 28].forEach((height, index) => { sheet.getRow(index + 1).height = height; });
        let titleSize = 20, wrap = false;
        if (hasSchool) {
            const available = widths.slice(2).reduce((sum, width) => sum + width * 7 + 5, 0) * .75 - 24;
            for (titleSize = 22; titleSize >= 12; titleSize--) if (schoolName.length * .58 * titleSize <= available) break;
            if (titleSize < 12) { titleSize = 12; wrap = true; sheet.getRow(2).height = 40; }
        }
        sheet.mergeCells('C2:I2');
        Object.assign(sheet.getCell('C2'), { value: hasSchool ? schoolName : title, font: font({ size: titleSize, bold: true }), alignment: { horizontal: hasSchool ? 'center' : 'right', vertical: 'middle', indent: hasSchool ? 0 : 1, wrapText: wrap } });
        sheet.mergeCells('C3:I3');
        Object.assign(sheet.getCell('C3'), { value: hasSchool ? title + '   |   ' + subtitle : subtitle, font: font({ size: 11, color: excelColor(COLOR.red) }), alignment: { horizontal: hasSchool ? 'center' : 'right', vertical: 'middle', indent: hasSchool ? 0 : 1 } });
        for (let col = 1; col <= 9; col++) sheet.getCell(4, col).fill = { type: 'pattern', pattern: 'solid', fgColor: excelColor(COLOR.red) };
        const alignment = (col) => col < 4 ? { horizontal: 'left', vertical: 'middle', indent: 1 } : { horizontal: 'center', vertical: 'middle' };
        HEAD.forEach((heading, col) => {
            const cell = sheet.getCell(6, col + 1); cell.value = heading; cell.font = font({ bold: true, color: excelColor('FFFFFF') });
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: excelColor(COLOR.charcoal) }; cell.alignment = alignment(col);
        });
        students.forEach((student, index) => {
            const rowNumber = index + 7, isLast = index === students.length - 1; sheet.getRow(rowNumber).height = 22;
            student.forEach((value, col) => {
                const cell = sheet.getCell(rowNumber, col + 1); cell.value = value; cell.font = font(); cell.alignment = alignment(col);
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: excelColor(index % 2 ? COLOR.alternate : 'FFFFFF') };
                cell.border = { bottom: isLast ? { style: 'medium', color: excelColor(COLOR.red) } : { style: 'thin', color: excelColor(COLOR.line) } };
                if (col === 8 && value === 'Revisar') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: excelColor(COLOR.review) }; cell.font = font({ bold: true, color: excelColor(COLOR.red) }); }
            });
        });
        sheet.addImage(imageId, { tl: { col: .06, row: .5 }, ext: { width: 139, height: 72 }, editAs: 'oneCell' });
    }

    async function imageData(url) {
        const response = await fetch(url);
        if (!response.ok) throw new Error('No se pudo cargar el logotipo del formato.');
        const blob = await response.blob();
        return await new Promise((resolve, reject) => {
            const reader = new FileReader(); reader.onload = () => resolve(reader.result); reader.onerror = reject; reader.readAsDataURL(blob);
        });
    }

    async function createWorkbook(classes, students, schoolName) {
        const workbook = new ExcelJS.Workbook();
        const imageId = workbook.addImage({ base64: await imageData($('fe-intro').dataset.logoUrl), extension: 'png' });
        const used = new Set(['todos los alumnos']);
        drawSheet(workbook, imageId, 'Todos los alumnos', 'Concentrado general', total(students.length), students, COLOR.charcoal, schoolName);
        for (const [classroom, list] of classes) drawSheet(workbook, imageId, sheetName(classroom, used), 'Listado de alumnos', classroom + '   |   ' + total(list.length), list, COLOR.red, schoolName);
        return workbook.xlsx.writeBuffer();
    }

    const cleanFileName = (value) => value.replace(/[\\/:*?"<>|]/g, '-').replace(/\s+/g, ' ').trim();
    function download(output) {
        const url = URL.createObjectURL(new Blob([output.buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
        const anchor = document.createElement('a'); anchor.href = url; anchor.download = output.name; document.body.append(anchor); anchor.click(); anchor.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    fileInput.addEventListener('change', (event) => setFiles([...event.target.files]));
    ['dragenter', 'dragover'].forEach((name) => dropZone.addEventListener(name, (event) => { event.preventDefault(); dropZone.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach((name) => dropZone.addEventListener(name, (event) => { event.preventDefault(); dropZone.classList.remove('is-over'); }));
    dropZone.addEventListener('drop', (event) => setFiles([...event.dataTransfer.files]));

    $('fe-download-all').addEventListener('click', async () => {
        for (const output of outputs) { download(output); await new Promise((resolve) => setTimeout(resolve, 700)); }
    });

    $('fe-generate').addEventListener('click', async () => {
        $('fe-generate').disabled = true; $('fe-results').replaceChildren(); $('fe-output').hidden = true; outputs = [];
        showMessage('Generando archivos…');
        try {
            let needsReview = 0;
            for (const [id, school] of groups) {
                const schoolName = school.input.value.trim();
                try { localStorage.setItem('colegioSI_' + id, schoolName); } catch {}
                const buffer = await createWorkbook(school.classes, school.students, schoolName);
                const review = school.students.filter((student) => student[8] === 'Revisar').length;
                outputs.push({ id, buffer, review, count: school.students.length, classes: school.classes.size, duplicates: school.duplicates, name: cleanFileName('Cuentas de Alumno ' + (schoolName ? schoolName + ' ' : '') + '- ' + id) + '.xlsx' });
                needsReview += review;
            }
            for (const output of outputs) {
                const item = document.createElement('div'); item.className = 'fe-result';
                const icon = document.createElement('div'); icon.className = 'fe-file-icon'; icon.textContent = 'XLSX';
                const info = document.createElement('div'); info.className = 'fe-result-info';
                const name = document.createElement('div'); name.className = 'fe-result-name'; name.textContent = output.name;
                const meta = document.createElement('div'); meta.className = 'fe-result-meta';
                meta.textContent = 'ID ' + output.id + ' · ' + total(output.count) + ' · ' + output.classes + (output.classes === 1 ? ' hoja de clase' : ' hojas de clase') + ' · ' + Math.max(1, Math.round(output.buffer.byteLength / 1024)) + ' KB' + (output.duplicates ? ' · ' + output.duplicates + ' repetidos omitidos' : '');
                const status = document.createElement('span'); status.className = 'fe-result-status' + (output.review ? ' needs-review' : '');
                status.textContent = output.review ? output.review + (output.review === 1 ? ' alumno por revisar' : ' alumnos por revisar') : 'Sin alumnos por revisar';
                info.append(name, meta, status);
                const button = document.createElement('button'); button.type = 'button'; button.className = 'fe-btn'; button.textContent = 'Descargar'; button.addEventListener('click', () => download(output));
                item.append(icon, info, button); $('fe-results').append(item);
            }
            $('fe-output').hidden = false; $('fe-download-all').hidden = outputs.length < 2;
            $('fe-download-all').textContent = 'Descargar todos (' + outputs.length + ')';
            $('fe-output').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            showMessage('Listo: ' + outputs.length + (outputs.length === 1 ? ' archivo generado.' : ' archivos generados.') + (needsReview ? ' Hay ' + needsReview + ' alumnos por revisar en la columna Género.' : ''), 'success');
        } catch (error) {
            console.error(error); showMessage(error.message || String(error), 'error');
        }
        $('fe-generate').disabled = false;
    });
})();
</script>
@endsection