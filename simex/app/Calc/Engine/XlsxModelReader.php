<?php

namespace App\Calc\Engine;

use SimpleXMLElement;
use ZipArchive;

/**
 * Reads an .xlsx workbook into a plain array model: constants, formulas (text), cached results,
 * defined names, data validations and unlocked (input) cells. Macros are never read or executed:
 * only the XML parts listed below are opened.
 *
 * Model shape:
 *   sheets[i] = [name, state, protected, cells[row][col], unlocked[row][col]=true, st[row][col]=styleIndex,
 *                merges[], validations[], maxRow, maxCol];  styles[styleIndex] = [fmt, bold, fill, unlocked]
 *   a cell is either a constant (float|string|bool) or an array:
 *     ['f' => 'A1+B1', 'v' => cached, 'a' => true (array formula), 'ref' => [r1,c1,r2,c2]]
 *     ['sf' => [masterRow, masterCol], 'v' => cached]    (shared formula, same AST shifted)
 *     ['af' => [masterRow, masterCol], 'v' => cached]    (inside a multi-cell array formula)
 *   a cached error is stored as ['#' => '#N/A'] in 'v'.
 */
final class XlsxModelReader
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    public const MAX_PART_BYTES = 80 * 1024 * 1024;

    /** @param ?string $inputFill ARGB fill colour that marks input cells in workbooks which do not use cell protection */
    public function read(string $path, ?string $inputFill = null): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new CalcException('Fișierul nu este un workbook .xlsx valid.');
        }
        try {
            if ($zip->locateName('xl/vbaProject.bin') !== false) {
                // .xlsm content: formulas are still read, the macro project is ignored and reported.
                $hasMacros = true;
            }
            $wb = $this->xml($zip, 'xl/workbook.xml');
            $rels = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
            $targets = [];
            foreach ($rels->Relationship as $rel) {
                $targets[(string) $rel['Id']] = (string) $rel['Target'];
            }
            $strings = $this->sharedStrings($zip);
            $styles = $this->styles($zip);

            $sheets = [];
            foreach ($wb->sheets->sheet as $sheet) {
                $rid = (string) $sheet->attributes(self::NS_REL)['id'];
                $target = $targets[$rid] ?? null;
                if ($target === null) {
                    continue;
                }
                $partName = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
                $data = $this->sheet($this->xml($zip, $partName), $strings, $styles, $inputFill);
                $data['name'] = (string) $sheet['name'];
                $data['state'] = (string) ($sheet['state'] ?? 'visible') ?: 'visible';
                $sheets[] = $data;
            }

            $names = [];
            if (isset($wb->definedNames)) {
                foreach ($wb->definedNames->definedName as $dn) {
                    $name = strtoupper((string) $dn['name']);
                    if (str_starts_with($name, '_XLNM.')) {
                        continue;
                    }
                    $names[$name] = (string) $dn;
                }
            }

            $externalLinks = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (str_starts_with($zip->getNameIndex($i), 'xl/externalLinks/externalLink')) {
                    $externalLinks++;
                }
            }

            return [
                'sheets' => $sheets,
                'names' => $names,
                'styles' => $styles,
                'date1904' => isset($wb->workbookPr) && (string) $wb->workbookPr['date1904'] === '1',
                'meta' => [
                    'has_macros' => $hasMacros ?? false,
                    'external_links' => $externalLinks,
                    'sha256' => hash_file('sha256', $path),
                    'modified' => $this->modified($zip),
                ],
            ];
        } finally {
            $zip->close();
        }
    }

    /** Last-saved date (Y-m-d) from the document properties; TODAY() is pinned to it when validating cached results. */
    private function modified(ZipArchive $zip): ?string
    {
        $core = $zip->getFromName('docProps/core.xml');
        if ($core !== false && preg_match('/<dcterms:modified[^>]*>(\d{4}-\d{2}-\d{2})/', $core, $m)) {
            return $m[1];
        }

        return null;
    }

    private function xml(ZipArchive $zip, string $name): SimpleXMLElement
    {
        $stat = $zip->statName($name);
        if ($stat === false) {
            throw new CalcException("Lipsește partea «{$name}» din workbook.");
        }
        if ($stat['size'] > self::MAX_PART_BYTES) {
            throw new CalcException("Partea «{$name}» este prea mare.");
        }
        $xml = @simplexml_load_string($zip->getFromName($name), SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        if ($xml === false) {
            throw new CalcException("Partea «{$name}» nu este XML valid.");
        }

        return $xml;
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }
        $out = [];
        foreach ($this->xml($zip, 'xl/sharedStrings.xml')->si as $si) {
            if (isset($si->t)) {
                $text = (string) $si->t;
            } else {
                $text = '';
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
            }
            $out[] = self::unescape($text);
        }

        return $out;
    }

    /**
     * Cell styles reduced to what the calculators need: number format, bold, solid fill colour and
     * the "unlocked" protection flag (how the workbooks mark input cells).
     *
     * @return list<array{fmt:?string,bold:bool,fill:?string,unlocked:bool}>
     */
    private function styles(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/styles.xml') === false) {
            return [];
        }
        $xml = $this->xml($zip, 'xl/styles.xml');
        $builtin = [1 => '0', 2 => '0.00', 3 => '#,##0', 4 => '#,##0.00', 9 => '0%', 10 => '0.00%', 14 => 'dd.mm.yyyy', 22 => 'dd.mm.yyyy hh:mm'];
        $formats = [];
        if (isset($xml->numFmts)) {
            foreach ($xml->numFmts->numFmt as $nf) {
                $formats[(int) $nf['numFmtId']] = (string) $nf['formatCode'];
            }
        }
        $bold = [];
        if (isset($xml->fonts)) {
            foreach ($xml->fonts->font as $font) {
                $bold[] = isset($font->b) && (string) $font->b['val'] !== '0';
            }
        }
        $fills = [];
        if (isset($xml->fills)) {
            foreach ($xml->fills->fill as $fill) {
                $rgb = null;
                if (isset($fill->patternFill) && (string) $fill->patternFill['patternType'] === 'solid' && isset($fill->patternFill->fgColor['rgb'])) {
                    $rgb = strtoupper((string) $fill->patternFill->fgColor['rgb']);
                }
                $fills[] = $rgb;
            }
        }
        $out = [];
        if (isset($xml->cellXfs)) {
            foreach ($xml->cellXfs->xf as $xf) {
                $id = (int) $xf['numFmtId'];
                $out[] = [
                    'fmt' => $formats[$id] ?? $builtin[$id] ?? null,
                    'bold' => $bold[(int) $xf['fontId']] ?? false,
                    'fill' => $fills[(int) $xf['fillId']] ?? null,
                    'unlocked' => isset($xf->protection) && (string) $xf->protection['locked'] === '0',
                ];
            }
        }

        return $out;
    }

    private function sheet(SimpleXMLElement $xml, array $strings, array $styles, ?string $inputFill): array
    {
        $cells = [];
        $unlocked = [];
        $st = [];
        $shared = [];
        $maxRow = 0;
        $maxCol = 0;
        foreach ($xml->sheetData->row as $row) {
            foreach ($row->c as $c) {
                [$r, $col] = self::addr((string) $c['r']);
                $xf = (int) $c['s'];
                $style = $styles[$xf] ?? null;
                if ($style !== null && ($style['unlocked'] || ($inputFill !== null && $style['fill'] === $inputFill))) {
                    $unlocked[$r][$col] = true;
                }
                if ($xf > 0) {
                    $st[$r][$col] = $xf;
                }
                $type = (string) $c['t'];
                $hasV = isset($c->v);
                $raw = $hasV ? (string) $c->v : null;
                $value = null;
                if ($type === 'inlineStr') {
                    $value = self::unescape((string) $c->is->t);
                } elseif ($hasV) {
                    $value = match ($type) {
                        's' => $strings[(int) $raw] ?? '',
                        'str' => self::unescape($raw),
                        'b' => $raw === '1',
                        'e' => ['#' => $raw],
                        default => (float) $raw,
                    };
                }
                if (isset($c->f)) {
                    $f = $c->f;
                    $ft = (string) $f['t'];
                    $text = (string) $f;
                    if ($ft === 'shared') {
                        $si = (string) $f['si'];
                        if ($text !== '') {
                            $shared[$si] = [$r, $col];
                            $cell = ['f' => $text, 'v' => $value];
                        } elseif (isset($shared[$si])) {
                            $cell = ['sf' => $shared[$si], 'v' => $value];
                        } else {
                            throw new CalcException('Formulă partajată fără sursă la '.$c['r']);
                        }
                    } elseif ($ft === 'array') {
                        $cell = ['f' => $text, 'v' => $value, 'a' => true];
                        $ref = (string) $f['ref'];
                        if ($ref !== '' && str_contains($ref, ':')) {
                            [$a, $b] = explode(':', $ref);
                            [$r1, $c1] = self::addr($a);
                            [$r2, $c2] = self::addr($b);
                            $cell['ref'] = [$r1, $c1, $r2, $c2];
                        }
                    } elseif ($ft === 'dataTable') {
                        throw new CalcException('Tabelele de date (What-If) nu sunt suportate: '.$c['r']);
                    } else {
                        $cell = ['f' => $text, 'v' => $value];
                    }
                    $cells[$r][$col] = $cell;
                } elseif ($value !== null) {
                    $cells[$r][$col] = is_array($value) ? ['err' => $value['#']] : $value;
                } else {
                    continue;
                }
                $maxRow = max($maxRow, $r);
                $maxCol = max($maxCol, $col);
            }
        }
        // Cells covered by a multi-cell array formula point to their master.
        foreach ($cells as $r => $rowCells) {
            foreach ($rowCells as $col => $cell) {
                if (is_array($cell) && isset($cell['ref'])) {
                    [$r1, $c1, $r2, $c2] = $cell['ref'];
                    for ($rr = $r1; $rr <= $r2; $rr++) {
                        for ($cc = $c1; $cc <= $c2; $cc++) {
                            if ($rr !== $r || $cc !== $col) {
                                $prev = $cells[$rr][$cc] ?? null;
                                $cells[$rr][$cc] = ['af' => [$r, $col], 'v' => is_array($prev) ? ($prev['v'] ?? null) : $prev];
                            }
                        }
                    }
                }
            }
        }

        $validations = [];
        if (isset($xml->dataValidations)) {
            foreach ($xml->dataValidations->dataValidation as $dv) {
                $validations[] = [
                    'type' => (string) $dv['type'],
                    'operator' => (string) $dv['operator'],
                    'sqref' => (string) $dv['sqref'],
                    'formula1' => isset($dv->formula1) ? (string) $dv->formula1 : null,
                    'formula2' => isset($dv->formula2) ? (string) $dv->formula2 : null,
                    'allowBlank' => (string) $dv['allowBlank'] === '1',
                ];
            }
        }

        // Validations that point to other sheets are stored in an Excel 2010 extension list.
        if (isset($xml->extLst)) {
            foreach ($xml->extLst->ext as $ext) {
                $x14 = $ext->children('http://schemas.microsoft.com/office/spreadsheetml/2009/9/main');
                if (! isset($x14->dataValidations)) {
                    continue;
                }
                foreach ($x14->dataValidations->dataValidation as $dv) {
                    $xm = 'http://schemas.microsoft.com/office/excel/2006/main';
                    $attrs = $dv->attributes();
                    $f1 = isset($dv->formula1) ? (string) $dv->formula1->children($xm)->f : null;
                    $f2 = isset($dv->formula2) ? (string) $dv->formula2->children($xm)->f : null;
                    $validations[] = [
                        'type' => (string) $attrs['type'],
                        'operator' => (string) $attrs['operator'],
                        'sqref' => (string) $dv->children($xm)->sqref,
                        'formula1' => $f1 === '' ? null : $f1,
                        'formula2' => $f2 === '' ? null : $f2,
                        'allowBlank' => (string) $attrs['allowBlank'] === '1',
                    ];
                }
            }
        }

        $merges = [];
        if (isset($xml->mergeCells)) {
            foreach ($xml->mergeCells->mergeCell as $mc) {
                $merges[] = (string) $mc['ref'];
            }
        }

        return [
            'cells' => $cells,
            'unlocked' => $unlocked,
            'st' => $st,
            'merges' => $merges,
            'validations' => $validations,
            'protected' => isset($xml->sheetProtection) && (string) $xml->sheetProtection['sheet'] === '1',
            'maxRow' => $maxRow,
            'maxCol' => $maxCol,
        ];
    }

    /** "AB12" → [12, 28] */
    public static function addr(string $a1): array
    {
        if (! preg_match('/^\$?([A-Za-z]{1,3})\$?(\d+)$/', $a1, $m)) {
            throw new CalcException("Adresă de celulă invalidă: {$a1}");
        }

        return [(int) $m[2], FormulaParser::colToNum($m[1])];
    }

    private static function unescape(string $s): string
    {
        if (! str_contains($s, '_x')) {
            return $s;
        }

        return preg_replace_callback('/_x([0-9A-Fa-f]{4})_/', fn ($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $s);
    }
}
