<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Calculation;
use App\Models\Company;
use App\Models\Machine;
use App\Models\Product;
use App\Models\ToolCostLine;
use App\Models\ToolProject;
use App\Services\Numbering;
use App\Support\Vocab;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Dies, moulds and special tooling: hours come from the validated calculators, other costs are entered by the engineer. */
class ToolProjectController extends Controller
{
    public function index()
    {
        return view('admin.tools.index', ['rows' => ToolProject::with(['company', 'lines'])->latest('id')->paginate(30)]);
    }

    public function create()
    {
        return view('admin.tools.form', ['tool' => new ToolProject(['type' => 'stanta_simpla', 'currency' => 'EUR']), 'companies' => Company::orderBy('name')->pluck('name', 'id')]);
    }

    public function store(Request $request)
    {
        $tool = ToolProject::create($this->validated($request) + ['reference' => Numbering::next('tool', 'SDV'), 'created_by' => $request->user()->id]);
        AuditLog::record('scula.creata', $tool);

        return redirect()->route('admin.tools.show', $tool)->with('status', 'Proiectul '.$tool->reference.' a fost creat. Adăugați liniile de cost.');
    }

    public function show(ToolProject $tool)
    {
        $tool->load(['lines.calculation', 'lines.machine', 'company', 'products']);

        return view('admin.tools.show', [
            'tool' => $tool, 'totals' => $tool->totals(), 'machines' => Machine::where('is_active', true)->orderBy('name')->get(),
            'calculations' => Calculation::whereNotNull('hours')->latest('id')->limit(150)->get(),
            'products' => Product::where('kind', 'piesa')->when($tool->company_id, fn ($q) => $q->where('company_id', $tool->company_id))->orderBy('code')->limit(300)->get(),
        ]);
    }

    public function edit(ToolProject $tool)
    {
        return view('admin.tools.form', ['tool' => $tool, 'companies' => Company::orderBy('name')->pluck('name', 'id')]);
    }

    public function update(Request $request, ToolProject $tool)
    {
        $tool->update($this->validated($request));
        if ($request->has('products')) {
            $tool->products()->sync(array_filter((array) $request->input('products'), 'is_numeric'));
        }
        AuditLog::record('scula.modificata', $tool);

        return redirect()->route('admin.tools.show', $tool)->with('status', 'Proiectul a fost actualizat.');
    }

    public function lineStore(Request $request, ToolProject $tool)
    {
        $input = $request->all();
        foreach (['quantity', 'unit_cost'] as $f) {
            $input[$f] = isset($input[$f]) && is_string($input[$f]) ? str_replace([' ', ','], ['', '.'], $input[$f]) : ($input[$f] ?? null);
        }
        $data = validator($input, [
            'phase' => ['required', Rule::in(array_keys(Vocab::TOOL_PHASES))], 'category' => ['required', Rule::in(array_keys(Vocab::TOOL_CATEGORIES))], 'description' => 'nullable|string|max:255',
            'quantity' => 'nullable|numeric|min:0|max:100000000', 'unit' => 'required|string|max:10', 'unit_cost' => 'nullable|numeric|min:0|max:100000000',
            'machine_id' => ['nullable', Rule::exists('machines', 'id')], 'calculation_id' => ['nullable', Rule::exists('calculations', 'id')],
        ])->validate();
        if (! empty($data['calculation_id'])) {
            // Hours and tariff are taken from the saved calculation, exactly as frozen in it.
            $calc = Calculation::findOrFail($data['calculation_id']);
            $data['quantity'] = $calc->hours;
            $data['unit'] = 'h';
            $data['unit_cost'] = $calc->results['machine']['hourly_rate'] ?? null;
            $data['description'] = $data['description'] ?: $calc->reference.' – '.$calc->title;
        } elseif (! empty($data['machine_id']) && ($data['unit_cost'] ?? '') === '') {
            $data['unit_cost'] = Machine::find($data['machine_id'])?->hourly_rate;
        }
        if (! $request->user()->can_('costs.view')) {
            $data['unit_cost'] = $data['unit_cost'] ?? null;
        }
        $data['quantity'] = (float) ($data['quantity'] ?? 0);
        $data['unit_cost'] = ($data['unit_cost'] ?? '') === '' ? null : $data['unit_cost'];
        $tool->lines()->create($data + ['sort' => (int) $tool->lines()->max('sort') + 1]);

        return back()->with('status', 'Linia de cost a fost adăugată.');
    }

    public function lineDestroy(ToolProject $tool, ToolCostLine $line)
    {
        abort_unless($line->tool_project_id === $tool->id, 404);
        $line->delete();

        return back()->with('status', 'Linia a fost ștearsă.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255', 'type' => ['required', Rule::in(array_keys(Vocab::TOOL_TYPES))], 'company_id' => ['nullable', Rule::exists('companies', 'id')],
            'complexity' => ['nullable', Rule::in(array_keys(Vocab::COMPLEXITIES))], 'operations_count' => 'nullable|integer|min:0|max:500', 'lead_time' => 'nullable|string|max:80',
            'amortization_quantity' => 'nullable|integer|min:1|max:2000000000', 'currency' => ['required', Rule::in(config('simex.currencies'))], 'notes' => 'nullable|string|max:10000',
        ]);
    }
}
