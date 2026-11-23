<?php

namespace App\Services;

class XlsxExportService
{
    public static function generate(
        string $sheetTitle,
        string $reportTitle,
        string $subtitle,
        array $columns,
        array $rows
    ): string {
        $colCount = max(1, count($columns));
        $lastCol = self::colLetter($colCount - 1);
        $safeSheetName = self::xmlText(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $sheetTitle) ?: 'Export', 0, 31));

        $sheetXml = self::buildWorksheetXml($reportTitle, $subtitle, $columns, $rows, $lastCol);
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
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        return self::buildZip([
            '[Content_Types].xml'        => $contentTypes,
            '_rels/.rels'                => $rootRels,
            'xl/workbook.xml'            => $workbookXml,
            'xl/_rels/workbook.xml.rels' => $workbookRels,
            'xl/styles.xml'              => $stylesXml,
            'xl/worksheets/sheet1.xml'   => $sheetXml,
        ]);
    }

    protected static function buildWorksheetXml(
        string $reportTitle,
        string $subtitle,
        array $columns,
        array $rows,
        string $lastCol
    ): string {
        $colsXml = '<cols>';
        foreach ($columns as $i => $col) {
            $idx = $i + 1;
            $width = (float) ($col['width'] ?? 22);
            $colsXml .= '<col min="' . $idx . '" max="' . $idx . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $rowsXml = '';
        $rowsXml .= '<row r="1" ht="24" customHeight="1">'
            . '<c r="A1" t="inlineStr" s="1"><is><t xml:space="preserve">' . self::xmlText($reportTitle) . '</t></is></c>'
            . '</row>';
        $rowsXml .= '<row r="2" ht="18" customHeight="1">'
            . '<c r="A2" t="inlineStr" s="2"><is><t xml:space="preserve">' . self::xmlText($subtitle) . '</t></is></c>'
            . '</row>';
        $rowsXml .= '<row r="3" ht="8" customHeight="1"/>';

        $rowsXml .= '<row r="4" ht="26" customHeight="1">';
        foreach ($columns as $i => $col) {
            $ref = self::colLetter($i) . '4';
            $label = (string) ($col['label'] ?? '');
            $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="3"><is><t xml:space="preserve">' . self::xmlText($label) . '</t></is></c>';
        }
        $rowsXml .= '</row>';

        $rowNum = 5;
        foreach ($rows as $rowIdx => $row) {
            $rowState = (string) ($row['row_state'] ?? 'normal');
            $zebra = ($rowIdx % 2 === 1);
            $cells = $row['cells'] ?? [];
            $maxLines = 1;

            foreach ($cells as $cIdx => $cell) {
                $text = is_array($cell) ? (string) ($cell['text'] ?? '') : (string) $cell;
                $colWidth = (float) ($columns[$cIdx]['width'] ?? 22);
                $lines = self::estimateLineCount($text, max(10, (int) floor($colWidth - 2)));
                if ($lines > $maxLines) {
                    $maxLines = $lines;
                }
            }

            $rowHeight = round(max(22, min(180, $maxLines * 14.5 + 8)), 1);
            $rowsXml .= '<row r="' . $rowNum . '" ht="' . $rowHeight . '" customHeight="1">';

            foreach ($columns as $cIdx => $col) {
                $cell = $cells[$cIdx] ?? '';
                $text = is_array($cell) ? (string) ($cell['text'] ?? '') : (string) $cell;
                $bold = is_array($cell) && !empty($cell['bold']);
                $tone = is_array($cell) ? (string) ($cell['tone'] ?? '') : '';
                $styleId = self::resolveCellStyle($rowState, $zebra, $bold, $tone);
                $ref = self::colLetter($cIdx) . $rowNum;
                $rowsXml .= '<c r="' . $ref . '" t="inlineStr" s="' . $styleId . '"><is><t xml:space="preserve">' . self::xmlText($text) . '</t></is></c>';
            }

            $rowsXml .= '</row>';
            $rowNum++;
        }

        $lastRow = max(4, 4 + count($rows));
        $mergesXml = '';
        if (count($columns) > 1) {
            $mergesXml = '<mergeCells count="2">'
                . '<mergeCell ref="A1:' . $lastCol . '1"/>'
                . '<mergeCell ref="A2:' . $lastCol . '2"/>'
                . '</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . $colsXml
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '<autoFilter ref="A4:' . $lastCol . $lastRow . '"/>'
            . $mergesXml
            . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';
    }

    protected static function resolveCellStyle(string $rowState, bool $zebra, bool $bold, string $tone): int
    {
        if ($tone === 'danger') {
            return $rowState === 'expired' ? 6 : 13;
        }
        if ($tone === 'warning') {
            return $rowState === 'warning' ? 7 : 14;
        }
        if ($tone === 'success') {
            return 12;
        }
        if ($rowState === 'expired') {
            return $bold ? 15 : 10;
        }
        if ($rowState === 'warning') {
            return $bold ? 16 : 11;
        }
        if ($bold) {
            return $zebra ? 9 : 8;
        }
        return $zebra ? 5 : 4;
    }

    protected static function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="8">'
            . '<font><sz val="10"/><color rgb="FF1E293B"/><name val="Segoe UI"/></font>'
            . '<font><b/><sz val="13"/><color rgb="FF1E293B"/><name val="Segoe UI"/></font>'
            . '<font><sz val="9"/><color rgb="FF475569"/><name val="Segoe UI"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FF1E293B"/><name val="Segoe UI"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFB91C1C"/><name val="Segoe UI"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFB45309"/><name val="Segoe UI"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FF047857"/><name val="Segoe UI"/></font>'
            . '</fonts>'
            . '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFEF2F2"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFBEB"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="17">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="6" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="7" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="6" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
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
