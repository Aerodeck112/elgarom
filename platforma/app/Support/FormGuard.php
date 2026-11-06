<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Lightweight anti-spam for public forms, without third-party captchas:
 *  - honeypot field ("website") that humans never see;
 *  - encrypted render timestamp: forms submitted in under 3 s or older than 24 h are rejected;
 *  - per-IP rate limits (applied in controllers);
 *  - link-density check on free text.
 */
class FormGuard
{
    public static function token(): string
    {
        return Crypt::encryptString((string) time());
    }

    /** Returns null when the request looks human, or a user-facing reason otherwise. */
    public static function check(Request $request, string ...$textFields): ?string
    {
        if (trim((string) $request->input('website')) !== '') {
            return 'Trimiterea a fost blocată de filtrul anti-spam. Dacă sunteți o persoană, reîncărcați pagina și încercați din nou.';
        }
        try {
            $t = (int) Crypt::decryptString((string) $request->input('_t'));
        } catch (\Throwable) {
            return 'Formularul a expirat. Reîncărcați pagina și trimiteți din nou (datele completate au fost păstrate).';
        }
        $age = time() - $t;
        if ($age < 3) {
            return 'Formularul a fost trimis prea repede. Așteptați câteva secunde și încercați din nou.';
        }
        if ($age > 86400) {
            return 'Formularul a expirat. Trimiteți din nou (datele completate au fost păstrate).';
        }
        foreach ($textFields as $f) {
            $text = (string) $request->input($f);
            if (preg_match_all('#https?://#i', $text) > 3 || preg_match('/\[url=|<a\s+href/i', $text)) {
                return 'Mesajul conține prea multe linkuri. Eliminați linkurile și încercați din nou.';
            }
        }

        return null;
    }

    public static function ipHash(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }
}
