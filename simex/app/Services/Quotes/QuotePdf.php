<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use Dompdf\Dompdf;
use Dompdf\Options;

/** Client-facing PDF. An issued quote is printed from its frozen snapshot; a draft is watermarked. */
final class QuotePdf
{
    public function __construct(private readonly QuoteService $quotes) {}

    public function data(Quote $quote): array
    {
        $snapshot = $quote->snapshot ?? $this->quotes->snapshot($quote);
        unset($snapshot['internal']);
        $logo = null;
        $path = $snapshot['issuer']['logo_path'] ?? null;
        if ($path && is_file(storage_path('app/private/'.$path)) && ! str_ends_with(strtolower($path), '.svg')) {
            $mime = mime_content_type(storage_path('app/private/'.$path)) ?: 'image/png';
            $logo = 'data:'.$mime.';base64,'.base64_encode(file_get_contents(storage_path('app/private/'.$path)));
        }

        return ['s' => $snapshot, 'logo' => $logo, 'draft' => $quote->snapshot === null, 'lang' => $snapshot['quote']['language'] ?? 'ro'];
    }

    public function render(Quote $quote): string
    {
        $data = $this->data($quote);
        $previous = app()->getLocale();
        app()->setLocale($data['lang']);
        try {
            $html = view('pdf.quote', $data)->render();
        } finally {
            app()->setLocale($previous);
        }
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', storage_path('app/private'));
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $canvas->page_text(520, 812, '{PAGE_NUM} / {PAGE_COUNT}', $pdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [0.45, 0.48, 0.52]);

        return $pdf->output();
    }
}
