<?php

namespace App\Calc;

use App\Calc\Engine\CalcException;
use App\Calc\Engine\ExcelError;
use App\Calc\Engine\Workbook;
use App\Calc\Engine\XlsxModelReader;

/**
 * Checks an uploaded workbook before it may be used for costing: it must be the workbook the
 * definition expects, use only supported functions, and every formula recomputed by the engine
 * must equal the result Excel stored in the file.
 */
final class ModelValidator
{
    public const TOLERANCE = 1e-9;

    public function validate(string $path, ModelDefinition $def): array
    {
        $report = ['ok' => false, 'errors' => [], 'warnings' => [], 'formulas' => 0, 'mismatches' => 0, 'samples' => [], 'sheets' => [], 'sha256' => hash_file('sha256', $path)];
        try {
            $model = (new XlsxModelReader)->read($path, $def->input_fill);
        } catch (CalcException $e) {
            $report['errors'][] = $e->getMessage();

            return $report;
        }
        $wb = Workbook::fromModel($model);
        if ($model['meta']['modified']) {
            $wb->today = Workbook::dateSerial(strtotime($model['meta']['modified'].' 12:00:00 UTC'));
        }
        if ($model['meta']['has_macros']) {
            $report['warnings'][] = 'Fișierul conține macro-uri (VBA). Ele nu sunt citite și nu sunt executate.';
        }
        if ($model['meta']['external_links'] > 0) {
            $report['errors'][] = 'Workbook-ul are legături către alte fișiere ('.$model['meta']['external_links'].'). Valorile lor nu pot fi verificate.';
        }
        foreach ($def->sheets() as $name => $_) {
            if (! $wb->hasSheet($name)) {
                $report['errors'][] = "Lipsește foaia «{$name}» așteptată de calculator.";
            }
        }
        if ($report['errors'] === []) {
            foreach ($def->requires ?? [] as $ref => $needle) {
                $pos = strrpos($ref, '!');
                $text = Display::text($wb->get(substr($ref, 0, $pos), substr($ref, $pos + 1)));
                if (! str_contains($text, $needle)) {
                    $report['errors'][] = "Celula {$ref} nu conține «{$needle}». Fișierul nu pare a fi «{$def->file_hint}» sau structura lui s-a schimbat.";
                }
            }
            foreach ($def->variants() as $variant) {
                foreach (array_merge([$variant['status']], array_values($variant['outputs'] ?? [])) as $ref) {
                    $pos = strrpos($ref, '!');
                    if (! $wb->isFormula(substr($ref, 0, $pos), substr($ref, $pos + 1))) {
                        $report['warnings'][] = "Celula de rezultat {$ref} nu mai conține o formulă.";
                    }
                }
            }
        }
        $unsupported = $wb->unsupportedFunctions();
        if ($unsupported !== []) {
            $report['errors'][] = 'Funcții Excel nesuportate de motorul de calcul: '.implode(', ', $unsupported).'.';
        }
        foreach ($model['sheets'] as $sheet) {
            $report['sheets'][$sheet['name']] = ['formulas' => 0, 'mismatches' => 0, 'state' => $sheet['state']];
        }
        foreach ($wb->formulaCells() as [$sheet, $addr, $cached]) {
            $report['formulas']++;
            $report['sheets'][$sheet]['formulas']++;
            try {
                $v = $wb->get($sheet, $addr);
            } catch (CalcException $e) {
                $v = ExcelError::of('#CALC!');
                $note = $e->getMessage();
            }
            if (is_array($cached)) {
                $cached = ExcelError::of($cached['#']);
            }
            if ($cached === null) {
                $same = $v === '';
            } elseif (is_float($cached) && is_float($v)) {
                $same = abs($cached - $v) <= self::TOLERANCE * max(1.0, abs($cached));
            } else {
                $same = $cached === $v;
            }
            if (! $same) {
                $report['mismatches']++;
                $report['sheets'][$sheet]['mismatches']++;
                if (count($report['samples']) < 25) {
                    $report['samples'][] = ['cell' => $sheet.'!'.$addr, 'excel' => Display::text($cached), 'engine' => Display::text($v), 'note' => $note ?? null];
                }
            }
            unset($note);
        }
        if ($report['mismatches'] > 0) {
            $report['errors'][] = $report['mismatches'].' formule dau alt rezultat decât cel salvat de Excel.';
        }
        if ($report['formulas'] === 0) {
            $report['errors'][] = 'Workbook-ul nu conține formule.';
        }
        $report['ok'] = $report['errors'] === [];

        return $report;
    }
}
