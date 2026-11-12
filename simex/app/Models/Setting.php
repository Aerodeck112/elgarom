<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value settings persisted in the database. Values are JSON encoded;
 * secrets (API keys, SMTP password, OAuth tokens) are stored encrypted with APP_KEY.
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_encrypted' => 'boolean'];

    private static ?array $memo = null;

    public static function allValues(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }
        try {
            if (! Schema::hasTable('settings')) {
                return self::$memo = [];
            }
        } catch (\Throwable) {
            return [];
        }

        return self::$memo = Cache::rememberForever('settings.all', function () {
            $out = [];
            foreach (self::all() as $row) {
                $raw = $row->value;
                if ($row->is_encrypted && $raw !== null) {
                    // Encrypted values are not cached in plain form: decrypted on demand.
                    $out[$row->key] = ['__enc' => $raw];

                    continue;
                }
                $out[$row->key] = $raw === null ? null : json_decode($raw, true);
            }

            return $out;
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::allValues();
        if (! array_key_exists($key, $all)) {
            return $default;
        }
        $v = $all[$key];
        if (is_array($v) && array_key_exists('__enc', $v) && count($v) === 1) {
            try {
                return json_decode(Crypt::decryptString($v['__enc']), true);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $v ?? $default;
    }

    public static function put(string $key, mixed $value, bool $encrypted = false): void
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        self::updateOrCreate(['key' => $key], [
            'value' => $encrypted ? Crypt::encryptString($json) : $json,
            'is_encrypted' => $encrypted,
        ]);
        self::flush();
    }

    public static function forget(string $key): void
    {
        self::where('key', $key)->delete();
        self::flush();
    }

    public static function has(string $key): bool
    {
        return self::get($key) !== null && self::get($key) !== '';
    }

    public static function flush(): void
    {
        self::$memo = null;
        Cache::forget('settings.all');
    }

    /** Group helper: all keys "prefix.*" as an array without the prefix. */
    public static function group(string $prefix): array
    {
        $out = [];
        foreach (array_keys(self::allValues()) as $k) {
            if (str_starts_with($k, $prefix.'.')) {
                $out[substr($k, strlen($prefix) + 1)] = self::get($k);
            }
        }

        return $out;
    }
}
