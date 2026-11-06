<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/** TOTP (RFC 6238) with replay protection and single-use, hashed recovery codes. */
class TwoFactor
{
    public function __construct(private Google2FA $g = new Google2FA) {}

    public function newSecret(): string
    {
        return $this->g->generateSecretKey(32);
    }

    public function qrSvg(User $user, string $secret): string
    {
        $issuer = setting('company.name', 'ELGAROM').' admin';
        $url = $this->g->getQRCodeUrl($issuer, $user->email, $secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd));

        return $writer->writeString($url);
    }

    /** Verifies a code for a secret not yet saved (setup). */
    public function verifySecret(string $secret, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);

        return strlen($code) === 6 && $this->g->verifyKey($secret, $code, 1);
    }

    /**
     * Verifies a code for the user and rejects re-use of the same or an older time step.
     * $requireNewerThanLogin forces a code generated after the last used one (fresh code for critical actions).
     */
    public function verifyUser(User $user, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6 || ! $user->two_factor_secret) {
            return false;
        }
        $step = $this->g->verifyKeyNewer($user->two_factor_secret, $code, $user->last_totp_step, 1);
        if ($step === false) {
            return false;
        }
        $user->forceFill(['last_totp_step' => $step === true ? $this->g->getTimestamp() : $step])->save();

        return true;
    }

    /** @return string[] plain codes, shown once */
    public function generateRecoveryCodes(User $user): array
    {
        $plain = [];
        for ($i = 0; $i < 10; $i++) {
            $plain[] = Str::lower(Str::random(5).'-'.Str::random(5));
        }
        $user->forceFill(['two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $plain)])->save();

        return $plain;
    }

    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::lower(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];
        foreach ($codes as $i => $hash) {
            if (Hash::check($code, $hash)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    public function remainingRecoveryCodes(User $user): int
    {
        return count($user->two_factor_recovery_codes ?? []);
    }
}
