<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\QuoteEvent;
use App\Models\QuoteFile;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuotesController extends Controller
{
    private function filtered(Request $r)
    {
        $q = QuoteRequest::query()->with('assignee')->withCount('files')->latest();
        if ($s = $r->query('status')) {
            $q->where('status', $s);
        }
        if ($k = $r->query('kind')) {
            $q->where('kind', $k);
        }
        if ($a = $r->query('assignee')) {
            $a === 'none' ? $q->whereNull('assignee_id') : $q->where('assignee_id', (int) $a);
        }
        if ($from = $r->query('from')) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to = $r->query('to')) {
            $q->whereDate('created_at', '<=', $to);
        }
        if ($term = trim((string) $r->query('q'))) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $q->where(fn ($w) => $w->where('reference', 'like', $like)->orWhere('name', 'like', $like)->orWhere('company', 'like', $like)->orWhere('email', 'like', $like)->orWhere('description', 'like', $like));
        }

        return $q;
    }

    public function index(Request $request)
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);

        return view('admin.quotes.index', [
            'items' => $this->filtered($request)->paginate(30)->withQueryString(),
            'users' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, QuoteRequest $quote)
    {
        AuditLog::record('cerere.vizualizata', $quote);

        return view('admin.quotes.show', [
            'q' => $quote->load(['files', 'events.user', 'assignee']),
            'users' => User::where('is_active', true)->whereIn('role', ['admin', 'sales'])->orderBy('name')->get(),
            'mails' => \App\Models\MailOutbox::where('related_type', 'quote')->where('related_id', $quote->id)->get(),
        ]);
    }

    public function update(Request $request, QuoteRequest $quote)
    {
        $d = $request->validate([
            'status' => ['required', Rule::in(array_keys(QuoteRequest::STATUSES))],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
        ]);
        $changes = [];
        if ($d['status'] !== $quote->status) {
            $changes[] = 'Stare: '.$quote->statusLabel().' → '.(QuoteRequest::STATUSES[$d['status']]);
        }
        if ((int) ($d['assignee_id'] ?? 0) !== (int) $quote->assignee_id) {
            $changes[] = 'Responsabil: '.(User::find($d['assignee_id'])?->name ?? 'nimeni');
        }
        if ($changes) {
            $quote->update($d);
            QuoteEvent::create(['quote_request_id' => $quote->id, 'user_id' => $request->user()->id, 'type' => 'modificare', 'body' => implode('; ', $changes)]);
            AuditLog::record('cerere.modificata', $quote, ['modificari' => $changes]);
        }

        return back()->with('status', $changes ? 'Cererea a fost actualizată.' : 'Nicio modificare.');
    }

    public function note(Request $request, QuoteRequest $quote)
    {
        $d = $request->validate(['body' => 'required|string|max:4000']);
        QuoteEvent::create(['quote_request_id' => $quote->id, 'user_id' => $request->user()->id, 'type' => 'nota', 'body' => $d['body']]);
        AuditLog::record('cerere.nota', $quote);

        return back()->with('status', 'Nota a fost adăugată.');
    }

    public function file(Request $request, QuoteRequest $quote, QuoteFile $file)
    {
        abort_unless($file->quote_request_id === $quote->id, 404);
        $path = storage_path('app/private/'.$file->path);
        abort_unless(is_file($path), 404);
        AuditLog::record('cerere.fisier_descarcat', $quote, ['fisier' => $file->original_name]);
        $inline = $request->boolean('view') && in_array($file->mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true);

        return response()->file($path, [
            'Content-Type' => $inline ? $file->mime : 'application/octet-stream',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.addcslashes($file->original_name, '"\\').'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->get();
        AuditLog::record('cerere.export', null, ['randuri' => $rows->count(), 'filtre' => $request->query()]);
        $cols = ['reference' => 'Număr', 'created_at' => 'Data', 'kind' => 'Tip', 'status' => 'Stare', 'name' => 'Nume', 'company' => 'Companie', 'email' => 'Email', 'phone' => 'Telefon', 'city' => 'Localitate', 'part_type' => 'Tip piese', 'material' => 'Material', 'process' => 'Execuție', 'quantity' => 'Cantitate', 'weight' => 'Greutate', 'dimensions' => 'Dimensiuni', 'deadline' => 'Termen', 'description' => 'Descriere', 'marketing_consent' => 'Acord marketing', 'source' => 'Sursa', 'assignee' => 'Responsabil', 'files_count' => 'Fișiere'];
        $esc = function ($v) {
            $v = (string) $v;
            // Neutralise spreadsheet formulas (CSV injection).
            if ($v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                $v = "'".$v;
            }

            return '"'.str_replace('"', '""', $v).'"';
        };

        return response()->streamDownload(function () use ($rows, $cols, $esc) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF".implode(';', array_map($esc, $cols))."\n");
            foreach ($rows as $r) {
                $line = [];
                foreach (array_keys($cols) as $c) {
                    $line[] = $esc(match ($c) {
                        'created_at' => $r->created_at->format('Y-m-d H:i'),
                        'status' => $r->statusLabel(),
                        'deadline' => $r->deadline?->format('Y-m-d'),
                        'assignee' => $r->assignee?->name,
                        'marketing_consent' => $r->marketing_consent ? 'da' : 'nu',
                        'part_type' => QuoteRequest::PART_TYPES[$r->part_type] ?? $r->part_type,
                        'process' => QuoteRequest::PROCESSES[$r->process] ?? $r->process,
                        default => $r->{$c},
                    });
                }
                fwrite($out, implode(';', $line)."\n");
            }
            fclose($out);
        }, 'cereri-elgarom-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(Request $request, QuoteRequest $quote)
    {
        foreach ($quote->files as $f) {
            @unlink(storage_path('app/private/'.$f->path));
        }
        @rmdir(storage_path('app/private/quotes/'.$quote->created_at->format('Y').'/'.$quote->id));
        AuditLog::record('cerere.stearsa', $quote, ['reference' => $quote->reference]);
        $quote->delete();

        return redirect()->route('admin.quotes.index')->with('status', 'Cererea și fișierele ei au fost șterse definitiv.');
    }
}
