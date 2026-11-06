<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\MailOutbox;
use App\Models\QuoteEvent;
use App\Models\QuoteFile;
use App\Models\QuoteRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Persists quote requests. Order of operations guarantees that a confirmation is only
 * shown after the request, its files and its outgoing emails are committed together.
 * Email delivery happens afterwards and failures are retried from the outbox.
 */
class QuoteService
{
    public function __construct(private UploadInspector $inspector) {}

    private function stashDir(string $sessionId): string
    {
        return storage_path('app/private/tmp/'.hash('sha256', $sessionId));
    }

    /** Keep valid uploads across a failed validation so the visitor does not re-upload them. */
    public function stash(string $sessionId, array $files): array
    {
        $dir = $this->stashDir($sessionId);
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $out = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || $this->inspector->problem($file, config('elgarom.uploads.quote_extensions'))) {
                continue;
            }
            if ($file->getSize() > config('elgarom.uploads.quote_max_kb') * 1024) {
                continue;
            }
            $token = Str::random(32);
            $file->move($dir, $token);
            $out[] = ['token' => $token, 'name' => $this->inspector->name($file), 'size' => filesize($dir.'/'.$token), 'ext' => strtolower($file->getClientOriginalExtension())];
        }

        return $out;
    }

    public function create(array $data, array $uploads, array $stash, string $sessionId): QuoteRequest
    {
        $stored = [];
        try {
            $quote = DB::transaction(function () use ($data, $uploads, $stash, $sessionId, &$stored) {
                $quote = QuoteRequest::createWithReference($data);
                $dir = 'quotes/'.now()->format('Y').'/'.$quote->id;
                $abs = storage_path('app/private/'.$dir);
                if (! is_dir($abs) && ! mkdir($abs, 0700, true) && ! is_dir($abs)) {
                    throw new RuntimeException('Directorul pentru fișiere nu poate fi creat.');
                }
                foreach ($uploads as $file) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $name = Str::random(40).'.'.$ext;
                    $mime = $this->inspector->detectedMime($file->getRealPath());
                    $orig = $this->inspector->name($file);
                    $file->move($abs, $name);
                    $stored[] = $abs.'/'.$name;
                    $this->fileRow($quote, $dir.'/'.$name, $orig, $mime);
                }
                foreach ($stash as $s) {
                    $src = $this->stashDir($sessionId).'/'.basename($s['token']);
                    if (! is_file($src)) {
                        continue;
                    }
                    $name = Str::random(40).'.'.$s['ext'];
                    if (! rename($src, $abs.'/'.$name)) {
                        throw new RuntimeException('Fișierul păstrat nu a putut fi mutat.');
                    }
                    $stored[] = $abs.'/'.$name;
                    $this->fileRow($quote, $dir.'/'.$name, $s['name'], $this->inspector->detectedMime($abs.'/'.$name));
                }
                QuoteEvent::create(['quote_request_id' => $quote->id, 'type' => 'creata', 'body' => 'Cerere primită prin '.$quote->source.'.']);
                $this->queueEmails($quote);

                return $quote;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $p) {
                @unlink($p);
            }
            throw $e;
        }
        AuditLog::record('cerere.primita', $quote, ['reference' => $quote->reference, 'kind' => $quote->kind], null);

        return $quote;
    }

    private function fileRow(QuoteRequest $quote, string $rel, string $orig, string $mime): void
    {
        $abs = storage_path('app/private/'.$rel);
        QuoteFile::create([
            'quote_request_id' => $quote->id, 'path' => $rel, 'original_name' => $orig,
            'mime' => $mime, 'size' => filesize($abs), 'sha256' => hash_file('sha256', $abs),
        ]);
    }

    private function queueEmails(QuoteRequest $quote): void
    {
        $label = $quote->kind === 'contact' ? 'Mesaj nou' : 'Cerere de ofertă nouă';
        foreach ((array) setting('notifications.recipients', []) as $to) {
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                MailOutbox::create([
                    'template' => 'team_new_quote', 'to_email' => $to,
                    'subject' => $label.' '.$quote->reference.($quote->company ? ' – '.$quote->company : ''),
                    'payload' => ['quote_id' => $quote->id], 'related_type' => 'quote', 'related_id' => $quote->id,
                    'next_attempt_at' => now(),
                ]);
            }
        }
        MailOutbox::create([
            'template' => 'client_confirmation', 'to_email' => $quote->email, 'to_name' => $quote->name,
            'subject' => __($quote->kind === 'contact' ? 'Am primit mesajul dumneavoastră' : 'Am primit cererea de ofertă', [], $quote->locale).' – '.$quote->reference,
            'payload' => ['quote_id' => $quote->id], 'related_type' => 'quote', 'related_id' => $quote->id,
            'next_attempt_at' => now(),
        ]);
    }

    public function clearStash(string $sessionId): void
    {
        $dir = $this->stashDir($sessionId);
        if (is_dir($dir)) {
            foreach (glob($dir.'/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($dir);
        }
    }
}
