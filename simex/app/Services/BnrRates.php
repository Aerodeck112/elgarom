<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** The EUR reference rate published by the National Bank of Romania (BNR), used as the default rate of new quotes. */
final class BnrRates
{
    public const URL = 'https://www.bnr.ro/nbrfxrates.xml';

    /** @return array{rate: float, date: string} */
    public function fetch(string $currency = 'EUR'): array
    {
        $res = Http::timeout(15)->accept('application/xml')->get(self::URL);
        if (! $res->ok()) {
            throw new RuntimeException('BNR nu a răspuns (HTTP '.$res->status().').');
        }

        return self::parse($res->body(), $currency);
    }

    /** @return array{rate: float, date: string} */
    public static function parse(string $xml, string $currency = 'EUR'): array
    {
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, options: LIBXML_NONET);
        libxml_use_internal_errors($prev);
        if ($doc === false) {
            throw new RuntimeException('Răspunsul BNR nu este un XML valid.');
        }
        $doc->registerXPathNamespace('b', 'http://www.bnr.ro/xsd');
        $cube = $doc->xpath('//b:Cube')[0] ?? null;
        $rate = $doc->xpath('//b:Cube/b:Rate[@currency="'.$currency.'"]')[0] ?? null;
        if ($cube === null || $rate === null) {
            throw new RuntimeException('Cursul '.$currency.' lipsește din răspunsul BNR.');
        }
        $multiplier = (float) ($rate['multiplier'] ?? 1) ?: 1.0;
        $value = round((float) $rate / $multiplier, 6);
        $date = (string) $cube['date'];
        if ($value <= 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new RuntimeException('Curs BNR invalid.');
        }

        return ['rate' => $value, 'date' => $date];
    }

    /** Stores the rate as the default for new quotes (quotes already created keep their own frozen rate). */
    public function apply(?int $userId = null): array
    {
        $fx = $this->fetch();
        $old = setting('quotes.exchange_rate');
        Setting::put('quotes.exchange_rate', (string) $fx['rate']);
        Setting::put('quotes.exchange_rate_date', $fx['date']);
        if ((string) $old !== (string) $fx['rate']) {
            AuditLog::record('curs.bnr', null, ['curs' => $fx['rate'], 'data' => $fx['date'], 'anterior' => $old]);
        }

        return $fx;
    }
}
