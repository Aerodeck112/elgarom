<?php

namespace App\Http\Controllers;

use App\Models\QuoteRequest;
use App\Services\Mail\Outbox;
use App\Services\QuoteService;
use App\Services\UploadInspector;
use App\Support\FormGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

use function Illuminate\Support\defer;

class QuoteController extends Controller
{
    public function __construct(private QuoteService $quotes, private UploadInspector $inspector) {}

    public function store(Request $request)
    {
        // Random key for the temporary upload folder, kept in the session (survives session id regeneration).
        $sid = $request->session()->get('quote_stash_key') ?? tap(bin2hex(random_bytes(16)), fn ($k) => $request->session()->put('quote_stash_key', $k));
        $back = fn () => redirect()->to(url('/solicita-oferta').'#formular')->withInput($request->except(['files', '_t', 'website']));

        if ($reason = FormGuard::check($request, 'description')) {
            return $back()->with('quote_failed', $reason);
        }
        $key = 'quote:'.FormGuard::ipHash($request);
        if (RateLimiter::tooManyAttempts($key, 6)) {
            $min = (int) ceil(RateLimiter::availableIn($key) / 60);

            return $back()->with('quote_failed', 'Ați trimis mai multe cereri într-un timp scurt. Încercați din nou peste '.$min.' minute sau sunați-ne.');
        }

        $exts = config('elgarom.uploads.quote_extensions');
        $maxKb = config('elgarom.uploads.quote_max_kb');
        $stash = $request->session()->get('quote_stash', []);
        $maxFiles = config('elgarom.uploads.quote_max_files') - count($stash);

        $v = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\/.\s\-]{6,40}$/'],
            'city' => ['nullable', 'string', 'max:120'],
            'part_type' => ['nullable', 'in:'.implode(',', array_keys(QuoteRequest::PART_TYPES))],
            'material' => ['nullable', 'string', 'max:80'],
            'process' => ['nullable', 'in:'.implode(',', array_keys(QuoteRequest::PROCESSES))],
            'description' => ['required', 'string', 'min:10', 'max:6000'],
            'quantity' => ['nullable', 'string', 'max:60'],
            'weight' => ['nullable', 'string', 'max:60'],
            'dimensions' => ['nullable', 'string', 'max:120'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today', 'before:+5 years'],
            'deadline_note' => ['nullable', 'string', 'max:120'],
            'files' => ['nullable', 'array', 'max:'.max(0, $maxFiles)],
            'files.*' => ['file', 'max:'.$maxKb],
            'privacy' => ['accepted'],
            'marketing' => ['nullable', 'boolean'],
            'source' => ['nullable', 'in:formular,asistent_ai'],
        ], [
            'name.required' => 'Completați numele.',
            'email.required' => 'Completați adresa de email.',
            'email.email' => 'Adresa de email nu pare validă.',
            'phone.regex' => 'Numărul de telefon poate conține doar cifre, spații și + ( ) - . /',
            'description.required' => 'Descrieți pe scurt piesa și cerințele.',
            'description.min' => 'Descrierea trebuie să aibă cel puțin 10 caractere.',
            'deadline.after_or_equal' => 'Termenul dorit nu poate fi în trecut.',
            'files.max' => 'Puteți atașa cel mult '.config('elgarom.uploads.quote_max_files').' fișiere în total.',
            'files.*.max' => 'Fiecare fișier poate avea cel mult '.round($maxKb / 1024).' MB.',
            'privacy.accepted' => 'Pentru a trimite cererea, confirmați că ați citit Politica de confidențialitate.',
        ]);

        $files = array_values(array_filter((array) $request->file('files', [])));
        $v->after(function ($validator) use ($files, $exts) {
            foreach ($files as $i => $f) {
                if ($p = $this->inspector->problem($f, $exts)) {
                    $validator->errors()->add('files.'.$i, $p);
                }
            }
        });

        if ($v->fails()) {
            $stash = array_merge($stash, $this->quotes->stash($sid, array_filter($files, fn ($f) => ! $this->inspector->problem($f, $exts) && $f->getSize() <= $maxKb * 1024)));
            $request->session()->put('quote_stash', array_slice($stash, 0, config('elgarom.uploads.quote_max_files')));

            return $back()->withErrors($v, 'oferta');
        }
        RateLimiter::hit($key, 3600);

        $data = $v->validated();
        try {
            $quote = $this->quotes->create([
                'kind' => 'oferta',
                'name' => $data['name'], 'company' => $data['company'] ?? null, 'email' => $data['email'],
                'phone' => $data['phone'] ?? null, 'city' => $data['city'] ?? null,
                'part_type' => $data['part_type'] ?? null, 'material' => $data['material'] ?? null, 'process' => $data['process'] ?? null,
                'description' => $data['description'], 'quantity' => $data['quantity'] ?? null, 'weight' => $data['weight'] ?? null,
                'dimensions' => $data['dimensions'] ?? null, 'deadline' => $data['deadline'] ?? null, 'deadline_note' => $data['deadline_note'] ?? null,
                'marketing_consent' => (bool) ($data['marketing'] ?? false), 'privacy_accepted_at' => now(),
                'source' => $data['source'] ?? 'formular', 'ip_hash' => FormGuard::ipHash($request),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255), 'locale' => app()->getLocale(),
                'meta' => ['policy_version' => (int) setting('cookies.policy_version', 1)],
            ], $files, $stash, $sid);
        } catch (\Throwable $e) {
            report($e);

            return $back()->with('quote_failed', 'Cererea nu a putut fi salvată din cauza unei erori tehnice și NU a fost trimisă. Datele completate au fost păstrate – încercați din nou peste câteva minute sau sunați la '.(setting('company.phones', [])[0]['number'] ?? '').'.');
        }

        $request->session()->forget(['quote_stash', 'quote_stash_key']);
        if ($quote->source === 'asistent_ai' && ($pid = $request->session()->get('ai.conversation'))) {
            \App\Models\AiConversation::where('public_id', $pid)->update(['led_to_quote' => true]);
        }
        $this->quotes->clearStash($sid);
        defer(fn () => app(Outbox::class)->sendFor('quote', $quote->id));

        return redirect()->route('quote.sent')->with('quote_ref', $quote->reference)->with('quote_kind', 'oferta');
    }

    public function message(Request $request)
    {
        $back = fn () => redirect()->to(url()->previous().'#mesaj')->withInput($request->except(['_t', 'website']));
        if ($reason = FormGuard::check($request, 'message')) {
            return $back()->withErrors(['message' => $reason], 'mesaj');
        }
        $key = 'msg:'.FormGuard::ipHash($request);
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return $back()->withErrors(['message' => 'Prea multe mesaje într-un timp scurt. Încercați din nou mai târziu sau sunați-ne.'], 'mesaj');
        }
        $v = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\/.\s\-]{6,40}$/'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'privacy' => ['accepted'],
        ], [
            'name.required' => 'Completați numele.', 'email.required' => 'Completați adresa de email.',
            'email.email' => 'Adresa de email nu pare validă.', 'message.required' => 'Scrieți mesajul.',
            'message.min' => 'Mesajul trebuie să aibă cel puțin 10 caractere.',
            'privacy.accepted' => 'Confirmați că ați citit Politica de confidențialitate.',
        ]);
        if ($v->fails()) {
            return $back()->withErrors($v, 'mesaj');
        }
        RateLimiter::hit($key, 3600);
        $d = $v->validated();
        try {
            $quote = $this->quotes->create([
                'kind' => 'contact', 'name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null,
                'description' => $d['message'], 'privacy_accepted_at' => now(), 'source' => 'formular_scurt',
                'ip_hash' => FormGuard::ipHash($request), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'locale' => app()->getLocale(),
            ], [], [], $request->session()->getId());
        } catch (\Throwable $e) {
            report($e);

            return $back()->withErrors(['message' => 'Mesajul nu a putut fi salvat și NU a fost trimis. Încercați din nou sau sunați-ne.'], 'mesaj');
        }
        defer(fn () => app(Outbox::class)->sendFor('quote', $quote->id));

        return redirect()->route('quote.sent')->with('quote_ref', $quote->reference)->with('quote_kind', 'contact');
    }

    public function sent(Request $request)
    {
        $ref = $request->session()->get('quote_ref');

        return response()->view('pages.quote-sent', [
            'ref' => $ref,
            'kind' => $request->session()->get('quote_kind', 'oferta'),
            'mailQueued' => true,
            'metaTitle' => 'Cerere înregistrată | '.setting('seo.site_name', 'ELGAROM'),
            'noindex' => true,
        ]);
    }
}
