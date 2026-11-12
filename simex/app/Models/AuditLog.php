<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['data' => 'array', 'created_at' => 'datetime'];

    /** Keys never written to the audit log. */
    private const REDACT = ['password', 'password_confirmation', 'current_password', 'api_key', 'secret', 'token', 'smtp_password', 'client_secret', 'code', 'totp'];

    public static function record(string $action, $subject = null, array $data = [], $userId = null): void
    {
        $req = request();
        try {
            self::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'ip' => $req?->ip(),
                'user_agent' => substr((string) $req?->userAgent(), 0, 255),
                'data' => self::redact($data),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function redact(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_string($k)) {
                foreach (self::REDACT as $r) {
                    if (str_contains(strtolower($k), $r)) {
                        $data[$k] = '[ascuns]';

                        continue 2;
                    }
                }
            }
            if (is_array($v)) {
                $data[$k] = self::redact($v);
            }
        }

        return $data;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
