<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Excel export of a quote. Internal costs are included only when asked for by a user allowed to see them. */
final class QuoteExcel
{
    public function build(Quote $quote, bool $withCosts): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('Oferta');
        $rows = [
            ['Ofertă', $quote->label()], ['Emitent', $quote->entity->legal_name ?: $quote->entity->name], ['Client', $quote->company->name],
            ['Data emiterii', $quote->issued_at?->format('d.m.Y') ?? 'ciornă'], ['Valabilă până la', $quote->valid_until?->format('d.m.Y') ?? ''], ['Monedă', $quote->currency], [],
        ];
        $head = ['Nr.', 'Cod', 'Denumire', 'Cantitate', 'U.M.', 'Preț unitar', 'Discount %', 'Total', 'Opțional'];
        if ($withCosts) {
            $head[] = 'Cost unitar intern';
        }
        $rows[] = $head;
        $headRow = count($rows);
        foreach ($quote->items as $i) {
            $row = [$i->position, $i->code, $i->name, $i->quantity, $i->unit, $i->unit_price, $i->discount_percent, $i->line_total, $i->is_optional ? 'da' : ''];
            if ($withCosts) {
                $row[] = $i->unit_cost;
            }
            $rows[] = $row;
        }
        $rows[] = [];
        $rows[] = ['', '', '', '', '', '', 'Subtotal', $quote->subtotal];
        $rows[] = ['', '', '', '', '', '', 'Discount %', $quote->discount_percent];
        $rows[] = ['', '', '', '', '', '', 'Total net', $quote->total_net];
        $rows[] = ['', '', '', '', '', '', 'TVA '.$quote->vat_rate.' %', $quote->vat_amount];
        $rows[] = ['', '', '', '', '', '', 'Total', $quote->total_gross];
        $sheet->fromArray($rows, null, 'A1', true);
        $sheet->getStyle('A'.$headRow.':J'.$headRow)->getFont()->setBold(true);
        $sheet->getStyle('A1:A6')->getFont()->setBold(true);
        foreach (['F', 'H', 'J'] as $col) {
            $sheet->getStyle($col.($headRow + 1).':'.$col.count($rows))->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'oferta');
        (new Xlsx($book))->save($tmp);
        $bytes = file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }
}
