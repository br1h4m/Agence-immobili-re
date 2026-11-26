<?php
// backend/helpers/XlsxHandler.php
declare(strict_types=1);

namespace Immolode\Helpers;

/**
 * Lecteur et générateur natif de fichiers Excel (.xlsx OOXML) et CSV UTF-8
 * en PHP pur (zlib + SimpleXML), sans dépendance externe ni ZipArchive.
 */
class XlsxHandler {
    /**
     * Vérifie si un fichier binaire possède l'en-tête ZIP d'un vrai .xlsx (PK\x03\x04).
     */
    public static function isXlsxFile(string $filePath): bool {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return false;
        }
        $fh = fopen($filePath, 'rb');
        if (!$fh) {
            return false;
        }
        $magic = fread($fh, 4);
        fclose($fh);
        return $magic === "PK\x03\x04";
    }

    /**
     * Extrait les entrées XML d'une archive ZIP (.xlsx) via son Central Directory.
     * Gère les fichiers générés par Excel, LibreOffice, Google Sheets, SheetJS et openpyxl.
     */
    private static function readZipEntries(string $binary): array {
        $len = strlen($binary);
        if ($len < 22) {
            throw new \RuntimeException('Archive .xlsx invalide ou corrompue.');
        }

        // Recherche du End of Central Directory (PK\x05\x06) depuis la fin
        $searchStart = max(0, $len - 65557);
        $eocdPos = strrpos(substr($binary, $searchStart), "PK\x05\x06");
        if ($eocdPos === false) {
            throw new \RuntimeException('Le fichier n\'est pas une archive .xlsx valide.');
        }
        $eocdPos += $searchStart;

        $eocd = unpack(
            'vtotalEntries/VcdSize/VcdOffset',
            substr($binary, $eocdPos + 10, 10)
        );
        $cdOffset = (int)$eocd['cdOffset'];
        $totalEntries = (int)$eocd['totalEntries'];

        $entries = [];
        $pos = $cdOffset;
        for ($i = 0; $i < $totalEntries; $i++) {
            if ($pos + 46 > $len || substr($binary, $pos, 4) !== "PK\x01\x02") {
                break;
            }
            $hdr = unpack(
                'vmethod/vtime/vdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen/vcommentLen/vdisk/vintAttr/VextAttr/VlocalOffset',
                substr($binary, $pos + 10, 36)
            );
            $name = substr($binary, $pos + 46, $hdr['nameLen']);
            $pos += 46 + $hdr['nameLen'] + $hdr['extraLen'] + $hdr['commentLen'];

            $localOffset = (int)$hdr['localOffset'];
            if ($localOffset + 30 > $len || substr($binary, $localOffset, 4) !== "PK\x03\x04") {
                continue;
            }
            $loc = unpack('vnameLen/vextraLen', substr($binary, $localOffset + 26, 4));
            $dataStart = $localOffset + 30 + $loc['nameLen'] + $loc['extraLen'];
            $rawData = substr($binary, $dataStart, (int)$hdr['compSize']);

            if ((int)$hdr['method'] === 0) {
                $entries[$name] = $rawData;
            } elseif ((int)$hdr['method'] === 8) {
                $inflated = @gzinflate($rawData);
                if ($inflated !== false) {
                    $entries[$name] = $inflated;
                }
            }
        }

        return $entries;
    }

    /**
     * Convertit une référence de colonne Excel (ex. "AB12" -> index 0-basé).
     */
    private static function columnRefToIndex(string $cellRef): int {
        $letters = preg_replace('/[^A-Z]/i', '', strtoupper($cellRef));
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return max(0, $index - 1);
    }

    /**
     * Convertit un index 0-basé en lettre de colonne Excel (0 -> A, 26 -> AA).
     */
    private static function indexToColumnLetters(int $index): string {
        $letters = '';
        $num = $index + 1;
        while ($num > 0) {
            $mod = ($num - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $num = intdiv($num - 1, 26);
        }
        return $letters;
    }

    /**
     * Lit un véritable fichier Excel (.xlsx) et retourne les lignes sous forme de tableaux indexés.
     */
    public static function parseXlsx(string $filePath): array {
        $binary = file_get_contents($filePath);
        if ($binary === false || !str_starts_with($binary, "PK\x03\x04")) {
            throw new \RuntimeException('Le fichier transmis n\'est pas un véritable fichier Excel (.xlsx valide).');
        }

        $entries = self::readZipEntries($binary);
        if (!isset($entries['xl/workbook.xml'])) {
            throw new \RuntimeException('Structure Excel (.xlsx) invalide : classeur introuvable.');
        }

        // 1. Chargement des chaînes partagées (sharedStrings.xml)
        $sharedStrings = [];
        if (isset($entries['xl/sharedStrings.xml'])) {
            $ssXml = @simplexml_load_string($entries['xl/sharedStrings.xml']);
            if ($ssXml !== false) {
                $ssXml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($ssXml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $txt = '';
                        foreach ($si->r as $run) {
                            $txt .= (string)$run->t;
                        }
                        $sharedStrings[] = $txt;
                    } else {
                        $sharedStrings[] = trim((string)$si);
                    }
                }
            }
        }

        // 2. Localisation de la première feuille de calcul
        $sheetEntryName = 'xl/worksheets/sheet1.xml';
        if (isset($entries['xl/_rels/workbook.xml.rels'], $entries['xl/workbook.xml'])) {
            $wbXml = @simplexml_load_string($entries['xl/workbook.xml']);
            $relsXml = @simplexml_load_string($entries['xl/_rels/workbook.xml.rels']);
            if ($wbXml !== false && $relsXml !== false && isset($wbXml->sheets->sheet[0])) {
                $rAttrs = $wbXml->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $firstRid = (string)($rAttrs['id'] ?? '');
                foreach ($relsXml->Relationship as $rel) {
                    if ((string)$rel['Id'] === $firstRid) {
                        $target = ltrim((string)$rel['Target'], '/');
                        if (!str_starts_with($target, 'xl/')) {
                            $target = 'xl/' . $target;
                        }
                        if (isset($entries[$target])) {
                            $sheetEntryName = $target;
                        }
                        break;
                    }
                }
            }
        }

        if (!isset($entries[$sheetEntryName])) {
            throw new \RuntimeException('Aucune feuille de calcul trouvée dans le fichier Excel.');
        }

        $sheetXml = @simplexml_load_string($entries[$sheetEntryName]);
        if ($sheetXml === false || !isset($sheetXml->sheetData)) {
            throw new \RuntimeException('Impossible de lire les données de la feuille Excel.');
        }

        $rows = [];
        $maxCols = 0;

        foreach ($sheetXml->sheetData->row as $rowNode) {
            $rowVals = [];
            $fallbackCol = 0;
            foreach ($rowNode->c as $cell) {
                $ref = (string)($cell['r'] ?? '');
                $colIdx = $ref !== '' ? self::columnRefToIndex($ref) : $fallbackCol;
                $fallbackCol = $colIdx + 1;

                $type = (string)($cell['t'] ?? '');
                $val = '';
                if ($type === 's') {
                    $idx = (int)($cell->v ?? -1);
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    if (isset($cell->is->t)) {
                        $val = (string)$cell->is->t;
                    } elseif (isset($cell->is->r)) {
                        foreach ($cell->is->r as $run) {
                            $val .= (string)$run->t;
                        }
                    }
                } elseif ($type === 'b') {
                    $val = ((string)$cell->v === '1') ? '1' : '0';
                } else {
                    $val = isset($cell->v) ? (string)$cell->v : '';
                }

                $rowVals[$colIdx] = trim($val);
                if ($colIdx + 1 > $maxCols) {
                    $maxCols = $colIdx + 1;
                }
            }

            // Ignorer les lignes totalement vides
            $nonEmpty = false;
            foreach ($rowVals as $v) {
                if ($v !== '') {
                    $nonEmpty = true;
                    break;
                }
            }
            if (!$nonEmpty) {
                continue;
            }

            $dense = [];
            for ($c = 0; $c < $maxCols; $c++) {
                $dense[] = $rowVals[$c] ?? '';
            }
            $rows[] = $dense;
        }

        return $rows;
    }

    /**
     * Analyse les fichiers .xls basés sur SpreadsheetML XML 2003 ou table HTML exportée pour Excel.
     */
    public static function parseXmlOrHtmlSpreadsheet(string $content): array {
        $rows = [];
        // SpreadsheetML 2003 (<Workbook ...><Worksheet><Table><Row><Cell><Data>...)
        if (stripos($content, '<Workbook') !== false || stripos($content, '<ss:Row') !== false || stripos($content, '<Row') !== false) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $loaded = $dom->loadXML($content);
            libxml_clear_errors();
            if ($loaded) {
                foreach ($dom->getElementsByTagName('Row') as $rowNode) {
                    $r = [];
                    $col = 0;
                    foreach ($rowNode->childNodes as $cell) {
                        if (!($cell instanceof \DOMElement) || strtolower($cell->localName) !== 'cell') {
                            continue;
                        }
                        $idxAttr = $cell->getAttribute('ss:Index') ?: $cell->getAttributeNS('urn:schemas-microsoft-com:office:spreadsheet', 'Index');
                        if ($idxAttr !== '') {
                            $targetIdx = max(0, (int)$idxAttr - 1);
                            while ($col < $targetIdx) {
                                $r[] = '';
                                $col++;
                            }
                        }
                        $r[] = trim($cell->textContent);
                        $col++;
                    }
                    if (!empty(array_filter($r, fn($v) => $v !== ''))) {
                        $rows[] = $r;
                    }
                }
                if (!empty($rows)) {
                    return $rows;
                }
            }
        }

        // HTML Table (.xls fallback)
        if (stripos($content, '<table') !== false || stripos($content, '<tr') !== false) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
            libxml_clear_errors();
            foreach ($dom->getElementsByTagName('tr') as $tr) {
                $r = [];
                foreach ($tr->childNodes as $cell) {
                    if ($cell instanceof \DOMElement && in_array(strtolower($cell->tagName), ['th', 'td'], true)) {
                        $r[] = trim($cell->textContent);
                    }
                }
                if (!empty(array_filter($r, fn($v) => $v !== ''))) {
                    $rows[] = $r;
                }
            }
            if (!empty($rows)) {
                return $rows;
            }
        }

        return $rows;
    }

    /**
     * Analyse un fichier CSV UTF-8/ISO avec détection automatique du séparateur (; , \t).
     */
    public static function parseCsv(string $content): array {
        // Supprimer BOM UTF-8 éventuel
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252,ISO-8859-1');
        }
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [];
        }

        $firstLine = $lines[0];
        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $rows = [];
        // Utiliser un flux mémoire pour gérer correctement les guillemets multilignes éventuels
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $content);
        rewind($fh);
        while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
            $nonEmpty = false;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') {
                    $nonEmpty = true;
                    break;
                }
            }
            if ($nonEmpty) {
                $rows[] = array_map(fn($c) => trim((string)$c), $row);
            }
        }
        fclose($fh);

        return $rows;
    }

    /**
     * Échappe une chaîne pour XML 1.0 en supprimant les caractères de contrôle illégaux.
     */
    private static function xmlEscape(string $value): string {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Génère un véritable fichier Excel binaire (.xlsx OOXML) en mémoire.
     */
    public static function buildXlsxBinary(array $headers, array $rows, string $sheetName = 'Export'): string {
        $safeSheet = self::xmlEscape(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $sheetName), 0, 31) ?: 'Export');

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $rootRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $safeSheet . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF1C1C1C"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF4EFE6"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD6D2CA"/></left><right style="thin"><color rgb="FFD6D2CA"/></right><top style="thin"><color rgb="FFD6D2CA"/></top><bottom style="medium"><color rgb="FFB59A6A"/></bottom></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';

        // Construction de sheet1.xml
        $sheetDataXml = '<row r="1">';
        foreach (array_values($headers) as $cIdx => $headerText) {
            $cellRef = self::indexToColumnLetters($cIdx) . '1';
            $sheetDataXml .= '<c r="' . $cellRef . '" t="inlineStr" s="1"><is><t>'
                . self::xmlEscape((string)$headerText) . '</t></is></c>';
        }
        $sheetDataXml .= '</row>';

        foreach (array_values($rows) as $rIdx => $row) {
            $rowNum = $rIdx + 2;
            $sheetDataXml .= '<row r="' . $rowNum . '">';
            foreach (array_values($row) as $cIdx => $cellVal) {
                $cellRef = self::indexToColumnLetters($cIdx) . $rowNum;
                $strVal = (string)($cellVal ?? '');
                if ($strVal !== '' && preg_match('/^-?(?:0|[1-9]\d*)(?:\.\d+)?$/', $strVal) && strlen($strVal) <= 14) {
                    $sheetDataXml .= '<c r="' . $cellRef . '"><v>' . $strVal . '</v></c>';
                } else {
                    $sheetDataXml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t xml:space="preserve">'
                        . self::xmlEscape($strVal) . '</t></is></c>';
                }
            }
            $sheetDataXml .= '</row>';
        }

        // Largeurs automatiques des colonnes
        $colsXml = '<cols>';
        $colCount = count($headers);
        for ($cIdx = 0; $cIdx < $colCount; $cIdx++) {
            $maxW = mb_strlen((string)($headers[$cIdx] ?? ''));
            foreach ($rows as $row) {
                $cellLen = mb_strlen((string)($row[$cIdx] ?? ''));
                if ($cellLen > $maxW) {
                    $maxW = $cellLen;
                }
            }
            $width = min(48, max(12, $maxW + 4));
            $colNum = $cIdx + 1;
            $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $colsXml
            . '<sheetData>' . $sheetDataXml . '</sheetData>'
            . '</worksheet>';

        return self::buildZipArchive([
            '[Content_Types].xml'       => $contentTypesXml,
            '_rels/.rels'               => $rootRelsXml,
            'xl/workbook.xml'           => $workbookXml,
            'xl/_rels/workbook.xml.rels'=> $workbookRelsXml,
            'xl/styles.xml'             => $stylesXml,
            'xl/worksheets/sheet1.xml'  => $sheetXml
        ]);
    }

    /**
     * Empaquette un tableau associatif [chemin => contenu] en archive ZIP standard (Deflate).
     */
    private static function buildZipArchive(array $files): string {
        $zipData = '';
        $centralDir = '';
        $offset = 0;
        $count = 0;

        foreach ($files as $name => $content) {
            $crc = crc32($content) & 0xFFFFFFFF;
            $uncompSize = strlen($content);
            $compressed = gzdeflate($content, 6);
            $compSize = strlen($compressed);
            $nameLen = strlen($name);

            // Local File Header (PK\x03\x04)
            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034B50, // signature
                20,         // version needed (2.0)
                0x0800,     // bit 11 UTF-8 filenames
                8,          // compression deflate
                0,          // mod time
                0x5421,     // mod date
                $crc,
                $compSize,
                $uncompSize,
                $nameLen,
                0           // extra len
            ) . $name;

            $zipData .= $localHeader . $compressed;

            // Central Directory Header (PK\x01\x02)
            $centralDir .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014B50, // signature
                20,         // version made by
                20,         // version needed
                0x0800,     // bit 11 UTF-8
                8,          // compression deflate
                0,          // mod time
                0x5421,     // mod date
                $crc,
                $compSize,
                $uncompSize,
                $nameLen,
                0,          // extra len
                0,          // comment len
                0,          // disk number
                0,          // internal attrs
                0,          // external attrs
                $offset
            ) . $name;

            $offset += strlen($localHeader) + $compSize;
            $count++;
        }

        // End of Central Directory Record (PK\x05\x06)
        $eocd = pack(
            'VvvvvVVv',
            0x06054B50,
            0,
            0,
            $count,
            $count,
            strlen($centralDir),
            $offset,
            0
        );

        return $zipData . $centralDir . $eocd;
    }

    /**
     * Génère un CSV UTF-8 (avec BOM pour ouverture directe dans Excel) séparé par points-virgules.
     */
    public static function buildCsvString(array $headers, array $rows): string {
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, $headers, ';');
        foreach ($rows as $r) {
            fputcsv($fh, array_map(fn($v) => (string)($v ?? ''), $r), ';');
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv !== false ? $csv : '';
    }
}
