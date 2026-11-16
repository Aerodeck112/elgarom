<?php

namespace App\Services\Ai;

use Anthropic\Client;
use App\Models\AuditLog;
use App\Models\Setting;
use RuntimeException;

/**
 * Writing tools backed by Claude. Runs only on the server with the key stored (encrypted) in the settings;
 * the key never reaches the browser. The tools draft text: they never compute or change prices, costs or
 * quantities, and their output is only shown to the user, who copies what they want.
 */
final class Assistant
{
    public const TOOLS = [
        'email' => ['Email de însoțire a ofertei', 'Redactează emailul cu care trimiteți oferta (PDF atașat), în limba clientului.'],
        'rfq' => ['Rezumat cerere de ofertă', 'Din emailul sau textul primit de la client extrage reperele, materialele, cantitățile, termenele și întrebările de clarificat.'],
        'translate' => ['Traducere text tehnic', 'Traduce sau reformulează un text tehnic-comercial în română, engleză sau germană.'],
    ];

    public const LANGS = ['ro' => 'română', 'en' => 'engleză', 'de' => 'germană'];

    public static function configured(): bool
    {
        return filled(setting('ai.api_key'));
    }

    /** @return array{text: string, input: int, output: int} */
    public function run(string $tool, string $text, string $lang, ?int $userId = null): array
    {
        if (! self::configured()) {
            throw new RuntimeException('Cheia API Claude nu este configurată (Administrare → API Claude).');
        }
        if (! isset(self::TOOLS[$tool])) {
            throw new RuntimeException('Instrument necunoscut.');
        }
        $language = self::LANGS[$lang] ?? 'română';
        $system = 'Ești asistentul de redactare al departamentului comercial al unui grup industrial din România (ștanțe, matrițe, prelucrări CNC, debitare laser, zincare). '
            .'Scrii doar text. Nu calculezi, nu estimezi și nu modifici prețuri, costuri, cantități, termene sau alte cifre: le preiei exact cum apar în textul primit, iar dacă lipsesc nu le inventezi. '
            .'Nu inventa nume, adrese sau angajamente. Răspunde concis, fără introduceri.';
        $prompt = match ($tool) {
            'email' => "Redactează în limba {$language} un email scurt și profesionist de însoțire a ofertei atașate (PDF). Folosește doar datele de mai jos; nu menționa valori pe care nu le conțin.\n\nDate ofertă:\n{$text}",
            'rfq' => "Analizează cererea de ofertă de mai jos și răspunde în limba {$language} cu: 1) client și persoană de contact (dacă apar), 2) tabel cu reperele cerute: denumire, material, cantitate, termen, 3) cerințe tehnice și documente atașate menționate, 4) întrebări de clarificat cu clientul. Marchează cu «nespecificat» ce lipsește.\n\nCererea:\n{$text}",
            'translate' => "Tradu textul de mai jos în limba {$language}, păstrând terminologia tehnică, unitățile de măsură și toate cifrele exact cum sunt.\n\nText:\n{$text}",
        };

        $client = new Client(apiKey: (string) setting('ai.api_key'), requestOptions: ['timeout' => (float) config('simex.ai.timeout', 45), 'maxRetries' => 1]);
        $message = $client->messages->create(
            model: (string) setting('ai.model', array_key_first(config('simex.ai.models'))),
            maxTokens: 4000,
            outputConfig: ['effort' => 'low'],
            system: $system,
            messages: [['role' => 'user', 'content' => $prompt]],
        );
        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('Modelul a refuzat cererea. Reformulați textul și încercați din nou.');
        }
        $out = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $out .= $block->text;
            }
        }
        $in = (int) $message->usage->inputTokens;
        $outTok = (int) $message->usage->outputTokens;
        $key = 'ai.usage.'.now()->format('Y-m');
        $usage = (array) (setting($key) ?: []);
        Setting::put($key, ['input' => ($usage['input'] ?? 0) + $in, 'output' => ($usage['output'] ?? 0) + $outTok, 'runs' => ($usage['runs'] ?? 0) + 1]);
        // Only metadata is logged, never the text sent or received.
        AuditLog::record('ai.instrument', null, ['instrument' => $tool, 'limba' => $lang, 'tokeni_intrare' => $in, 'tokeni_iesire' => $outTok]);

        return ['text' => trim($out), 'input' => $in, 'output' => $outTok];
    }
}
