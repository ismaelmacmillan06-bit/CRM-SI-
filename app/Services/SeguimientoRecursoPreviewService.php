<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use ZipArchive;

class SeguimientoRecursoPreviewService
{
    private const MAX_SHEET_TABS = 10;
    private const MAX_SHEET_ROWS = 100;
    private const MAX_SHEET_COLS = 25;

    private const MAX_XML_BYTES = 5_242_880;        // 5 MB por entrada XML
    private const MAX_LEGACY_BYTES = 4_194_304;     // 4 MB para binarios heredados (.doc/.ppt)
    private const MAX_DOC_BLOCKS = 400;
    private const MAX_TABLE_ROWS = 100;
    private const MAX_TABLE_COLS = 20;

    private const MAX_SLIDES = 40;
    private const MAX_ARCHIVE_ENTRIES = 250;

    private const MAX_IMAGES = 12;
    private const MAX_IMAGE_BYTES = 614_400;        // 600 KB por imagen
    private const MAX_TOTAL_IMAGE_BYTES = 3_145_728; // 3 MB total embebido

    private const SPREADSHEET_READER_MAP = [
        'xlsx' => 'Xlsx',
        'xls'  => 'Xls',
        'csv'  => 'Csv',
        'ods'  => 'Ods',
    ];

    /**
     * Genera la vista previa de hojas de cálculo (XLSX, XLS, CSV, ODS), soportando múltiples hojas.
     */
    public function previewSpreadsheet(string $fullPath, string $extension): array
    {
        $spreadsheet = null;

        try {
            $reader = $this->createSpreadsheetReader($fullPath, $extension);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($fullPath);

            $sheetCount = $spreadsheet->getSheetCount();
            $activeIndex = $sheetCount > 0 ? min($spreadsheet->getActiveSheetIndex(), self::MAX_SHEET_TABS - 1) : 0;
            $limit = min($sheetCount, self::MAX_SHEET_TABS);

            $sheets = [];
            for ($idx = 0; $idx < $limit; $idx++) {
                $sheet = $spreadsheet->getSheet($idx);
                $rawTotalRows = (int) $sheet->getHighestRow();
                $highestRow = min($rawTotalRows, self::MAX_SHEET_ROWS);
                $highestColIndex = min(
                    Coordinate::columnIndexFromString($sheet->getHighestColumn()),
                    self::MAX_SHEET_COLS
                );

                $rows = [];
                $maxNonEmptyCol = 0;

                for ($r = 1; $r <= $highestRow; $r++) {
                    $row = [];
                    for ($c = 1; $c <= $highestColIndex; $c++) {
                        $colLetter = Coordinate::stringFromColumnIndex($c);
                        $val = $sheet->getCell($colLetter . $r)->getFormattedValue();
                        $text = $this->ensureUtf8(is_scalar($val) ? (string) $val : '');
                        if ($text !== '' && $c > $maxNonEmptyCol) {
                            $maxNonEmptyCol = $c;
                        }
                        $row[] = $text;
                    }
                    $rows[] = $row;
                }

                // Recortar columnas vacías al final preservando al menos 1 si hubo datos o filas
                if ($maxNonEmptyCol > 0 && $maxNonEmptyCol < $highestColIndex) {
                    $rows = array_map(static fn (array $row) => array_slice($row, 0, $maxNonEmptyCol), $rows);
                }

                $sheets[] = [
                    'title'         => $this->ensureUtf8((string) $sheet->getTitle()),
                    'totalRows'     => $rawTotalRows,
                    'rows'          => $rows,
                    'truncatedRows' => $rawTotalRows > self::MAX_SHEET_ROWS,
                ];
            }

            $activeSheet = $sheets[$activeIndex] ?? ($sheets[0] ?? [
                'title'         => 'Principal',
                'totalRows'     => 0,
                'rows'          => [],
                'truncatedRows' => false,
            ]);

            return [
                'ok'            => true,
                'sheetTitle'    => $activeSheet['title'],
                'totalRows'     => $activeSheet['totalRows'],
                'rows'          => $activeSheet['rows'],
                'activeIndex'   => $activeIndex,
                'totalSheets'   => $sheetCount,
                'sheets'        => $sheets,
                'truncatedTabs' => $sheetCount > self::MAX_SHEET_TABS,
            ];
        } catch (\Throwable) {
            return [
                'ok'      => false,
                'message' => 'No se pudo generar la vista previa de esta hoja de cálculo. Puedes descargarlo directamente.',
            ];
        } finally {
            if ($spreadsheet !== null) {
                $spreadsheet->disconnectWorksheets();
            }
        }
    }

    /**
     * Genera la vista previa estructurada de documentos Word / OpenDocument (DOCX, ODT, DOC).
     */
    public function previewDocument(string $fullPath, string $extension): array
    {
        $ext = strtolower($extension);

        try {
            $result = match ($ext) {
                'docx'  => $this->parseDocx($fullPath),
                'odt'   => $this->parseOdt($fullPath),
                'doc'   => $this->parseLegacyDoc($fullPath),
                default => ['ok' => false, 'message' => 'Formato de documento no soportado para vista previa.'],
            };
        } catch (\Throwable) {
            return [
                'ok'      => false,
                'message' => 'No se pudo generar la vista previa de este documento. Puedes descargarlo directamente.',
            ];
        }

        if (($result['ok'] ?? false) && empty($result['blocks'])) {
            return [
                'ok'      => false,
                'message' => 'El documento no contiene texto ni tablas visibles para previsualizar. Descárgalo para revisarlo completo.',
            ];
        }

        return $result;
    }

    /**
     * Genera la vista previa por diapositivas de presentaciones (PPTX, ODP, PPT).
     */
    public function previewPresentation(string $fullPath, string $extension): array
    {
        $ext = strtolower($extension);

        try {
            $result = match ($ext) {
                'pptx'  => $this->parsePptx($fullPath),
                'odp'   => $this->parseOdp($fullPath),
                'ppt'   => $this->parseLegacyPpt($fullPath),
                default => ['ok' => false, 'message' => 'Formato de presentación no soportado para vista previa.'],
            };
        } catch (\Throwable) {
            return [
                'ok'      => false,
                'message' => 'No se pudo generar la vista previa de esta presentación. Puedes descargarlo directamente.',
            ];
        }

        if (($result['ok'] ?? false) && empty($result['slides'])) {
            return [
                'ok'      => false,
                'message' => 'La presentación no contiene diapositivas legibles para previsualizar. Descárgala para abrirla completa.',
            ];
        }

        return $result;
    }

    /**
     * Lista el contenido de archivos ZIP o informa amablemente en RAR/7Z.
     */
    public function previewArchive(string $fullPath, string $extension): array
    {
        $ext = strtolower($extension);

        if ($ext !== 'zip') {
            return [
                'ok'                 => true,
                'format'             => strtoupper($ext),
                'unsupportedListing' => true,
                'message'            => "Los archivos comprimidos en formato .{$ext} no permiten listar su contenido desde el navegador. Descárgalo para abrirlo en tu equipo.",
            ];
        }

        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== true) {
            return [
                'ok'      => false,
                'message' => 'No se pudo abrir el archivo ZIP para previsualizar su contenido.',
            ];
        }

