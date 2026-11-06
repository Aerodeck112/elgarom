<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    /** A small list of very common passwords rejected regardless of length. */
    private const COMMON = ['parola123456', 'password1234', 'elgarom12345', 'administrator', 'qwertyuiop12', '123456789012', 'admin1234567', 'elgarom2026!', 'Parola123456'];

    public static function rules(?string $email = null): array
    {
        return [
            'required', 'string', 'max:200', 'confirmed',
            Password::min(12)->letters()->mixedCase()->numbers(),
            function ($attr, $value, $fail) use ($email) {
                $v = strtolower((string) $value);
                if (in_array($v, array_map('strtolower', self::COMMON), true)) {
                    $fail('Parola este prea comună.');
                }
                if ($email && str_contains($v, strtolower(strtok($email, '@')))) {
                    $fail('Parola nu poate conține numele contului de email.');
                }
            },
        ];
    }

    public static function messages(): array
    {
        return [
            'password.confirmed' => 'Confirmarea parolei nu coincide.',
            'password.min' => 'Parola trebuie să aibă cel puțin 12 caractere.',
            'password.letters' => 'Parola trebuie să conțină litere.',
            'password.mixed' => 'Parola trebuie să conțină litere mari și mici.',
            'password.numbers' => 'Parola trebuie să conțină cifre.',
        ];
    }
}
