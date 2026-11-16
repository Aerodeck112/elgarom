<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\Ai\Assistant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** «Integrare AI»: writing tools on the server. No figures are computed or changed here. */
class AiController extends Controller
{
    public function index(Request $request)
    {
        return $this->screen($request);
    }

    public function run(Request $request, Assistant $ai)
    {
        $data = $request->validate([
            'tool' => ['required', Rule::in(array_keys(Assistant::TOOLS))],
            'lang' => ['required', Rule::in(array_keys(Assistant::LANGS))],
            'quote_id' => 'nullable|integer|required_if:tool,email',
            'text' => 'nullable|string|max:20000|required_unless:tool,email',
        ]);
        $text = (string) ($data['text'] ?? '');
        if ($data['tool'] === 'email') {
            abort_unless($request->user()->can_('quotes.view'), 403);
            $text = $this->quoteBrief(Quote::with(['company', 'contact', 'entity', 'items'])->findOrFail($data['quote_id'])).($text !== '' ? "\nIndicații suplimentare: ".$text : '');
        }
        try {
            $result = $ai->run($data['tool'], $text, $data['lang'], $request->user()->id);
        } catch (\Throwable $e) {
            return $this->screen($request, error: $e->getMessage());
        }

        return $this->screen($request, $result);
    }

    private function screen(Request $request, ?array $result = null, ?string $error = null)
    {
        return view('admin.ai.index', [
            'configured' => Assistant::configured(), 'tools' => Assistant::TOOLS, 'langs' => Assistant::LANGS,
            'quotes' => $request->user()->can_('quotes.view') ? Quote::with('company:id,name')->where('is_current', true)->orderByDesc('id')->limit(60)->get(['id', 'number', 'revision', 'title', 'company_id']) : collect(),
            'usage' => (array) (setting('ai.usage.'.now()->format('Y-m')) ?: []),
            'result' => $result, 'error' => $error,
        ]);
    }

    /** What the email draft may know about a quote: public, client-facing data only. Internal costs and margins are never sent. */
    private function quoteBrief(Quote $q): string
    {
        $lines = [
            'Număr ofertă: '.$q->label(),
            'Emitent: '.($q->entity?->legal_name ?: $q->entity?->name),
            'Client: '.$q->company?->name,
            'Persoană de contact: '.($q->contact?->fullName() ?: 'nespecificat'),
            'Titlu: '.($q->title ?: 'nespecificat'),
            'Termen de execuție: '.($q->lead_time ?: 'nespecificat'),
            'Condiții de plată: '.($q->payment_terms ?: 'nespecificat'),
            'Valabilă până la: '.($q->valid_until?->format('d.m.Y') ?: 'nespecificat'),
        ];
        if (setting('ai.allow_confidential')) {
            $lines[] = 'Repere ofertate: '.$q->items->pluck('name')->filter()->take(20)->implode('; ');
        }

        return implode("\n", $lines);
    }
}