        try {
            $totalEntries = (int) $zip->numFiles;
            $limit = min($totalEntries, self::MAX_ARCHIVE_ENTRIES);
            $entries = [];
            $totalFiles = 0;
            $totalSize = 0;

            $statLimit = min($totalEntries, 10000);
            for ($i = 0; $i < $statLimit; $i++) {
                $stat = $zip->statIndex($i);
                if (!is_array($stat)) {
                    continue;
                }

                $name = $this->ensureUtf8((string) ($stat['name'] ?? ''), 'CP850,Windows-1252');
                $isDir = str_ends_with($name, '/');
                $size = (int) ($stat['size'] ?? 0);

                if (!$isDir) {
                    $totalFiles++;
                    $totalSize += $size;
                }

                if ($i < $limit) {
                    $entryExt = $isDir ? '' : strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
                    $entries[] = [
                        'name'          => $name,
                        'isDir'         => $isDir,
                        'size'          => $size,
                        'sizeFormatted' => $isDir ? 'Carpeta' : $this->formatBytes($size),
                        'ext'           => $entryExt ? strtoupper($entryExt) : ($isDir ? 'DIR' : 'ARCHIVO'),
                    ];
                }
            }

            return [
                'ok'                 => true,
                'format'             => 'ZIP',
                'totalEntries'       => $totalEntries,
                'totalFiles'         => $totalFiles,
                'totalSizeFormatted' => $this->formatBytes($totalSize),
                'truncated'          => $totalEntries > self::MAX_ARCHIVE_ENTRIES,
                'entries'            => $entries,
            ];
        } finally {
            $zip->close();
        }
    }

    private function createSpreadsheetReader(string $fullPath, string $extension)
    {
        $ext = strtolower($extension);

        if ($ext === 'csv') {
            /** @var Csv $reader */
            $reader = IOFactory::createReader('Csv');
            $reader->setInputEncoding(Csv::GUESS_ENCODING);
            $reader->setFallbackEncoding('CP1252');

            return $reader;
        }

        if (isset(self::SPREADSHEET_READER_MAP[$ext])) {
            return IOFactory::createReaderForFile($fullPath, ['Xlsx', 'Xls', 'Ods']);
        }

        return IOFactory::createReaderForFile($fullPath);
    }

    /* ─────────────────────── WORD / ODT / DOC ─────────────────────── */

    private function parseDocx(string $fullPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== true) {
            return ['ok' => false, 'message' => 'El archivo .docx no es un documento válido o está dañado.'];
        }

        try {
            $xmlStr = $this->readZipEntryLimited($zip, 'word/document.xml', self::MAX_XML_BYTES);
            if ($xmlStr === null) {
                return ['ok' => false, 'message' => 'No se pudo leer el contenido de este documento Word.'];
            }

            $dom = $this->loadXml($xmlStr);
            if ($dom === null || $dom->documentElement === null) {
                return ['ok' => false, 'message' => 'No se pudo interpretar el contenido XML del documento.'];
            }

            $rels = $this->readRelsMap($zip, 'word/_rels/document.xml.rels');
            $body = $this->findFirstDescendantByLocalName($dom->documentElement, 'body');
            if ($body === null) {
                return ['ok' => false, 'message' => 'El documento Word no contiene cuerpo legible.'];
            }

            $blocks = [];
            $truncated = false;
            $imageCount = 0;
            $imageBytes = 0;

            $this->walkDocxContainer(
                $body,
                $zip,
                $rels,
                $blocks,
                $truncated,
                $imageCount,
                $imageBytes
            );

            return [
                'ok'        => true,
                'format'    => 'DOCX',
                'blocks'    => $blocks,
                'truncated' => $truncated,
            ];
        } finally {
            $zip->close();
        }
    }

    private function walkDocxContainer(
        \DOMNode $container,
        ZipArchive $zip,
        array $rels,
        array &$blocks,
        bool &$truncated,
        int &$imageCount,
        int &$imageBytes
    ): void {
        foreach ($container->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            if (count($blocks) >= self::MAX_DOC_BLOCKS) {
                $truncated = true;
                return;
            }

            $name = $child->localName;
            if (in_array($name, ['Fallback', 'del', 'sectPr', 'pPr', 'tblPr', 'tblGrid'], true)) {
                continue;
            }

            if ($name === 'sdt') {
                $content = $this->findFirstDirectChildByLocalName($child, 'sdtContent');
                if ($content !== null) {
                    $this->walkDocxContainer($content, $zip, $rels, $blocks, $truncated, $imageCount, $imageBytes);
                }
                continue;
            }

            if ($name === 'AlternateContent') {
                $choice = $this->findFirstDirectChildByLocalName($child, 'Choice');
                if ($choice !== null) {
                    $this->walkDocxContainer($choice, $zip, $rels, $blocks, $truncated, $imageCount, $imageBytes);
                }
                continue;
            }

            if ($name === 'p') {
                $this->extractDocxParagraph($child, $zip, $rels, $blocks, $truncated, $imageCount, $imageBytes);
                continue;
            }

            if ($name === 'tbl') {
                $table = $this->extractDocxTable($child);
                if ($table !== null) {
                    $blocks[] = $table;
                }
            }
        }
    }

    private function extractDocxParagraph(
        \DOMElement $p,
        ZipArchive $zip,
        array $rels,
        array &$blocks,
        bool &$truncated,
        int &$imageCount,
        int &$imageBytes
    ): void {
        $pPr = $this->findFirstDirectChildByLocalName($p, 'pPr');
        $headingLevel = $this->detectDocxHeadingLevel($pPr);
        $isList = $headingLevel === null && $pPr !== null && $this->findFirstDirectChildByLocalName($pPr, 'numPr') !== null;

        $runs = [];
        $images = [];
        $this->collectDocxRuns($p, $zip, $rels, $runs, $images, $imageCount, $imageBytes, false, false);

        $runs = $this->mergeStyledRuns($runs);
        $plainText = trim($this->runsToText($runs));

        if ($plainText !== '') {
            if ($headingLevel !== null) {
                $blocks[] = [
                    'type'  => 'heading',
                    'level' => $headingLevel,
                    'text'  => $plainText,
                    'runs'  => $runs,
                ];
            } elseif ($isList) {
                $blocks[] = [
                    'type' => 'list_item',
                    'text' => $plainText,
                    'runs' => $runs,
                ];
            } else {
                $blocks[] = [
                    'type' => 'paragraph',
                    'text' => $plainText,
                    'runs' => $runs,
                ];
            }
        }

        foreach ($images as $src) {
            if (count($blocks) >= self::MAX_DOC_BLOCKS) {
                $truncated = true;
                break;
            }
            $blocks[] = [
                'type' => 'image',
                'src'  => $src,
            ];
        }
    }

    private function detectDocxHeadingLevel(?\DOMElement $pPr): ?int
    {
        if ($pPr === null) {
            return null;
        }

        $pStyle = $this->findFirstDirectChildByLocalName($pPr, 'pStyle');
        if ($pStyle !== null) {
            $val = strtolower(trim($this->getAttributeByLocalName($pStyle, 'val')));
            if ($val !== '') {
                if (in_array($val, ['title', 'titulo', 'heading1', 'titulo1', 'ttulo1'], true)) {
                    return 1;
                }
                if (in_array($val, ['subtitle', 'subtitulo', 'subttulo', 'heading2', 'titulo2', 'ttulo2'], true)) {
                    return 2;
                }
                if (preg_match('/^(?:heading|t[ií]?tulo)\s*([1-6])$/u', $val, $m)) {
                    return min(3, max(1, (int) $m[1]));
                }
            }
        }

        $outline = $this->findFirstDirectChildByLocalName($pPr, 'outlineLvl');
        if ($outline !== null) {
            $lvl = $this->getAttributeByLocalName($outline, 'val');
            if ($lvl !== '' && ctype_digit($lvl)) {
                return min(3, ((int) $lvl) + 1);
            }
        }

        return null;
    }

    private function collectDocxRuns(
        \DOMNode $node,
        ZipArchive $zip,
        array $rels,
        array &$runs,
        array &$images,
        int &$imageCount,
        int &$imageBytes,
        bool $bold,
        bool $italic
    ): void {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            $name = $child->localName;
            if (in_array($name, ['pPr', 'rPr', 'Fallback', 'del', 'delText', 'instrText', 'fldData', 'binData'], true)) {
                continue;
            }

            if ($name === 'AlternateContent') {
                $choice = $this->findFirstDirectChildByLocalName($child, 'Choice');
                if ($choice !== null) {
                    $this->collectDocxRuns($choice, $zip, $rels, $runs, $images, $imageCount, $imageBytes, $bold, $italic);
                }
                continue;
            }

            if ($name === 'r') {
                $rPr = $this->findFirstDirectChildByLocalName($child, 'rPr');
                [$rBold, $rItalic] = $this->detectDocxRunStyle($rPr, $bold, $italic);
                $this->collectDocxRuns($child, $zip, $rels, $runs, $images, $imageCount, $imageBytes, $rBold, $rItalic);
                continue;
            }

            if ($name === 't') {
                $txt = $child->textContent;
                if ($txt !== '') {
                    $runs[] = ['text' => $txt, 'bold' => $bold, 'italic' => $italic];
                }
                continue;
            }

            if ($name === 'tab') {
                $runs[] = ['text' => '    ', 'bold' => $bold, 'italic' => $italic];
                continue;
            }

            if ($name === 'br' || $name === 'cr') {
                $runs[] = ['text' => "\n", 'bold' => false, 'italic' => false];
                continue;
            }

            if ($name === 'blip') {
                $rId = $this->getAttributeByLocalName($child, 'embed');
                if ($rId !== '' && isset($rels[$rId])) {
                    $entryPath = $this->resolveZipTarget('word/', $rels[$rId]);
                    $dataUri = $this->extractSafeRasterDataUri($zip, $entryPath, $imageCount, $imageBytes);
                    if ($dataUri !== null) {
                        $images[] = $dataUri;
                    }
                }
                continue;
            }

            if ($name === 'p' && !empty($runs)) {
                $runs[] = ['text' => "\n", 'bold' => false, 'italic' => false];
            }

            $this->collectDocxRuns($child, $zip, $rels, $runs, $images, $imageCount, $imageBytes, $bold, $italic);
        }
    }

    private function detectDocxRunStyle(?\DOMElement $rPr, bool $defaultBold, bool $defaultItalic): array
    {
        if ($rPr === null) {
            return [$defaultBold, $defaultItalic];
        }

        $bEl = $this->findFirstDirectChildByLocalName($rPr, 'b');
        $iEl = $this->findFirstDirectChildByLocalName($rPr, 'i');

        $bold = $defaultBold;
        if ($bEl !== null) {
            $val = strtolower($this->getAttributeByLocalName($bEl, 'val'));
            $bold = !in_array($val, ['0', 'false', 'off'], true);
        }

        $italic = $defaultItalic;
        if ($iEl !== null) {
            $val = strtolower($this->getAttributeByLocalName($iEl, 'val'));
            $italic = !in_array($val, ['0', 'false', 'off'], true);
        }

        return [$bold, $italic];
    }

    private function extractDocxTable(\DOMElement $tbl): ?array
    {
        $rows = [];
        foreach ($tbl->childNodes as $tr) {
            if (!$tr instanceof \DOMElement || $tr->localName !== 'tr') {
                continue;
            }
            if (count($rows) >= self::MAX_TABLE_ROWS) {
                break;
            }

            $cells = [];
            foreach ($tr->childNodes as $tc) {
                if (!$tc instanceof \DOMElement || $tc->localName !== 'tc') {
                    continue;
                }
                if (count($cells) >= self::MAX_TABLE_COLS) {
                    break;
                }

                $cellParagraphs = [];
                foreach ($tc->childNodes as $child) {
                    if (!$child instanceof \DOMElement) {
                        continue;
                    }
                    if ($child->localName === 'p') {
                        $txt = trim($this->collectPlainTextInParagraph($child));
                        if ($txt !== '') {
                            $cellParagraphs[] = $txt;
                        }
                    }
                }
                $cells[] = implode("\n", $cellParagraphs);
            }

            if (!empty($cells)) {
                $rows[] = $cells;
            }
        }

        return empty($rows) ? null : [
            'type' => 'table',
            'rows' => $rows,
        ];
    }

    private function collectPlainTextInParagraph(\DOMElement $p): string
    {
        $runs = [];
        $this->collectDocxPlainRuns($p, $runs);

        return $this->runsToText($runs);
    }

    private function collectDocxPlainRuns(\DOMNode $node, array &$runs): void
    {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $name = $child->localName;
            if (in_array($name, ['pPr', 'rPr', 'Fallback', 'del', 'delText', 'instrText', 'fldData', 'binData'], true)) {
                continue;
            }
            if ($name === 'AlternateContent') {
                $choice = $this->findFirstDirectChildByLocalName($child, 'Choice');
                if ($choice !== null) {
                    $this->collectDocxPlainRuns($choice, $runs);
                }
                continue;
            }
            if ($name === 't') {
                $runs[] = ['text' => $child->textContent, 'bold' => false, 'italic' => false];
                continue;
            }
            if ($name === 'tab') {
                $runs[] = ['text' => ' ', 'bold' => false, 'italic' => false];
                continue;
            }
            if ($name === 'br' || $name === 'cr') {
                $runs[] = ['text' => "\n", 'bold' => false, 'italic' => false];
                continue;
            }
            $this->collectDocxPlainRuns($child, $runs);
        }
    }

    private function parseOdt(string $fullPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== true) {
            return ['ok' => false, 'message' => 'El archivo .odt no es un documento válido o está dañado.'];
        }

        try {
            $xmlStr = $this->readZipEntryLimited($zip, 'content.xml', self::MAX_XML_BYTES);
            if ($xmlStr === null) {
                return ['ok' => false, 'message' => 'No se pudo leer el contenido de este archivo OpenDocument.'];
            }

            $dom = $this->loadXml($xmlStr);
            if ($dom === null || $dom->documentElement === null) {
                return ['ok' => false, 'message' => 'No se pudo interpretar el contenido XML del documento ODT.'];
            }

            $styleMap = $this->buildOdfStyleMap($dom->documentElement);
            $textBody = $this->findFirstDescendantByLocalName($dom->documentElement, 'text');
            if ($textBody === null) {
                return ['ok' => false, 'message' => 'El documento ODT no contiene cuerpo legible.'];
            }

            $blocks = [];
            $truncated = false;
            $imageCount = 0;
            $imageBytes = 0;

            $this->walkOdtNodes($textBody, $zip, $styleMap, $blocks, $truncated, $imageCount, $imageBytes, false);

            return [
                'ok'        => true,
                'format'    => 'ODT',
                'blocks'    => $blocks,
                'truncated' => $truncated,
            ];
        } finally {
            $zip->close();
        }
    }

    private function walkOdtNodes(
        \DOMNode $parent,
        ZipArchive $zip,
        array $styleMap,
        array &$blocks,
        bool &$truncated,
        int &$imageCount,
        int &$imageBytes,
        bool $insideList
    ): void {
        foreach ($parent->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            if (count($blocks) >= self::MAX_DOC_BLOCKS) {
                $truncated = true;
                return;
            }

            $name = $child->localName;
            if ($name === 'section' || $name === 'list-header') {
                $this->walkOdtNodes($child, $zip, $styleMap, $blocks, $truncated, $imageCount, $imageBytes, $insideList);
                continue;
            }

            if ($name === 'list') {
                foreach ($child->childNodes as $li) {
                    if ($li instanceof \DOMElement && ($li->localName === 'list-item' || $li->localName === 'list-header')) {
                        $this->walkOdtNodes($li, $zip, $styleMap, $blocks, $truncated, $imageCount, $imageBytes, true);
                    }
                }
                continue;
            }

            if ($name === 'h' || $name === 'p') {
                $styleName = $this->getAttributeByLocalName($child, 'style-name');
                $baseStyle = $styleMap[$styleName] ?? ['bold' => false, 'italic' => false];
                $runs = [];
                $images = [];
                $this->collectOdfRuns(
                    $child,
                    $zip,
                    $styleMap,
                    $runs,
                    $images,
                    $imageCount,
                    $imageBytes,
                    $baseStyle['bold'],
                    $baseStyle['italic']
                );
                $runs = $this->mergeStyledRuns($runs);
                $plain = trim($this->runsToText($runs));

                if ($plain !== '') {
                    if ($name === 'h') {
                        $lvlRaw = $this->getAttributeByLocalName($child, 'outline-level');
                        $lvl = ($lvlRaw !== '' && ctype_digit($lvlRaw)) ? min(3, max(1, (int) $lvlRaw)) : 1;
                        $blocks[] = [
                            'type'  => 'heading',
                            'level' => $lvl,
                            'text'  => $plain,
                            'runs'  => $runs,
                        ];
                    } elseif ($insideList) {
                        $blocks[] = [
                            'type' => 'list_item',
                            'text' => $plain,
                            'runs' => $runs,
                        ];
                    } else {
                        $blocks[] = [
                            'type' => 'paragraph',
                            'text' => $plain,
                            'runs' => $runs,
                        ];
                    }
                }

                foreach ($images as $src) {
                    if (count($blocks) >= self::MAX_DOC_BLOCKS) {
                        $truncated = true;
                        break;
                    }
                    $blocks[] = ['type' => 'image', 'src' => $src];
                }
                continue;
            }

            if ($name === 'table') {
                $table = $this->extractOdfTable($child);
                if ($table !== null) {
                    $blocks[] = $table;
                }
            }
        }
    }

    private function parseLegacyDoc(string $fullPath): array
    {
        $head = @file_get_contents($fullPath, false, null, 0, 16) ?: '';

        // Un .docx o .rtf guardado con extensión .doc
        if (str_starts_with($head, "PK\x03\x04")) {
            return $this->parseDocx($fullPath);
        }
        if (str_starts_with(ltrim($head), '{\rtf')) {
            return $this->parseRtf($fullPath);
        }

        $raw = $this->readFileLimited($fullPath, self::MAX_LEGACY_BYTES);
        if ($raw === '') {
            return ['ok' => false, 'message' => 'El archivo .doc está vacío o no se pudo leer.'];
        }

        $paragraphs = $this->extractReadableBinaryText($raw);
        if (empty($paragraphs)) {
            return [
                'ok'      => false,
                'message' => 'No se pudo extraer texto legible de este documento .doc binario. Descárgalo para abrirlo en Word.',
            ];
        }

        $blocks = [];
        foreach (array_slice($paragraphs, 0, 300) as $para) {
            $blocks[] = [
                'type' => 'paragraph',
                'text' => $para,
                'runs' => [['text' => $para, 'bold' => false, 'italic' => false]],
            ];
        }

        return [
            'ok'        => true,
            'format'    => 'DOC',
            'notice'    => 'Vista previa simplificada de documento Word heredado (.doc). Descárgalo para ver su formato original completo.',
            'blocks'    => $blocks,
            'truncated' => count($paragraphs) > 300,
        ];
    }

    private function parseRtf(string $fullPath): array
    {
        $raw = $this->readFileLimited($fullPath, self::MAX_LEGACY_BYTES);
        $text = $this->rtfToPlainText($raw);
        $lines = preg_split("/\r\n|\r|\n/", $text) ?: [];

        $blocks = [];
        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/[ \t]{2,}/', ' ', $line));
            if ($line === '') {
                continue;
            }
            $blocks[] = [
                'type' => 'paragraph',
                'text' => $line,
                'runs' => [['text' => $line, 'bold' => false, 'italic' => false]],
            ];
            if (count($blocks) >= self::MAX_DOC_BLOCKS) {
                break;
            }
        }

        return [
            'ok'        => true,
            'format'    => 'DOC (RTF)',
            'notice'    => 'Vista previa de texto extraído del documento.',
            'blocks'    => $blocks,
            'truncated' => count($blocks) >= self::MAX_DOC_BLOCKS,
        ];
    }

    private function rtfToPlainText(string $rtf): string
    {
        $skipDestinations = [
            'fonttbl', 'colortbl', 'stylesheet', 'info', 'pict', 'object',
            'header', 'footer', 'headerl', 'headerr', 'headerf',
            'footerl', 'footerr', 'footerf', 'footnote', 'fldinst', 'datastore', 'themedata', 'colorschememapping',
        ];

        $len = strlen($rtf);
        $stack = [];
        $skip = false;
        $uc = 1;
        $out = '';
        $i = 0;

        while ($i < $len) {
            $ch = $rtf[$i];

            if ($ch === '{') {
                $stack[] = [$skip, $uc];
                if ($i + 2 < $len && $rtf[$i + 1] === '\\' && $rtf[$i + 2] === '*') {
                    $skip = true;
                    $i += 3;
                    continue;
                }
                $i++;
                continue;
            }

            if ($ch === '}') {
                if (!empty($stack)) {
                    [$skip, $uc] = array_pop($stack);
                }
                $i++;
                continue;
            }

            if ($ch === "\r" || $ch === "\n") {
                $i++;
                continue;
            }

            if ($ch === '\\') {
                $i++;
                if ($i >= $len) {
                    break;
                }
                $next = $rtf[$i];

                if ($next === '\\' || $next === '{' || $next === '}') {
                    if (!$skip) {
                        $out .= $next;
                    }
                    $i++;
                    continue;
                }

                if ($next === '\'') {
                    $hex = substr($rtf, $i + 1, 2);
                    if (ctype_xdigit($hex) && strlen($hex) === 2) {
                        if (!$skip) {
                            $byte = chr((int) hexdec($hex));
                            $out .= mb_convert_encoding($byte, 'UTF-8', 'Windows-1252');
                        }
                        $i += 3;
                        continue;
                    }
                }

                if ($next === '~') {
                    if (!$skip) {
                        $out .= ' ';
                    }
                    $i++;
                    continue;
                }

                if ($next === '-' || $next === '_') {
                    if (!$skip && $next === '_') {
                        $out .= '-';
                    }
                    $i++;
                    continue;
                }

                // Leer palabra de control
                $wordStart = $i;
                while ($i < $len && ctype_alpha($rtf[$i])) {
                    $i++;
                }
                $word = substr($rtf, $wordStart, $i - $wordStart);

                if ($word === '') {
                    $i++;
                    continue;
                }

                $param = '';
                if ($i < $len && ($rtf[$i] === '-' || ctype_digit($rtf[$i]))) {
                    $pStart = $i;
                    if ($rtf[$i] === '-') {
                        $i++;
                    }
                    while ($i < $len && ctype_digit($rtf[$i])) {
                        $i++;
                    }
                    $param = substr($rtf, $pStart, $i - $pStart);
                }

                if ($i < $len && $rtf[$i] === ' ') {
                    $i++;
                }

                if (in_array($word, $skipDestinations, true)) {
                    $skip = true;
                    continue;
                }

                if ($word === 'uc' && $param !== '') {
                    $uc = max(0, (int) $param);
                    continue;
                }

                if ($skip) {
                    continue;
                }

                if ($word === 'par' || $word === 'line' || $word === 'row') {
                    $out .= "\n";
                } elseif ($word === 'tab' || $word === 'cell') {
                    $out .= ($word === 'cell') ? "\t| " : ' ';
                } elseif ($word === 'u' && $param !== '') {
                    $code = (int) $param;
                    if ($code < 0) {
                        $code += 65536;
                    }
                    if ($code > 0) {
                        $out .= mb_chr($code, 'UTF-8');
                    }
                    // Saltar caracteres sustitutos ($uc)
                    $skipped = 0;
                    while ($skipped < $uc && $i < $len && $rtf[$i] !== '{' && $rtf[$i] !== '}') {
                        if ($rtf[$i] === '\\' && $i + 3 < $len && $rtf[$i + 1] === '\'') {
                            $i += 4;
                        } else {
                            $i++;
                        }
                        $skipped++;
                    }
                }
                continue;
            }

            if (!$skip) {
                $out .= $ch;
            }
            $i++;
        }

        return $out;
    }

    /* ─────────────────────── PPTX / ODP / PPT ─────────────────────── */

    private function parsePptx(string $fullPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== true) {
            return ['ok' => false, 'message' => 'El archivo .pptx no es una presentación válida o está dañada.'];
        }

        try {
            $slideEntries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (is_string($name) && preg_match('#^ppt/slides/slide(\d+)\.xml$#i', $name, $m)) {
                    $slideEntries[(int) $m[1]] = $name;
                }
            }

            if (empty($slideEntries)) {
                return ['ok' => false, 'message' => 'No se encontraron diapositivas en esta presentación.'];
            }

            ksort($slideEntries, SORT_NUMERIC);
            $totalSlides = count($slideEntries);
            $slice = array_slice($slideEntries, 0, self::MAX_SLIDES, true);

            $slides = [];
            $imageCount = 0;
            $imageBytes = 0;
            $displayNumber = 1;

            foreach ($slice as $slideFileNum => $entryName) {
                $xmlStr = $this->readZipEntryLimited($zip, $entryName, self::MAX_XML_BYTES);
                if ($xmlStr === null) {
                    continue;
                }

                $dom = $this->loadXml($xmlStr);
                if ($dom === null || $dom->documentElement === null) {
                    continue;
                }

                $relsPath = "ppt/slides/_rels/slide{$slideFileNum}.xml.rels";
                $rels = $this->readRelsMap($zip, $relsPath);

                $title = '';
                $items = [];
                $tables = [];
                $images = [];

                $spTree = $this->findFirstDescendantByLocalName($dom->documentElement, 'spTree');
                $rootNode = $spTree ?? $dom->documentElement;

                $this->walkPptxShapeTree(
                    $rootNode,
                    $zip,
                    $rels,
                    $title,
                    $items,
                    $tables,
                    $images,
                    $imageCount,
                    $imageBytes
                );

                $slides[] = [
                    'number' => $displayNumber++,
                    'title'  => $title,
                    'items'  => $items,
                    'tables' => $tables,
                    'images' => $images,
                ];
            }

            return [
                'ok'          => true,
                'format'      => 'PPTX',
                'totalSlides' => $totalSlides,
                'truncated'   => $totalSlides > self::MAX_SLIDES,
                'slides'      => $slides,
            ];
        } finally {
            $zip->close();
        }
    }

    private function walkPptxShapeTree(
        \DOMNode $container,
        ZipArchive $zip,
        array $rels,
        string &$title,
        array &$items,
        array &$tables,
        array &$images,
        int &$imageCount,
        int &$imageBytes
    ): void {
        foreach ($container->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $name = $child->localName;
            if ($name === 'Fallback') {
                continue;
            }
            if ($name === 'AlternateContent') {
                $choice = $this->findFirstDirectChildByLocalName($child, 'Choice');
                if ($choice !== null) {
                    $this->walkPptxShapeTree($choice, $zip, $rels, $title, $items, $tables, $images, $imageCount, $imageBytes);
                }
                continue;
            }
            if ($name === 'grpSp') {
                $this->walkPptxShapeTree($child, $zip, $rels, $title, $items, $tables, $images, $imageCount, $imageBytes);
                continue;
            }

            if ($name === 'sp') {
                $phType = $this->detectPptxPlaceholderType($child);
                if ($phType === 'sldnum') {
                    continue;
                }

                $txBody = $this->findFirstDirectChildByLocalName($child, 'txBody');
                if ($txBody === null) {
                    continue;
                }

                $shapeLines = [];
                foreach ($txBody->childNodes as $p) {
                    if (!$p instanceof \DOMElement || $p->localName !== 'p') {
                        continue;
                    }
                    $pPr = $this->findFirstDirectChildByLocalName($p, 'pPr');
                    $lvlRaw = $pPr ? $this->getAttributeByLocalName($pPr, 'lvl') : '';
                    $lvl = ($lvlRaw !== '' && ctype_digit($lvlRaw)) ? min(4, (int) $lvlRaw) : 0;

                    $runs = [];
                    $this->collectDrawingTextRuns($p, $runs);
                    $line = trim($this->runsToText($runs));
                    if ($line !== '') {
                        $shapeLines[] = ['text' => $line, 'level' => $lvl];
                    }
                }

                if (empty($shapeLines)) {
                    continue;
                }

                if (in_array($phType, ['title', 'ctrtitle'], true) && $title === '') {
                    $title = implode(' ', array_column($shapeLines, 'text'));
                } else {
                    foreach ($shapeLines as $entry) {
                        $items[] = $entry;
                    }
                }
                continue;
            }

            if ($name === 'graphicFrame') {
                $tbl = $this->findFirstDescendantByLocalName($child, 'tbl');
                if ($tbl !== null) {
                    $parsedTable = $this->extractDrawingTable($tbl);
                    if ($parsedTable !== null) {
                        $tables[] = $parsedTable;
                    }
                }
                continue;
            }

            if ($name === 'pic') {
                $blip = $this->findFirstDescendantByLocalName($child, 'blip');
                if ($blip !== null) {
                    $rId = $this->getAttributeByLocalName($blip, 'embed');
                    if ($rId !== '' && isset($rels[$rId])) {
                        $entryPath = $this->resolveZipTarget('ppt/slides/', $rels[$rId]);
                        $dataUri = $this->extractSafeRasterDataUri($zip, $entryPath, $imageCount, $imageBytes);
                        if ($dataUri !== null) {
                            $images[] = $dataUri;
                        }
                    }
                }
            }
        }
    }

    private function detectPptxPlaceholderType(\DOMElement $sp): string
    {
        $nvSpPr = $this->findFirstDirectChildByLocalName($sp, 'nvSpPr');
        $nvPr = $nvSpPr ? $this->findFirstDirectChildByLocalName($nvSpPr, 'nvPr') : null;
        $ph = $nvPr ? $this->findFirstDirectChildByLocalName($nvPr, 'ph') : null;

        return $ph ? strtolower($this->getAttributeByLocalName($ph, 'type')) : '';
    }

    private function collectDrawingTextRuns(\DOMNode $node, array &$runs): void
    {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $name = $child->localName;
            if (in_array($name, ['pPr', 'rPr', 'endParaRPr', 'Fallback'], true)) {
                continue;
            }
            if ($name === 't') {
                $runs[] = ['text' => $child->textContent, 'bold' => false, 'italic' => false];
                continue;
            }
            if ($name === 'br') {
                $runs[] = ['text' => "\n", 'bold' => false, 'italic' => false];
                continue;
            }
            if ($name === 'tab') {
                $runs[] = ['text' => ' ', 'bold' => false, 'italic' => false];
                continue;
            }
            $this->collectDrawingTextRuns($child, $runs);
        }
    }

    private function extractDrawingTable(\DOMElement $tbl): ?array
    {
        $rows = [];
        foreach ($tbl->childNodes as $tr) {
            if (!$tr instanceof \DOMElement || $tr->localName !== 'tr') {
                continue;
            }
            if (count($rows) >= self::MAX_TABLE_ROWS) {
                break;
            }

            $cells = [];
            foreach ($tr->childNodes as $tc) {
                if (!$tc instanceof \DOMElement || $tc->localName !== 'tc') {
                    continue;
                }
                if (count($cells) >= self::MAX_TABLE_COLS) {
                    break;
                }

                $runs = [];
                $this->collectDrawingTextRuns($tc, $runs);
                $cells[] = trim($this->runsToText($runs));
            }
            if (!empty($cells)) {
                $rows[] = $cells;
            }
        }

        return empty($rows) ? null : $rows;
    }

    private function parseOdp(string $fullPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== true) {
            return ['ok' => false, 'message' => 'El archivo .odp no es una presentación válida o está dañada.'];
        }

        try {
            $xmlStr = $this->readZipEntryLimited($zip, 'content.xml', self::MAX_XML_BYTES);
            if ($xmlStr === null) {
                return ['ok' => false, 'message' => 'No se pudo leer el contenido de esta presentación ODP.'];
            }

            $dom = $this->loadXml($xmlStr);
            if ($dom === null || $dom->documentElement === null) {
                return ['ok' => false, 'message' => 'No se pudo interpretar el contenido XML de la presentación ODP.'];
            }

            $presentation = $this->findFirstDescendantByLocalName($dom->documentElement, 'presentation');
            if ($presentation === null) {
                return ['ok' => false, 'message' => 'La presentación ODP no contiene diapositivas legibles.'];
            }

            $pages = [];
            foreach ($presentation->childNodes as $child) {
                if ($child instanceof \DOMElement && $child->localName === 'page') {
                    $pages[] = $child;
                }
            }

            $totalSlides = count($pages);
            $slides = [];
            $imageCount = 0;
            $imageBytes = 0;
            $styleMap = $this->buildOdfStyleMap($dom->documentElement);

            foreach (array_slice($pages, 0, self::MAX_SLIDES) as $idx => $page) {
                $title = '';
                $items = [];
                $tables = [];
                $images = [];

                foreach ($page->childNodes as $child) {
                    if (!$child instanceof \DOMElement) {
                        continue;
                    }
                    $this->walkOdpNode($child, $zip, $styleMap, $title, $items, $tables, $images, $imageCount, $imageBytes);
                }

                $slides[] = [
                    'number' => $idx + 1,
                    'title'  => $title,
                    'items'  => $items,
                    'tables' => $tables,
                    'images' => $images,
                ];
            }

            return [
                'ok'          => true,
                'format'      => 'ODP',
                'totalSlides' => $totalSlides,
                'truncated'   => $totalSlides > self::MAX_SLIDES,
                'slides'      => $slides,
            ];
        } finally {
            $zip->close();
        }
    }

    private function walkOdpNode(
        \DOMElement $node,
        ZipArchive $zip,
        array $styleMap,
        string &$title,
        array &$items,
        array &$tables,
        array &$images,
        int &$imageCount,
        int &$imageBytes
    ): void {
        $name = $node->localName;
        if ($name === 'frame') {
            $presClass = strtolower($this->getAttributeByLocalName($node, 'class'));
            if ($presClass === 'page-number') {
                return;
            }

            $frameLines = [];
            $this->collectOdpFrameText($node, $zip, $styleMap, $frameLines, $images, $imageCount, $imageBytes, 0);

            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement && $child->localName === 'table') {
                    $tbl = $this->extractOdfTable($child);
                    if ($tbl !== null) {
                        $tables[] = $tbl['rows'];
                    }
                }
            }

            if ($presClass === 'title' && $title === '' && !empty($frameLines)) {
                $title = implode(' ', array_column($frameLines, 'text'));
            } else {
                foreach ($frameLines as $entry) {
                    $items[] = $entry;
                }
            }
            return;
        }

        if ($name === 'table') {
            $tbl = $this->extractOdfTable($node);
            if ($tbl !== null) {
                $tables[] = $tbl['rows'];
            }
            return;
        }

        if ($name === 'g') {
            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $this->walkOdpNode($child, $zip, $styleMap, $title, $items, $tables, $images, $imageCount, $imageBytes);
                }
            }
        }
    }

    private function collectOdpFrameText(
        \DOMNode $node,
        ZipArchive $zip,
        array $styleMap,
        array &$lines,
        array &$images,
        int &$imageCount,
        int &$imageBytes,
        int $level
    ): void {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $name = $child->localName;
            if ($name === 'p' || $name === 'h') {
                $runs = [];
                $localImages = [];
                $this->collectOdfRuns($child, $zip, $styleMap, $runs, $localImages, $imageCount, $imageBytes, false, false);
                $text = trim($this->runsToText($runs));
                if ($text !== '') {
                    $lines[] = ['text' => $text, 'level' => min(4, $level)];
                }
                foreach ($localImages as $src) {
                    $images[] = $src;
                }
                continue;
            }
            if ($name === 'image') {
                $href = $this->getAttributeByLocalName($child, 'href');
                if ($href !== '') {
                    $dataUri = $this->extractSafeRasterDataUri($zip, $this->resolveZipTarget('', $href), $imageCount, $imageBytes);
                    if ($dataUri !== null) {
                        $images[] = $dataUri;
                    }
                }
                continue;
            }
            if ($name === 'list') {
                $this->collectOdpFrameText($child, $zip, $styleMap, $lines, $images, $imageCount, $imageBytes, $level + 1);
                continue;
            }
            if ($name === 'list-item' || $name === 'text-box') {
                $this->collectOdpFrameText($child, $zip, $styleMap, $lines, $images, $imageCount, $imageBytes, $level);
            }
        }
    }

    private function parseLegacyPpt(string $fullPath): array
    {
        $head = @file_get_contents($fullPath, false, null, 0, 8) ?: '';
        if (str_starts_with($head, "PK\x03\x04")) {
            return $this->parsePptx($fullPath);
        }

        $raw = $this->readFileLimited($fullPath, self::MAX_LEGACY_BYTES);
        if ($raw === '') {
            return ['ok' => false, 'message' => 'El archivo .ppt está vacío o no se pudo leer.'];
        }

        $slides = $this->extractPptBinaryRecords($raw);

        if (empty($slides)) {
            $paragraphs = $this->extractReadableBinaryText($raw);
            if (!empty($paragraphs)) {
                $slides[] = [
                    'number' => 1,
                    'title'  => 'Contenido extraído de la presentación',
                    'items'  => array_map(
                        static fn (string $p) => ['text' => $p, 'level' => 0],
                        array_slice($paragraphs, 0, 80)
                    ),
                    'tables' => [],
                    'images' => [],
                ];
            }
        }

        if (empty($slides)) {
            return [
                'ok'      => false,
                'message' => 'No se pudo extraer texto legible de esta presentación .ppt binaria. Descárgala para abrirla en PowerPoint.',
            ];
        }

        return [
            'ok'          => true,
            'format'      => 'PPT',
            'notice'      => 'Vista previa simplificada de presentación PowerPoint heredada (.ppt). Descárgala para ver su diseño completo.',
            'totalSlides' => count($slides),
            'truncated'   => count($slides) >= self::MAX_SLIDES,
            'slides'      => $slides,
        ];
    }

    private function extractPptBinaryRecords(string $raw): array
    {
        $len = strlen($raw);
        $chunks = [];
        $offset = 0;

        // Buscar TextCharsAtom (0x0FA0 = UTF-16LE) y TextBytesAtom (0x0FA8 = Windows-1252)
        while ($offset < $len - 8) {
            $posU16 = strpos($raw, "\x00\x00\xA0\x0F", $offset);
            $posU8 = strpos($raw, "\x00\x00\xA8\x0F", $offset);

            if ($posU16 === false && $posU8 === false) {
                break;
            }

            $isU16 = $posU8 === false || ($posU16 !== false && $posU16 < $posU8);
            $pos = $isU16 ? $posU16 : $posU8;

            $sizeData = unpack('Vlen', substr($raw, $pos + 4, 4));
            $recLen = (int) ($sizeData['len'] ?? 0);

            if ($recLen <= 0 || $recLen > 65536 || $pos + 8 + $recLen > $len) {
                $offset = $pos + 4;
                continue;
            }

            $payload = substr($raw, $pos + 8, $recLen);
            $decoded = $isU16
                ? @mb_convert_encoding($payload, 'UTF-8', 'UTF-16LE')
                : @mb_convert_encoding($payload, 'UTF-8', 'Windows-1252');

            $cleaned = $this->normalizeLegacyText($decoded ?: '');
            if ($cleaned !== '' && !$this->looksLikeOleNoise($cleaned)) {
                $chunks[] = ['offset' => $pos, 'text' => $cleaned];
            }

            $offset = $pos + 8 + $recLen;
            if (count($chunks) >= 300) {
                break;
            }
        }

        if (empty($chunks)) {
            return [];
        }

        // Agrupar bloques consecutivos en tarjetas de diapositiva usando SlidePersistAtom (0x03F3) o bloques lógicos
        $slides = [];
        $currentItems = [];
        $slideNum = 1;

        foreach ($chunks as $chunk) {
            $lines = preg_split("/\r\n|\r|\n/", $chunk['text']) ?: [];
            $lines = array_values(array_filter(array_map('trim', $lines), static fn ($l) => $l !== ''));
            if (empty($lines)) {
                continue;
            }

            if (count($currentItems) >= 10 && count($slides) < self::MAX_SLIDES) {
                $title = $currentItems[0]['text'];
                $slides[] = [
                    'number' => $slideNum++,
                    'title'  => mb_strlen($title) <= 100 ? $title : '',
                    'items'  => mb_strlen($title) <= 100 ? array_slice($currentItems, 1) : $currentItems,
                    'tables' => [],
                    'images' => [],
                ];
                $currentItems = [];
            }

            foreach ($lines as $line) {
                $currentItems[] = ['text' => $line, 'level' => 0];
            }
        }

        if (!empty($currentItems) && count($slides) < self::MAX_SLIDES) {
            $title = $currentItems[0]['text'];
            $slides[] = [
                'number' => $slideNum,
                'title'  => mb_strlen($title) <= 100 ? $title : '',
                'items'  => mb_strlen($title) <= 100 ? array_slice($currentItems, 1) : $currentItems,
                'tables' => [],
                'images' => [],
            ];
        }

        return $slides;
    }

    /* ─────────────────────── HELPERS COMPARTIDOS ─────────────────────── */

    private function buildOdfStyleMap(\DOMElement $root): array
    {
        $map = [];
        $autoStyles = $this->findFirstDescendantByLocalName($root, 'automatic-styles');
        if ($autoStyles === null) {
            return $map;
        }

        foreach ($autoStyles->childNodes as $style) {
            if (!$style instanceof \DOMElement || $style->localName !== 'style') {
                continue;
            }
            $name = $this->getAttributeByLocalName($style, 'name');
            if ($name === '') {
                continue;
            }
            $textProps = $this->findFirstDirectChildByLocalName($style, 'text-properties');
            if ($textProps === null) {
                continue;
            }
            $weight = strtolower($this->getAttributeByLocalName($textProps, 'font-weight'));
            $fontStyle = strtolower($this->getAttributeByLocalName($textProps, 'font-style'));
            $map[$name] = [
                'bold'   => $weight === 'bold' || ((int) $weight >= 600),
                'italic' => $fontStyle === 'italic' || $fontStyle === 'oblique',
            ];
        }

        return $map;
    }

    private function collectOdfRuns(
        \DOMNode $node,
        ZipArchive $zip,
        array $styleMap,
        array &$runs,
        array &$images,
        int &$imageCount,
        int &$imageBytes,
        bool $bold,
        bool $italic
    ): void {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMText) {
                if ($child->wholeText !== '') {
                    $runs[] = ['text' => $child->wholeText, 'bold' => $bold, 'italic' => $italic];
                }
                continue;
            }

            if (!$child instanceof \DOMElement) {
                continue;
            }

            $name = $child->localName;
            if (in_array($name, ['note', 'annotation'], true)) {
                continue;
            }

            if ($name === 's') {
                $cntRaw = $this->getAttributeByLocalName($child, 'c');
                $cnt = ($cntRaw !== '' && ctype_digit($cntRaw)) ? min(20, max(1, (int) $cntRaw)) : 1;
                $runs[] = ['text' => str_repeat(' ', $cnt), 'bold' => $bold, 'italic' => $italic];
                continue;
            }

            if ($name === 'tab') {
                $runs[] = ['text' => '    ', 'bold' => $bold, 'italic' => $italic];
                continue;
            }

            if ($name === 'line-break') {
                $runs[] = ['text' => "\n", 'bold' => false, 'italic' => false];
                continue;
            }

            if ($name === 'image') {
                $href = $this->getAttributeByLocalName($child, 'href');
                if ($href !== '') {
                    $dataUri = $this->extractSafeRasterDataUri($zip, $this->resolveZipTarget('', $href), $imageCount, $imageBytes);
                    if ($dataUri !== null) {
                        $images[] = $dataUri;
                    }
                }
                continue;
            }

            if ($name === 'span') {
                $styleName = $this->getAttributeByLocalName($child, 'style-name');
                $st = $styleMap[$styleName] ?? ['bold' => false, 'italic' => false];
                $this->collectOdfRuns(
                    $child,
                    $zip,
                    $styleMap,
                    $runs,
                    $images,
                    $imageCount,
                    $imageBytes,
                    $bold || $st['bold'],
                    $italic || $st['italic']
                );
                continue;
            }

            $this->collectOdfRuns($child, $zip, $styleMap, $runs, $images, $imageCount, $imageBytes, $bold, $italic);
        }
    }

    private function extractOdfTable(\DOMElement $table): ?array
    {
        $rows = [];
        foreach ($table->childNodes as $rowEl) {
            if (!$rowEl instanceof \DOMElement || $rowEl->localName !== 'table-row') {
                continue;
            }
            if (count($rows) >= self::MAX_TABLE_ROWS) {
                break;
            }

            $cells = [];
            foreach ($rowEl->childNodes as $cellEl) {
                if (!$cellEl instanceof \DOMElement || $cellEl->localName !== 'table-cell') {
                    continue;
                }
                if (count($cells) >= self::MAX_TABLE_COLS) {
                    break;
                }
                $cells[] = trim($this->collectOdfCellText($cellEl));
            }

            if (!empty($cells)) {
                $rows[] = $cells;
            }
        }

        return empty($rows) ? null : ['type' => 'table', 'rows' => $rows];
    }

    private function collectOdfCellText(\DOMElement $cell): string
    {
        $lines = [];
        foreach ($cell->childNodes as $child) {
            if ($child instanceof \DOMElement && ($child->localName === 'p' || $child->localName === 'h')) {
                $txt = trim($child->textContent);
                if ($txt !== '') {
                    $lines[] = $txt;
                }
            }
        }

        return implode("\n", $lines);
    }

    private function readZipEntryLimited(ZipArchive $zip, string $entryName, int $maxBytes): ?string
    {
        $stream = @$zip->getStream($entryName);
        if (!is_resource($stream)) {
            return null;
        }

        try {
            $buffer = '';
            $read = 0;
            while (!feof($stream)) {
                $chunk = fread($stream, 65536);
                if ($chunk === false) {
                    return null;
                }
                if ($chunk === '') {
                    break;
                }
                $read += strlen($chunk);
                if ($read > $maxBytes) {
                    return null;
                }
                $buffer .= $chunk;
            }

            return $buffer;
        } finally {
            fclose($stream);
        }
    }

    private function readFileLimited(string $fullPath, int $maxBytes): string
    {
        $fh = @fopen($fullPath, 'rb');
        if (!is_resource($fh)) {
            return '';
        }

        try {
            return fread($fh, $maxBytes) ?: '';
        } finally {
            fclose($fh);
        }
    }

    private function loadXml(string $xml): ?\DOMDocument
    {
        if (stripos($xml, '<!ENTITY') !== false) {
            return null;
        }

        $prev = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument();
            $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
            return $loaded ? $dom : null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    private function readRelsMap(ZipArchive $zip, string $relsPath): array
    {
        $xml = $this->readZipEntryLimited($zip, $relsPath, 1_048_576);
        if ($xml === null) {
            return [];
        }

        $dom = $this->loadXml($xml);
        if ($dom === null || $dom->documentElement === null) {
            return [];
        }

        $map = [];
        foreach ($dom->documentElement->childNodes as $rel) {
            if (!$rel instanceof \DOMElement || $rel->localName !== 'Relationship') {
                continue;
            }
            $id = $rel->getAttribute('Id');
            $target = $rel->getAttribute('Target');
            $mode = strtolower($rel->getAttribute('TargetMode'));
            if ($id !== '' && $target !== '' && $mode !== 'external') {
                $map[$id] = $target;
            }
        }

        return $map;
    }

    private function resolveZipTarget(string $baseDir, string $target): string
    {
        $target = str_replace('\\', '/', $target);
        if (str_starts_with($target, '/')) {
            $combined = ltrim($target, '/');
        } else {
            $combined = $baseDir . $target;
        }

        $parts = [];
        foreach (explode('/', $combined) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private function extractSafeRasterDataUri(
        ZipArchive $zip,
        string $entryPath,
        int &$imageCount,
        int &$imageBytes
    ): ?string {
        if ($entryPath === '' || $imageCount >= self::MAX_IMAGES || $imageBytes >= self::MAX_TOTAL_IMAGE_BYTES) {
            return null;
        }

        $ext = strtolower((string) pathinfo($entryPath, PATHINFO_EXTENSION));
        $allowedMime = match ($ext) {
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => null,
        };

        if ($allowedMime === null) {
            return null;
        }

        $remaining = min(self::MAX_IMAGE_BYTES, self::MAX_TOTAL_IMAGE_BYTES - $imageBytes);
        $bytes = $this->readZipEntryLimited($zip, $entryPath, $remaining);
        if ($bytes === null || $bytes === '') {
            return null;
        }

        $info = @getimagesizefromstring($bytes);
        if (!is_array($info) || empty($info['mime']) || !in_array($info['mime'], ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
            return null;
        }

        $imageCount++;
        $imageBytes += strlen($bytes);

        return 'data:' . $info['mime'] . ';base64,' . base64_encode($bytes);
    }

    private function extractReadableBinaryText(string $raw): array
    {
        $paragraphs = [];

        // 1. UTF-16LE (típico en Unicode de Word 97-2003 / PowerPoint)
        if (preg_match_all('/(?:[\x20-\x7E\xA1-\xFF\r\n\t]\x00){4,}/s', $raw, $u16Matches)) {
            foreach ($u16Matches[0] as $seq) {
                $utf8 = @mb_convert_encoding($seq, 'UTF-8', 'UTF-16LE');
                if ($utf8 === false) {
                    continue;
                }
                $this->appendNormalizedBinaryParagraphs($utf8, $paragraphs);
            }
        }

        // 2. Fallback a Windows-1252 si UTF-16LE devolvió muy poco texto
        $totalChars = array_sum(array_map('mb_strlen', $paragraphs));
        if ($totalChars < 24 && preg_match_all('/[\x20-\x7E\xA1-\xFF\r\n\t]{5,}/', $raw, $u8Matches)) {
            foreach ($u8Matches[0] as $seq) {
                $utf8 = @mb_convert_encoding($seq, 'UTF-8', 'Windows-1252');
                if ($utf8 === false) {
                    continue;
                }
                $this->appendNormalizedBinaryParagraphs($utf8, $paragraphs);
            }
        }

        return $paragraphs;
    }

    private function appendNormalizedBinaryParagraphs(string $chunk, array &$paragraphs): void
    {
        $normalized = $this->normalizeLegacyText($chunk);
        foreach (preg_split("/\r\n|\r|\n/", $normalized) ?: [] as $line) {
            $line = trim((string) preg_replace('/[ \t]{2,}/', ' ', $line));
            if (mb_strlen($line) < 2 || $this->looksLikeOleNoise($line)) {
                continue;
            }
            // Descartar líneas casi totalmente simbólicas sin letras o dígitos
            if (!preg_match('/[\p{L}\p{N}]/u', $line)) {
                continue;
            }
            $paragraphs[] = $line;
        }
    }

    private function normalizeLegacyText(string $text): string
    {
        // En OLE Word/PPT el salto de párrafo interno suele ser \r o \x0b (vertical tab)
        $text = str_replace(["\r\n", "\r", "\x0b"], "\n", $text);
        // Quitar caracteres de control no imprimibles salvo \n y \t
        return (string) preg_replace('/[\x00-\x08\x0C\x0E-\x1F\x7F]/', '', $text);
    }

    private function looksLikeOleNoise(string $line): bool
    {
        $noisePatterns = [
            '/^(?:Root Entry|WordDocument|[01]Table|SummaryInformation|DocumentSummaryInformation|CompObj|MsoDataStore|PowerPoint Document|Current User|Pictures)$/i',
            '/^(?:Microsoft (?:Office )?(?:Word|PowerPoint)|Normal(?:\.dotm)?|Times New Roman|Calibri|Cambria|Arial)$/i',
            '/^(?:MSWordDoc|Word\.Document\.\d+|PowerPoint\.Show\.\d+|bjbj.{0,10})$/i',
            '/^HYPERLINK\s+"[^"]*"$/i',
        ];

        foreach ($noisePatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    private function mergeStyledRuns(array $runs): array
    {
        $merged = [];
        foreach ($runs as $run) {
            $text = $run['text'] ?? '';
            if ($text === '') {
                continue;
            }
            $bold = (bool) ($run['bold'] ?? false);
            $italic = (bool) ($run['italic'] ?? false);
            $lastIdx = count($merged) - 1;

            if ($lastIdx >= 0 && $merged[$lastIdx]['bold'] === $bold && $merged[$lastIdx]['italic'] === $italic) {
                $merged[$lastIdx]['text'] .= $text;
            } else {
                $merged[] = ['text' => $text, 'bold' => $bold, 'italic' => $italic];
            }
        }

        return $merged;
    }

    private function runsToText(array $runs): string
    {
        return implode('', array_column($runs, 'text'));
    }

    private function findFirstDirectChildByLocalName(\DOMNode $parent, string $localName): ?\DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->localName === $localName) {
                return $child;
            }
        }

        return null;
    }

    private function findFirstDescendantByLocalName(\DOMNode $root, string $localName): ?\DOMElement
    {
        foreach ($root->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                if ($child->localName === $localName) {
                    return $child;
                }
                $found = $this->findFirstDescendantByLocalName($child, $localName);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function getAttributeByLocalName(\DOMElement $el, string $localName): string
    {
        foreach ($el->attributes as $attr) {
            if ($attr->localName === $localName) {
                return (string) $attr->value;
            }
        }

        return '';
    }

    private function ensureUtf8(string $text, string $fallbackEncodings = 'Windows-1252'): string
    {
        if ($text === '' || mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $converted = @mb_convert_encoding($text, 'UTF-8', $fallbackEncodings);
        if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        return mb_scrub($text, 'UTF-8');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }
}
