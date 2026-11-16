<?php

namespace App\Http\Controllers\Admin;

use App\Calc\ModelRegistry;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Calculation;
use App\Models\Company;
use App\Models\Entity;
use App\Models\Quote;
use App\Models\Rfq;
use App\Models\ToolProject;
use App\Services\Quotes\QuoteExcel;
use App\Services\Quotes\QuotePdf;
use App\Services\Quotes\QuoteService;
use App\Support\Vocab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class QuoteController extends Controller
{
    public function __construct(private readonly QuoteService $quotes) {}

    public function index(Request $request)
    {
        $q = Quote::query()->with(['company', 'entity', 'owner'])->where('is_current', true);
        if (($term = trim((string) $request->query('q'))) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('title', 'like', $like)->orWhereHas('company', fn ($c) => $c->where('name', 'like', $like)));
        }
        if (isset(Vocab::QUOTE_STATUSES[$request->query('status')])) {
            $q->where('status', $request->query('status'));
        }
        if ($request->filled('entity')) {
            $q->where('entity_id', (int) $request->query('entity'));
        }

        $currency = setting('quotes.currency', 'EUR');
        $cur = fn () => Quote::where('is_current', true)->where('currency', $currency);
        $kpi = [
            'total' => Quote::where('is_current', true)->count(),
            'month' => Quote::where('is_current', true)->where('created_at', '>=', now()->startOfMonth())->count(),
            'open_value' => (float) $cur()->whereIn('status', ['aprobata', 'trimisa', 'negociere'])->sum('total_net'),
            'won_value' => (float) $cur()->where('status', 'acceptata')->sum('total_net'),
            'currency' => $currency,
        ];

        return view('admin.quotes.index', ['rows' => $q->orderByDesc('id')->paginate(30)->withQueryString(), 'entities' => Entity::orderBy('sort')->pluck('name', 'id'), 'kpi' => $kpi,
            'counts' => Quote::where('is_current', true)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status')]);
    }

    /** Removes a quote and all its revisions from the register (administrator only; kept as soft-deleted for the audit). */
    public function destroy(Request $request, Quote $quote)
    {
        DB::transaction(function () use ($quote) {
            $all = Quote::where('number', $quote->number)->get();
            $calcIds = \App\Models\QuoteItem::whereIn('quote_id', $all->pluck('id'))->whereNotNull('calculation_id')->pluck('calculation_id')->unique();
            $all->each->delete();
            // A calculation stays locked only while another issued quote still uses it.
            $stillUsed = \App\Models\QuoteItem::whereIn('calculation_id', $calcIds)->whereHas('quote', fn ($q) => $q->whereNotNull('issued_at'))->pluck('calculation_id');
            Calculation::whereIn('id', $calcIds->diff($stillUsed))->update(['locked_at' => null]);
        });
        AuditLog::record('oferta.stearsa', $quote, ['numar' => $quote->number, 'status' => $quote->status, 'total' => $quote->total_net]);

        return redirect()->route('admin.quotes.index')->with('status', 'Oferta '.$quote->number.' a fost ștearsă din registru.');
    }

    public function create(Request $request)
    {
        $rfq = $request->integer('rfq') ? Rfq::find($request->integer('rfq')) : null;
        $quote = new Quote([
            'company_id' => $rfq?->company_id ?? ($request->integer('company') ?: null), 'contact_id' => $rfq?->contact_id, 'rfq_id' => $rfq?->id, 'entity_id' => $rfq?->entity_id,
            'title' => $rfq?->title, 'type' => 'piese', 'language' => $rfq?->company?->language ?? 'ro', 'currency' => setting('quotes.currency', 'EUR'),
            'vat_rate' => (float) setting('quotes.vat_rate', 0), 'exchange_rate' => setting('quotes.exchange_rate'), 'exchange_rate_date' => setting('quotes.exchange_rate_date'), 'discount_percent' => 0,
        ]);

        return view('admin.quotes.form', ['quote' => $quote, 'items' => collect()] + $this->lists($quote));
    }

    public function store(Request $request)
    {
        [$data, $items] = $this->validated($request);
        $quote = DB::transaction(function () use ($data, $items, $request) {
            $quote = $this->quotes->create($data, $request->user());
            $quote->items()->createMany($items);
            $quote->events()->create(['user_id' => $request->user()->id, 'type' => 'creata']);

            return $this->quotes->recalculate($quote);
        });
        AuditLog::record('oferta.creata', $quote);

        return redirect()->route('admin.quotes.show', $quote)->with('status', 'Oferta '.$quote->number.' a fost creată.');
    }

    public function show(Quote $quote)
    {
        $quote->load(['entity', 'company', 'contact', 'owner', 'approver', 'items.calculation', 'events.user', 'rfq']);

        return view('admin.quotes.show', [
            'quote' => $quote, 'revisions' => $quote->revisions()->get(), 'reasons' => $this->quotes->approvalReasons($quote),
            'calculations' => $quote->isEditable() ? Calculation::where('state', '!=', 'block')->where(fn ($w) => $w->where('company_id', $quote->company_id)->orWhereNull('company_id'))->latest('id')->limit(100)->get() : collect(),
            'tools' => $quote->isEditable() ? ToolProject::latest('id')->limit(100)->get() : collect(),
        ]);
    }

    public function edit(Quote $quote)
    {
        if (! $quote->isEditable()) {
            return redirect()->route('admin.quotes.show', $quote)->with('error', 'Doar ciornele pot fi modificate. Creați o revizie nouă.');
        }

        return view('admin.quotes.form', ['quote' => $quote, 'items' => $quote->items] + $this->lists($quote));
    }

    public function update(Request $request, Quote $quote)
    {
        abort_unless($quote->isEditable(), 409);
        [$data, $items] = $this->validated($request, $quote);
        DB::transaction(function () use ($quote, $data, $items) {
            unset($data['entity_id']);
            $quote->update($data);
            $quote->items()->delete();
            $quote->items()->createMany($items);
            $this->quotes->recalculate($quote);
        });
        AuditLog::record('oferta.modificata', $quote);

        return redirect()->route('admin.quotes.show', $quote)->with('status', 'Oferta a fost salvată.');
    }

    /** Adds the result of a saved calculation or tool project as quote lines. */
    public function import(Request $request, Quote $quote)
    {
        abort_unless($quote->isEditable(), 409);
        $data = $request->validate(['calculation_id' => ['nullable', Rule::exists('calculations', 'id')], 'tool_project_id' => ['nullable', Rule::exists('tool_projects', 'id')]]);
        $position = (int) $quote->items()->max('position');
        $seeCosts = $request->user()->can_('costs.view');
        if (! empty($data['calculation_id'])) {
            $calc = Calculation::findOrFail($data['calculation_id']);
            if ($calc->state === 'block') {
                return back()->with('error', 'Calculul nu are un rezultat valabil.');
            }
            $position = $this->addCalculation($quote, $calc, $position);
        }
        if (! empty($data['tool_project_id'])) {
            $tool = ToolProject::with('lines')->findOrFail($data['tool_project_id']);
            $quote->items()->create(['position' => ++$position, 'kind' => 'scula', 'code' => $tool->reference, 'name' => $tool->name, 'description' => $tool->typeLabel(), 'quantity' => 1, 'unit' => 'buc',
                'unit_price' => 0, 'unit_cost' => $tool->totals()['total'], 'tool_project_id' => $tool->id, 'lead_time' => $tool->lead_time]);
        }
        $this->quotes->recalculate($quote);

        return back()->with('status', 'Pozițiile au fost adăugate. Verificați prețurile de vânzare'.($seeCosts ? '' : ' cu un coleg care vede costurile').'.');
    }

    public function submit(Request $request, Quote $quote)
    {
        abort_unless($quote->status === 'ciorna', 409);

        return $this->attempt(fn () => $this->quotes->submit($quote, $request->user()), $quote, 'oferta.la_aprobare', 'Oferta a fost trimisă la aprobare internă.');
    }

    public function approve(Request $request, Quote $quote)
    {
        abort_unless($quote->status === 'aprobare', 409);

        return $this->attempt(fn () => $this->quotes->approve($quote, $request->user()), $quote, 'oferta.aprobata', 'Oferta a fost aprobată și emisă. Conținutul ei este acum înghețat.');
    }

    public function sendBack(Request $request, Quote $quote)
    {
        abort_unless($quote->status === 'aprobare', 409);
        $note = $request->validate(['note' => 'nullable|string|max:1000'])['note'] ?? null;
        $quote->update(['status' => 'ciorna']);
        $quote->events()->create(['user_id' => $request->user()->id, 'type' => 'returnata', 'body' => $note]);
        AuditLog::record('oferta.returnata', $quote);

        return back()->with('status', 'Oferta a fost returnată ofertantului.');
    }

    /** Commercial outcome recorded by a person (sending by email arrives in phase 3). */
    public function status(Request $request, Quote $quote)
    {
        $allowed = ['aprobata' => ['trimisa', 'anulata'], 'trimisa' => ['negociere', 'acceptata', 'respinsa', 'expirata'], 'negociere' => ['acceptata', 'respinsa', 'expirata']][$quote->status] ?? [];
        $data = $request->validate(['status' => ['required', Rule::in($allowed)], 'note' => 'nullable|string|max:1000', 'confirmed_revenue' => 'nullable|numeric|min:0']);
        $update = ['status' => $data['status']];
        if ($data['status'] === 'trimisa') {
            $update['sent_at'] = now();
        }
        if (in_array($data['status'], ['acceptata', 'respinsa'], true)) {
            $update['decided_at'] = now();
        }
        if ($request->filled('confirmed_revenue')) {
            $update['confirmed_revenue'] = $data['confirmed_revenue'];
        }
        $quote->update($update);
        $quote->events()->create(['user_id' => $request->user()->id, 'type' => $data['status'], 'body' => $data['note'] ?? null]);
        $stage = ['trimisa' => 'trimisa', 'negociere' => 'negociere', 'acceptata' => 'client'][$data['status']] ?? null;
        if ($stage) {
            $quote->company->update(['stage' => $stage]);
        }
        AuditLog::record('oferta.status', $quote, ['status' => $data['status']]);

        return back()->with('status', 'Status actualizat: '.$quote->statusLabel().'.');
    }

    public function revise(Request $request, Quote $quote)
    {
        abort_if($quote->status === 'ciorna', 409);
        $copy = $this->quotes->revise($quote, $request->user());
        AuditLog::record('oferta.revizie', $copy);

        return redirect()->route('admin.quotes.edit', $copy)->with('status', 'Revizia '.$copy->revision.' a fost creată. Versiunea anterioară rămâne neschimbată.');
    }

    public function duplicate(Request $request, Quote $quote)
    {
        $companyId = $request->validate(['company_id' => ['nullable', Rule::exists('companies', 'id')]])['company_id'] ?? null;
        $copy = $this->quotes->duplicate($quote, $request->user(), $companyId ? (int) $companyId : null);
        AuditLog::record('oferta.duplicata', $copy, ['sursa' => $quote->label()]);

        return redirect()->route('admin.quotes.edit', $copy)->with('status', 'Ofertă nouă '.$copy->number.' creată din '.$quote->label().'.');
    }

    public function compare(Quote $quote, Quote $other)
    {
        abort_unless($quote->number === $other->number, 404);
        [$old, $new] = $quote->revision < $other->revision ? [$quote, $other] : [$other, $quote];

        return view('admin.quotes.compare', ['old' => $old->load('items'), 'new' => $new->load('items'), 'diff' => $this->quotes->diff($old, $new)]);
    }

    public function pdf(Quote $quote, QuotePdf $pdf)
    {
        AuditLog::record('oferta.pdf', $quote);

        return response($pdf->render($quote), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$quote->number.($quote->revision ? '-rev'.$quote->revision : '').'.pdf"']);
    }

    public function excel(Request $request, Quote $quote, QuoteExcel $excel)
    {
        $withCosts = $request->boolean('costuri') && $request->user()->can_('costs.view');
        AuditLog::record('oferta.excel', $quote, ['cu_costuri' => $withCosts]);

        return response($excel->build($quote->load(['items', 'entity', 'company']), $withCosts), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="'.$quote->number.'.xlsx"']);
    }

    /** One click from a saved calculation to a draft quote for the same client, with the calculation's lines. */
    public function fromCalculation(Request $request, Calculation $calculation)
    {
        if ($calculation->state === 'block') {
            return back()->with('error', 'Calculul nu are încă un rezultat valabil. Completați datele care lipsesc și salvați din nou.');
        }
        if (! $calculation->company_id) {
            return back()->with('error', 'Alegeți clientul în panoul «Salvează calculul», salvați, apoi creați oferta.');
        }
        $def = app(ModelRegistry::class)->find($calculation->model_key);
        $entity = Entity::where('code', $def->entity)->where('is_issuer', true)->first() ?? Entity::where('is_issuer', true)->orderBy('sort')->firstOrFail();
        $company = Company::findOrFail($calculation->company_id);
        $currency = collect($calculation->results['summary'] ?? [])->pluck('currency')->filter()->first() ?? setting('quotes.currency', 'EUR');
        $quote = DB::transaction(function () use ($request, $calculation, $entity, $company, $currency, $def) {
            $quote = $this->quotes->create([
                'entity_id' => $entity->id, 'company_id' => $company->id, 'contact_id' => $company->contacts()->where('is_primary', true)->value('id'),
                'rfq_id' => $calculation->rfq_id, 'title' => $calculation->title, 'type' => $def->entity === 'fepo' ? 'stante' : 'piese',
                'language' => $company->language ?: 'ro', 'currency' => $currency, 'vat_rate' => (float) setting('quotes.vat_rate', 0),
                'exchange_rate' => setting('quotes.exchange_rate'), 'exchange_rate_date' => setting('quotes.exchange_rate_date'), 'discount_percent' => 0,
            ], $request->user());
            $this->addCalculation($quote, $calculation, 0);
            $quote->events()->create(['user_id' => $request->user()->id, 'type' => 'creata', 'body' => 'Din calculul '.$calculation->reference]);

            return $this->quotes->recalculate($quote);
        });
        AuditLog::record('oferta.creata_din_calcul', $quote, ['calcul' => $calculation->reference]);

        return redirect()->route('admin.quotes.edit', $quote)->with('status', 'Oferta '.$quote->number.' a fost creată din '.$calculation->reference.'. Verificați prețurile și condițiile, apoi salvați.');
    }

    private function addCalculation(Quote $quote, Calculation $calc, int $position): int
    {
        $lines = $calc->results['lines'] ?? [];
        if ($lines !== []) {
            foreach ($lines as $l) {
                if (! is_numeric($l['unit_price'] ?? null) || ! is_numeric($l['qty'] ?? null)) {
                    continue;
                }
                $quote->items()->create(['position' => ++$position, 'kind' => $l['kind'] ?? 'piesa', 'code' => $l['code'], 'name' => $l['name'] ?: $l['code'], 'quantity' => $l['qty'], 'unit' => $l['unit'] ?? 'buc',
                    'unit_price' => $l['unit_price'], 'unit_cost' => is_numeric($l['cost'] ?? null) ? $l['cost'] : null, 'calculation_id' => $calc->id]);
            }
        } else {
            // Hour models give a cost only when the machine has a tariff; the selling price is always set by a person.
            $quote->items()->create(['position' => ++$position, 'kind' => 'serviciu', 'code' => $calc->reference, 'name' => $calc->title,
                'description' => ($calc->results['model']['title'] ?? '').' – '.money($calc->hours).' ore', 'quantity' => 1, 'unit' => 'lot', 'unit_price' => 0, 'unit_cost' => $calc->machineCost(), 'calculation_id' => $calc->id]);
        }

        return $position;
    }

    private function attempt(\Closure $action, Quote $quote, string $audit, string $message)
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditLog::record($audit, $quote);

        return back()->with('status', $message);
    }

    private function lists(Quote $quote): array
    {
        return ['companies' => Company::orderBy('name')->pluck('name', 'id'), 'entities' => Entity::where('is_issuer', true)->orderBy('sort')->get()];
    }

    private function validated(Request $request, ?Quote $quote = null): array
    {
        $num = fn ($v) => is_string($v) ? str_replace([' ', ','], ['', '.'], $v) : $v;
        $input = $request->all();
        foreach (['vat_rate', 'discount_percent', 'exchange_rate'] as $f) {
            $input[$f] = $num($input[$f] ?? null);
        }
        foreach ($input['items'] ?? [] as $i => $row) {
            foreach (['quantity', 'unit_price', 'unit_cost', 'discount_percent'] as $f) {
                if (isset($row[$f])) {
                    $input['items'][$i][$f] = $num($row[$f]);
                }
            }
        }
        $canDiscount = $request->user()->can_('quotes.discount');
        $data = validator($input, [
            'entity_id' => [$quote ? 'nullable' : 'required', Rule::exists('entities', 'id')->where('is_issuer', true)],
            'company_id' => ['required', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('company_id', $input['company_id'] ?? 0)],
            'rfq_id' => ['nullable', Rule::exists('rfqs', 'id')], 'type' => ['required', Rule::in(array_keys(Vocab::QUOTE_TYPES))], 'title' => 'nullable|string|max:255',
            'language' => ['required', Rule::in(array_keys(config('simex.languages')))], 'currency' => ['required', Rule::in(config('simex.currencies'))],
            'exchange_rate' => 'nullable|numeric|min:0.0001|max:100000', 'exchange_rate_date' => 'nullable|date', 'vat_rate' => 'required|numeric|min:0|max:50',
            'valid_until' => 'nullable|date', 'lead_time' => 'nullable|string|max:160', 'payment_terms' => 'nullable|string|max:255', 'delivery_terms' => 'nullable|string|max:255',
            'incoterm' => ['nullable', Rule::in(Vocab::INCOTERMS)], 'min_order' => 'nullable|string|max:160', 'intro' => 'nullable|string|max:5000', 'notes' => 'nullable|string|max:10000',
            'internal_notes' => 'nullable|string|max:10000', 'discount_percent' => 'nullable|numeric|min:0|max:100',
            'items' => 'nullable|array|max:200', 'items.*.name' => 'nullable|string|max:255', 'items.*.code' => 'nullable|string|max:80', 'items.*.kind' => ['nullable', Rule::in(array_keys(Vocab::ITEM_KINDS))],
            'items.*.description' => 'nullable|string|max:3000', 'items.*.quantity' => 'nullable|numeric|min:0|max:1000000000', 'items.*.unit' => 'nullable|string|max:10',
            'items.*.unit_price' => 'nullable|numeric|min:0|max:1000000000', 'items.*.unit_cost' => 'nullable|numeric|min:0|max:1000000000', 'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.lead_time' => 'nullable|string|max:160', 'items.*.calculation_id' => ['nullable', Rule::exists('calculations', 'id')], 'items.*.tool_project_id' => ['nullable', Rule::exists('tool_projects', 'id')],
        ])->validate();

        $seeCosts = $request->user()->can_('costs.view');
        $existing = $quote ? $quote->items->keyBy('id') : collect();
        $items = [];
        foreach ($input['items'] ?? [] as $i => $raw) {
            $row = $data['items'][$i] ?? [];
            if (! filled($row['name'] ?? null)) {
                continue;
            }
            $previous = $existing[(int) ($raw['id'] ?? 0)] ?? null;
            $items[] = [
                'position' => count($items) + 1, 'kind' => $row['kind'] ?? 'piesa', 'code' => $row['code'] ?? null, 'name' => $row['name'], 'description' => $row['description'] ?? null,
                'quantity' => (float) ($row['quantity'] ?? 1), 'unit' => ($row['unit'] ?? '') ?: 'buc', 'unit_price' => (float) ($row['unit_price'] ?? 0),
                // Users without the "internal costs" permission cannot see or overwrite the cost of a line.
                'unit_cost' => $seeCosts ? (($row['unit_cost'] ?? '') === '' || ! isset($row['unit_cost']) ? null : (float) $row['unit_cost']) : $previous?->unit_cost,
                'discount_percent' => $canDiscount ? (float) ($row['discount_percent'] ?? 0) : (float) ($previous?->discount_percent ?? 0),
                'is_optional' => ! empty($raw['is_optional']), 'lead_time' => $row['lead_time'] ?? null,
                'calculation_id' => $row['calculation_id'] ?? null, 'tool_project_id' => $row['tool_project_id'] ?? null, 'line_total' => 0,
            ];
        }
        unset($data['items']);
        $data['discount_percent'] = $canDiscount ? (float) ($data['discount_percent'] ?? 0) : (float) ($quote?->discount_percent ?? 0);

        return [$data, $items];
    }
}
