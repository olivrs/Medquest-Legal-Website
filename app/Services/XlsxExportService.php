<?php

namespace App\Services;

class XlsxExportService
{
    public static function generate(
        string $sheetTitle,
        string $reportTitle,
        string $subtitle,
        array $columns,
        array $rows,
        array $formMeta = [],
        ?string $logoPath = null,
        array $headerGroups = []
    ): string {
        $colCount = max(6, count($columns));
        $lastCol = self::colLetter($colCount - 1);
        $safeSheetName = self::xmlText(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $sheetTitle) ?: 'Export', 0, 31));

        $logoBytes = ($logoPath && is_file($logoPath)) ? @file_get_contents($logoPath) : false;
        $hasLogo = is_string($logoBytes) && $logoBytes !== '';

        $sheetXml = self::buildWorksheetXml($reportTitle, $subtitle, $columns, $rows, $formMeta, $colCount, $lastCol, $hasLogo, $headerGroups);
        $stylesXml = self::buildStylesXml();
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $safeSheetName . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . ($hasLogo ? '<Default Extension="png" ContentType="image/png"/>' : '')
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . ($hasLogo ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' : '')
            . '</Types>';

        $files = [
            '[Content_Types].xml'        => $contentTypes,
            '_rels/.rels'                => $rootRels,
            'xl/workbook.xml'            => $workbookXml,
            'xl/_rels/workbook.xml.rels' => $workbookRels,
            'xl/styles.xml'              => $stylesXml,
            'xl/worksheets/sheet1.xml'   => $sheetXml,
        ];

        if ($hasLogo) {
            $files['xl/media/image1.png'] = $logoBytes;
            $files['xl/drawings/drawing1.xml'] = self::buildDrawingXml();
            $files['xl/drawings/_rels/drawing1.xml.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image1.png"/>'
                . '</Relationships>';
            $files['xl/worksheets/_rels/sheet1.xml.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
                . '</Relationships>';
        }

        return self::buildZip($files);
    }

    protected static function buildDrawingXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<xdr:oneCellAnchor>'
            . '<xdr:from><xdr:col>0</xdr:col><xdr:colOff>142875</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>238125</xdr:rowOff></xdr:from>'
            . '<xdr:ext cx="1809750" cy="428625"/>'
            . '<xdr:pic>'
            . '<xdr:nvPicPr>'
            . '<xdr:cNvPr id="2" name="Medquest Logo"/>'
            . '<xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr>'
            . '</xdr:nvPicPr>'
            . '<xdr:blipFill>'
            . '<a:blip r:embed="rId1"/>'
            . '<a:stretch><a:fillRect/></a:stretch>'
            . '</xdr:blipFill>'
            . '<xdr:spPr>'
            . '<a:xfrm><a:off x="0" y="0"/><a:ext cx="1809750" cy="428625"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
            . '</xdr:spPr>'
            . '</xdr:pic>'
            . '<xdr:clientData/>'
            . '</xdr:oneCellAnchor>'
            . '</xdr:wsDr>';
    }

    protected static function buildWorksheetXml(
        string $reportTitle,
        string $subtitle,
        array $columns,
        array $rows,
        array $formMeta,
        int $colCount,
        string $lastCol,
        bool $hasLogo,
        array $headerGroups = []
    ): string {
        $colsXml = '<cols>';
        foreach ($columns as $i => $col) {
            $idx = $i + 1;
            $width = (float) ($col['width'] ?? 20);
            $colsXml .= '<col min="' . $idx . '" max="' . $idx . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $logoEndIdx = (($columns[1]['width'] ?? 0) < 15 && $colCount >= 8) ? 2 : 1;
        $logoEndCol = self::colLetter($logoEndIdx);
        $midStartIdx = $logoEndIdx + 1;
        $midStartCol = self::colLetter($midStartIdx);

        $lastColWidth = (float) ($columns[$colCount - 1]['width'] ?? 20);
        $legendStartIdx = ($lastColWidth < 16 && $colCount >= 9) ? ($colCount - 2) : ($colCount - 1);
        $legendStartCol = self::colLetter($legendStartIdx);
        $metaValIdx = $legendStartIdx - 1;
        $metaLabelIdx = $legendStartIdx - 2;
        $midEndIdx = max($midStartIdx, $legendStartIdx - 3);
        $midEndCol = self::colLetter($midEndIdx);

        $docNo = (string) ($formMeta['doc_no'] ?? 'MJG-FOR-LAC.12-001');
        $effDate = (string) ($formMeta['effective_date'] ?? '22 AUG 2024');
        $revNo = (string) ($formMeta['revision_no'] ?? '01');
        $pageNo = (string) ($formMeta['page_no'] ?? '1 of 1');

        $headerMetaRows = [
            1 => ['label' => 'Doc. No.',       'val' => ': ' . $docNo,   'legend' => 'Past Deadline / ED',   'legendStyle' => 12],
            2 => ['label' => 'Effective Date', 'val' => ': ' . $effDate, 'legend' => 'Near Deadline (<=90d)', 'legendStyle' => 13],
            3 => ['label' => 'Revision No.',   'val' => ': ' . $revNo,   'legend' => 'Approved (ACC)',       'legendStyle' => 14],
            4 => ['label' => 'Page No.',       'val' => ': ' . $pageNo,  'legend' => '',                     'legendStyle' => 11],
        ];

        $merges = [
            'A1:' . $logoEndCol . '4',
            $midStartCol . '1:' . $midEndCol . '2',
            $midStartCol . '3:' . $midEndCol . '4',
            'A5:' . $lastCol . '5',
        ];

        $rowsXml = '';
        for ($r = 1; $r <= 4; $r++) {
            $rowsXml .= '<row r="' . $r . '" ht="18" customHeight="1">';
            for ($c = 0; $c < $colCount; $c++) {
                $colL = self::colLetter($c);
                $ref = $colL . $r;
                if ($c <= $logoEndIdx) {
                    $text = ($r === 1 && $c === 0 && !$hasLogo) ? 'Medquest' : '';
                    $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t xml:space="preserve">' . self::xmlText($text) . '</t></is></c>';
                } elseif ($c <= $midEndIdx) {
                    if ($c === $midStartIdx && $r === 1) {
                        $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t xml:space="preserve">FORM</t></is></c>';
                    } elseif ($c === $midStartIdx && $r === 3) {
                        $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t xml:space="preserve">' . self::xmlText($reportTitle) . '</t></is></c>';
                    } else {
                        $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t xml:space="preserve"></t></is></c>';
                    }
                } elseif ($c === $metaLabelIdx) {
                    $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="11"><is><t xml:space="preserve">' . self::xmlText($headerMetaRows[$r]['label']) . '</t></is></c>';
                } elseif ($c === $metaValIdx) {
                    $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="11"><is><t xml:space="preserve">' . self::xmlText($headerMetaRows[$r]['val']) . '</t></is></c>';
                } else {
                    $legendText = ($c === $legendStartIdx) ? $headerMetaRows[$r]['legend'] : '';
                    $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="' . $headerMetaRows[$r]['legendStyle'] . '"><is><t xml:space="preserve">' . self::xmlText($legendText) . '</t></is></c>';
                }
            }
            if ($legendStartIdx < $colCount - 1) {
                $merges[] = $legendStartCol . $r . ':' . $lastCol . $r;
            }
            $rowsXml .= '</row>';
        }

        $rowsXml .= '<row r="5" ht="18" customHeight="1">'
            . '<c r="A5" t="inlineStr" s="2"><is><t xml:space="preserve">' . self::xmlText($subtitle) . '</t></is></c>'
            . '</row>';

        $groupSpanSum = 0;
        foreach ($headerGroups as $grp) {
            $groupSpanSum += max(1, (int) ($grp['colspan'] ?? 1));
        }
        $hasGroupedHeaders = !empty($headerGroups) && $groupSpanSum === count($columns);

        if ($hasGroupedHeaders) {
            $subHeaderCols = array_fill(0, count($columns), false);
            $rowsXml .= '<row r="6" ht="20" customHeight="1">';
            $c = 0;
            foreach ($headerGroups as $grp) {
                $span = max(1, (int) ($grp['colspan'] ?? 1));
                $gLabel = (string) ($grp['label'] ?? '');
                for ($k = 0; $k < $span; $k++) {
                    $colL = self::colLetter($c + $k);
                    $cellText = ($k === 0) ? $gLabel : '';
                    $rowsXml .= '<c r="' . $colL . '6" t="inlineStr" s="3"><is><t xml:space="preserve">' . self::xmlText($cellText) . '</t></is></c>';
                    if ($span > 1) {
                        $subHeaderCols[$c + $k] = true;
                    }
                }
                if ($span > 1) {
                    $merges[] = self::colLetter($c) . '6:' . self::colLetter($c + $span - 1) . '6';
                } else {
                    $merges[] = self::colLetter($c) . '6:' . self::colLetter($c) . '7';
                }
                $c += $span;
            }
            $rowsXml .= '</row>';

            $rowsXml .= '<row r="7" ht="20" customHeight="1">';
            foreach ($columns as $i => $col) {
                $ref = self::colLetter($i) . '7';
                $label = !empty($subHeaderCols[$i]) ? (string) ($col['label'] ?? '') : '';
                $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="3"><is><t xml:space="preserve">' . self::xmlText($label) . '</t></is></c>';
            }
            $rowsXml .= '</row>';

            $filterRow = 7;
            $rowNum = 8;
        } else {
            $rowsXml .= '<row r="6" ht="24" customHeight="1">';
            foreach ($columns as $i => $col) {
                $ref = self::colLetter($i) . '6';
                $label = (string) ($col['label'] ?? '');
                $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="3"><is><t xml:space="preserve">' . self::xmlText($label) . '</t></is></c>';
            }
            $rowsXml .= '</row>';

            $filterRow = 6;
            $rowNum = 7;
        }

        foreach ($rows as $rowIdx => $row) {
            $rowState = (string) ($row['row_state'] ?? 'normal');
            $cells = $row['cells'] ?? [];
            $maxLines = 1;

            foreach ($cells as $cIdx => $cell) {
                $text = is_array($cell) ? (string) ($cell['text'] ?? '') : (string) $cell;
                $colWidth = (float) ($columns[$cIdx]['width'] ?? 20);
                $lines = self::estimateLineCount($text, max(6, (int) floor($colWidth - 2)));
                if ($lines > $maxLines) {
                    $maxLines = $lines;
                }
            }

            $rowHeight = round(max(22, min(180, $maxLines * 14.2 + 8)), 1);
            $rowsXml .= '<row r="' . $rowNum . '" ht="' . $rowHeight . '" customHeight="1">';

            foreach ($columns as $cIdx => $col) {
                $cell = $cells[$cIdx] ?? '';
                $text = is_array($cell) ? (string) ($cell['text'] ?? '') : (string) $cell;
                $center = is_array($cell) && !empty($cell['center']);
                $bold = is_array($cell) && !empty($cell['bold']);
                $styleId = self::resolveDataCellStyle($rowState, $center, $bold);
                $ref = self::colLetter($cIdx) . $rowNum;
                $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="' . $styleId . '"><is><t xml:space="preserve">' . self::xmlText($text) . '</t></is></c>';
            }

            $rowsXml .= '</row>';
            $rowNum++;
        }

        $lastRow = max($filterRow, $filterRow + count($rows));
        $mergesXml = '<mergeCells count="' . count($merges) . '">';
        foreach ($merges as $mRef) {
            $mergesXml .= '<mergeCell ref="' . $mRef . '"/>';
        }
        $mergesXml .= '</mergeCells>';

        $drawingTag = $hasLogo ? '<drawing r:id="rId1"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="' . $filterRow . '" topLeftCell="A' . ($filterRow + 1) . '" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . $colsXml
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '<autoFilter ref="A' . $filterRow . ':' . $lastCol . $lastRow . '"/>'
            . $mergesXml
            . '<pageMargins left="0.35" right="0.35" top="0.45" bottom="0.45" header="0.2" footer="0.2"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . $drawingTag
            . '</worksheet>';
    }

    protected static function resolveDataCellStyle(string $rowState, bool $center, bool $bold): int
    {
        if ($rowState === 'expired') {
            return $center ? 16 : 6;
        }
        if ($rowState === 'warning') {
            return $center ? 17 : 7;
        }
        if ($rowState === 'approved') {
            return $center ? 18 : 8;
        }
        if ($center) {
            return 15;
        }
        return $bold ? 5 : 4;
    }

    protected static function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="5">'
            . '<font><sz val="9.5"/><color rgb="FF111827"/><name val="Arial"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF111827"/><name val="Arial"/></font>'
            . '<font><sz val="8.5"/><color rgb="FF374151"/><name val="Arial"/></font>'
            . '<font><b/><sz val="9.5"/><color rgb="FF111827"/><name val="Arial"/></font>'
            . '<font><b/><sz val="8.5"/><color rgb="FF111827"/><name val="Arial"/></font>'
            . '</fonts>'
            . '<fills count="7">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFCA5A5"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFEF08A"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFBBF7D0"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FF1E293B"/></left>'
            . '<right style="thin"><color rgb="FF1E293B"/></right>'
            . '<top style="thin"><color rgb="FF1E293B"/></top>'
            . '<bottom style="thin"><color rgb="FF1E293B"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="19">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    protected static function estimateLineCount(string $text, int $charsPerLine): int
    {
        if ($text === '') {
            return 1;
        }
        $lines = 0;
        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $text)) as $segment) {
            $len = mb_strlen($segment);
            $lines += max(1, (int) ceil($len / max(1, $charsPerLine)));
        }
        return $lines;
    }

    protected static function colLetter(int $index): string
    {
        $letter = '';
        $n = $index + 1;
        while ($n > 0) {
            $rem = ($n - 1) % 26;
            $letter = chr(65 + $rem) . $letter;
            $n = intdiv($n - 1, 26);
        }
        return $letter;
    }

    protected static function xmlText(string $text): string
    {
        $clean = str_replace(["\r\n", "\r"], "\n", $text);
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $clean) ?? $clean;
        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected static function buildZip(array $files): string
    {
        $localData = '';
        $centralDir = '';
        $offset = 0;
        $canDeflate = function_exists('gzdeflate');
        $count = count($files);

        foreach ($files as $name => $raw) {
            $uncompSize = strlen($raw);
            $crc = crc32($raw) & 0xFFFFFFFF;
            if ($canDeflate) {
                $comp = gzdeflate($raw, 6);
                $method = 8;
            } else {
                $comp = $raw;
                $method = 0;
            }
            $compSize = strlen($comp);
            $nameLen = strlen($name);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0x0800,
                $method,
                0,
                0x5421,
                $crc,
                $compSize,
                $uncompSize,
                $nameLen,
                0
            );
            $localData .= $localHeader . $name . $comp;

            $centralDir .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0x0800,
                $method,
                0,
                0x5421,
                $crc,
                $compSize,
                $uncompSize,
                $nameLen,
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $name;

            $offset = strlen($localData);
        }

        $eocd = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $count,
            $count,
            strlen($centralDir),
            $offset,
            0
        );

        return $localData . $centralDir . $eocd;
    }
}
