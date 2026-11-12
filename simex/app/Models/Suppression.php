<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Global exclusion list. Only a hash of the address is kept, so the list itself holds no personal data in clear. */
class Suppression extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public static function hash(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    public static function has(?string $email): bool
    {
        return filled($email) && self::where('email_hash', self::hash($email))->exists();
    }

    public static function add(string $email, string $reason = 'dezabonare', ?string $source = null): void
    {
        self::firstOrCreate(['email_hash' => self::hash($email)], ['reason' => $reason, 'source' => $source]);
    }
}
