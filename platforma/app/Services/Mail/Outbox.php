<?php

namespace App\Services\Mail;

use App\Models\MailOutbox;
use App\Models\QuoteRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * Database-backed email outbox. Rows are written in the same transaction as the record
 * they belong to, then delivered right after the HTTP response, by cron
 * (php artisan elgarom:mail) or, without cron, opportunistically on admin visits.
 * Failures are retried with exponential backoff; nothing is lost when SMTP is down.
 */
class Outbox
{
    public static function configured(): bool
    {
        return Setting::has('smtp.host') && Setting::has('smtp.from_email');
    }

    public static function applyConfig(): void
    {
        $enc = setting('smtp.encryption', 'tls');
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'scheme' => $enc === 'ssl' ? 'smtps' : 'smtp',
            'host' => setting('smtp.host'),
            'port' => (int) setting('smtp.port', $enc === 'ssl' ? 465 : 587),
            'username' => setting('smtp.username'),
            'password' => setting('smtp.password'),
            'timeout' => (int) setting('smtp.timeout', 15),
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            'require_tls' => $enc === 'tls',
        ]);
        Config::set('mail.from', ['address' => setting('smtp.from_email'), 'name' => setting('smtp.from_name', setting('company.name', 'ELGAROM'))]);
        app('mail.manager')->purge('smtp');
    }

    public function sendFor(string $relatedType, int $relatedId): void
    {
        // foreach, not each(): a failed delivery must not stop the remaining emails.
        foreach (MailOutbox::where('related_type', $relatedType)->where('related_id', $relatedId)->where('status', 'pending')->orderBy('id')->get() as $m) {
            $this->deliver($m);
        }
    }

    /** Process due messages. Returns [sent, failed]. */
    public function process(int $limit = 20): array
    {
        $lock = Cache::lock('mail-outbox', 120);
        if (! $lock->get()) {
            return [0, 0];
        }
        try {
            MailOutbox::where('status', 'sending')->where('updated_at', '<', now()->subMinutes(10))
                ->update(['status' => 'pending', 'next_attempt_at' => now()]);
            $sent = $failed = 0;
            $due = MailOutbox::where('status', 'pending')
                ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
                ->orderBy('id')->limit($limit)->get();
            foreach ($due as $m) {
                $this->deliver($m) ? $sent++ : $failed++;
            }
            Cache::put('mail-outbox:last-run', now()->timestamp, 86400);

            return [$sent, $failed];
        } finally {
            $lock->release();
        }
    }

    /** Called from admin requests when no cron is configured. */
    public function processIfStale(): void
    {
        if (now()->timestamp - (int) Cache::get('mail-outbox:last-run', 0) > 300) {
            $this->process(10);
        }
    }

    public function deliver(MailOutbox $m): bool
    {
        // Claim the row atomically so two processes never send the same email.
        $claimed = MailOutbox::whereKey($m->id)->where('status', 'pending')->update(['status' => 'sending', 'updated_at' => now()]);
        if (! $claimed) {
            return false;
        }
        $m->refresh();
        if (! self::configured()) {
            return $this->fail($m, 'SMTP nu este configurat (Administrare → SMTP și notificări).', false);
        }
        try {
            self::applyConfig();
            $html = $this->render($m);
            Mail::mailer('smtp')->html($html, function ($msg) use ($m) {
                $msg->to($m->to_email, $m->to_name)->subject($m->subject);
                if ($m->template === 'team_new_quote' && ($q = QuoteRequest::find($m->payload['quote_id'] ?? 0))) {
                    $msg->replyTo($q->email, $q->name);
                }
            });
            $m->update(['status' => 'sent', 'sent_at' => now(), 'attempts' => $m->attempts + 1, 'last_error' => null]);

            return true;
        } catch (\Throwable $e) {
            return $this->fail($m, mb_substr($this->clean($e->getMessage()), 0, 1000), true);
        }
    }

    private function fail(MailOutbox $m, string $error, bool $countAttempt): bool
    {
        $attempts = $m->attempts + ($countAttempt ? 1 : 0);
        $max = (int) config('elgarom.mail.max_attempts', 8);
        $final = $attempts >= $max;
        $m->update([
            'status' => $final ? 'failed' : 'pending',
            'attempts' => $attempts,
            'last_error' => $error,
            // 1, 2, 4, 8 … minutes, capped at 6 hours. Unconfigured SMTP is re-checked every 15 minutes.
            'next_attempt_at' => $countAttempt ? now()->addMinutes(min(360, 2 ** max(0, $attempts - 1))) : now()->addMinutes(15),
        ]);
        logger()->warning('Email netrimis', ['outbox_id' => $m->id, 'attempts' => $attempts, 'error' => $error]);

        return false;
    }

    /** Remove credentials that some transports echo back in error messages. */
    private function clean(string $msg): string
    {
        $pw = (string) setting('smtp.password');

        return $pw !== '' ? str_replace($pw, '[ascuns]', $msg) : $msg;
    }

    private function inLocale(?string $locale, \Closure $fn): string
    {
        $prev = app()->getLocale();
        app()->setLocale($locale && \App\Support\Locales::isEnabled($locale) ? $locale : $prev);
        try {
            return $fn();
        } finally {
            app()->setLocale($prev);
        }
    }

    public function render(MailOutbox $m): string
    {
        $quote = QuoteRequest::with('files')->find($m->payload['quote_id'] ?? 0);

        return match ($m->template) {
            'team_new_quote' => view('emails.team-quote', ['q' => $quote])->render(),
            'client_confirmation' => $this->inLocale($quote?->locale, fn () => view('emails.client-confirmation', ['q' => $quote])->render()),
            'test' => view('emails.test')->render(),
            default => throw new \RuntimeException('Șablon email necunoscut: '.$m->template),
        };
    }
}
