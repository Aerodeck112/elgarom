<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Entity;
use App\Models\Rfq;
use App\Models\User;
use App\Services\Numbering;
use App\Support\Vocab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RfqController extends Controller
{
    public function index(Request $request)
    {
        $q = Rfq::query()->with(['company', 'owner', 'engineer']);
        if (($term = trim((string) $request->query('q'))) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('title', 'like', $like)->orWhereHas('company', fn ($c) => $c->where('name', 'like', $like)));
        }
        if (isset(Vocab::RFQ_STATUSES[$request->query('status')])) {
            $q->where('status', $request->query('status'));
        } elseif ($request->query('status') === 'deschise') {
            $q->whereIn('status', Rfq::OPEN);
        }

        return view('admin.rfqs.index', [
            'rows' => $q->orderByDesc('id')->paginate(30)->withQueryString(),
            'counts' => Rfq::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function create(Request $request)
    {
        $rfq = new Rfq(['status' => 'noua', 'priority' => 'normala', 'received_at' => now(), 'owner_id' => auth()->id(), 'company_id' => $request->integer('company') ?: null, 'services' => []]);

        return view('admin.rfqs.form', ['rfq' => $rfq] + $this->lists());
    }

    public function store(Request $request)
    {
        [$data, $items] = $this->validated($request);
        $rfq = DB::transaction(function () use ($data, $items) {
            $rfq = Rfq::create($data + ['number' => Numbering::next('rfq', 'RFQ')]);
            $rfq->items()->createMany($items);
            // The company moves forward in the commercial flow when it sends its first request.
            if (in_array($rfq->company->stage, ['lead', 'calificare'], true)) {
                $rfq->company->update(['stage' => 'cerere']);
            }

            return $rfq;
        });
        AuditLog::record('rfq.creata', $rfq);

        return redirect()->route('admin.rfqs.show', $rfq)->with('status', 'Cererea '.$rfq->number.' a fost înregistrată.');
    }

    public function show(Rfq $rfq)
    {
        $rfq->load(['company', 'contact', 'entity', 'owner', 'engineer', 'items', 'quotes', 'calculations']);

        return view('admin.rfqs.show', ['rfq' => $rfq]);
    }

    public function edit(Rfq $rfq)
    {
        return view('admin.rfqs.form', ['rfq' => $rfq->load('items')] + $this->lists());
    }

    public function update(Request $request, Rfq $rfq)
    {
        [$data, $items] = $this->validated($request);
        DB::transaction(function () use ($rfq, $data, $items) {
            $rfq->update($data);
            $rfq->items()->delete();
            $rfq->items()->createMany($items);
        });
        AuditLog::record('rfq.modificata', $rfq);

        return redirect()->route('admin.rfqs.show', $rfq)->with('status', 'Cererea a fost actualizată.');
    }

    public function status(Request $request, Rfq $rfq)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Vocab::RFQ_STATUSES))], 'lost_reason' => 'nullable|string|max:255']);
        $rfq->update($data);
        AuditLog::record('rfq.status', $rfq, ['status' => $data['status']]);

        return back()->with('status', 'Statusul cererii: '.$rfq->statusLabel().'.');
    }

    private function lists(): array
    {
        return [
            'companies' => Company::orderBy('name')->pluck('name', 'id'),
            'entities' => Entity::orderBy('sort')->pluck('name', 'id'),
            'users' => User::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'company_id' => ['required', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('company_id', $request->input('company_id'))],
            'entity_id' => ['nullable', Rule::exists('entities', 'id')], 'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000',
            'services' => 'nullable|array', 'services.*' => [Rule::in(array_keys(Vocab::SERVICES))], 'source' => ['nullable', Rule::in(array_keys(Vocab::LEAD_SOURCES))],
            'status' => ['required', Rule::in(array_keys(Vocab::RFQ_STATUSES))], 'priority' => ['required', Rule::in(array_keys(Vocab::PRIORITIES))],
            'received_at' => 'nullable|date', 'due_at' => 'nullable|date', 'owner_id' => ['nullable', Rule::exists('users', 'id')], 'engineer_id' => ['nullable', Rule::exists('users', 'id')],
            'notes' => 'nullable|string|max:10000',
            'items' => 'nullable|array|max:100', 'items.*.part_name' => 'nullable|string|max:255', 'items.*.part_code' => 'nullable|string|max:80',
            'items.*.service' => ['nullable', Rule::in(array_keys(Vocab::SERVICES))], 'items.*.drawing_no' => 'nullable|string|max:80', 'items.*.revision' => 'nullable|string|max:20',
            'items.*.material' => 'nullable|string|max:120', 'items.*.quantity' => 'nullable|numeric|min:0', 'items.*.annual_quantity' => 'nullable|numeric|min:0', 'items.*.lot_size' => 'nullable|numeric|min:0',
        ]);
        $items = [];
        foreach ($data['items'] ?? [] as $row) {
            if (filled($row['part_name'] ?? null)) {
                $items[] = ['position' => count($items) + 1, 'service' => $row['service'] ?? 'piese'] + $row;
            }
        }
        unset($data['items']);
        $data['services'] = $data['services'] ?? [];

        return [$data, $items];
    }
}
