<?php

namespace App\Http\Controllers\Admin;

use App\Calc\CalcService;
use App\Calc\ModelRegistry;
use App\Calc\ModelValidator;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CalcModelVersion;
use App\Models\Calculation;
use App\Models\Company;
use App\Models\Rfq;
use App\Services\Numbering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use RuntimeException;

class CalculatorController extends Controller
{
    public function __construct(private readonly ModelRegistry $registry, private readonly CalcService $calc) {}

    /** Calculators of one area (piese | stante) with their status and the saved calculations. */
    public function index(Request $request, string $area)
    {
        abort_unless(in_array($area, ['piese', 'stante'], true), 404);
        $entity = $area === 'piese' ? 'argento' : 'fepo';
        $models = array_filter($this->registry->all(), fn ($d) => $d->entity === $entity);
        // Calculators that price a whole job come first, hour estimators after.
        uasort($models, fn ($a, $b) => [$a->kind !== 'cost', $a->title] <=> [$b->kind !== 'cost', $b->title]);
        $active = CalcModelVersion::where('status', 'activ')->get()->keyBy('model_key');

        return view('admin.calc.index', [
            'area' => $area, 'models' => $models, 'active' => $active,
            'calculations' => Calculation::with(['company', 'author'])->whereIn('model_key', array_keys($models))->latest('id')->paginate(20),
        ]);
    }

    public function create(Request $request, string $key)
    {
        $def = $this->registry->find($key);
        if (! $this->calc->isReady($key)) {
            return view('admin.calc.pending', ['def' => $def]);
        }
        $calc = new Calculation(['model_key' => $key, 'inputs' => [], 'title' => '', 'rfq_id' => $request->integer('rfq') ?: null, 'company_id' => $request->integer('company') ?: null]);

        return $this->screen($request, $def, $calc);
    }

    public function show(Request $request, Calculation $calculation)
    {
        return $this->screen($request, $this->registry->find($calculation->model_key), $calculation);
    }

    private function screen(Request $request, $def, Calculation $calc)
    {
        $sheets = $this->calc->visibleSheets($def, $request->user());

        return view('admin.calc.show', [
            'def' => $def, 'calc' => $calc, 'sheets' => $sheets,
            'version' => $calc->exists ? $calc->version : CalcModelVersion::active($def->key()),
            'companies' => Company::orderBy('name')->pluck('name', 'id'),
            'rfqs' => Rfq::whereIn('status', Rfq::OPEN)->orWhere('id', $calc->rfq_id)->orderByDesc('id')->limit(200)->get(['id', 'number', 'title']),
        ]);
    }

    /** Recalculates on the server and returns one sheet plus the results. Nothing is stored. */
    public function evaluate(Request $request, string $key)
    {
        $def = $this->registry->find($key);
        $data = $request->validate([
            'sheet' => ['required', 'string', Rule::in(array_keys($this->calc->visibleSheets($def, $request->user())))],
            'inputs' => 'nullable|array|max:20000', 'params' => 'nullable|array|max:20000', 'calculation' => 'nullable|integer',
        ]);
        $saved = isset($data['calculation']) ? Calculation::find($data['calculation']) : null;
        abort_if($saved && $saved->model_key !== $key, 404);
        try {
            $session = $saved
                ? $this->calc->open($key, $saved->version, $saved->results['parameters']['overrides'] ?? [], $saved->calc_date->toDateString())
                : $this->calc->open($key);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
        $rejected = $session->apply($this->scalars($data['inputs'] ?? []), ['input']);
        if (! empty($data['params'])) {
            abort_unless($request->user()->can_('rates.edit'), 403);
            $rejected = array_merge($rejected, $session->apply($this->scalars($data['params']), ['params']));
        }
        $roles = $saved?->isLocked() ? [] : $this->calc->writableRoles($request->user());

        return response()->json($this->calc->present($session, $request->user(), $saved?->results['machine'] ?? null) + [
            'view' => $session->sheetView($data['sheet'], $roles),
            'rejected' => $rejected,
        ]);
    }

    public function store(Request $request, string $key)
    {
        $this->registry->find($key);

        return $this->persist($request, new Calculation(['model_key' => $key, 'reference' => Numbering::next('calc', 'CALC')]));
    }

    public function update(Request $request, Calculation $calculation)
    {
        if ($calculation->isLocked()) {
            return back()->with('error', 'Calculul este blocat: face parte dintr-o ofertă emisă. Duplicați-l pentru o variantă nouă.');
        }

        return $this->persist($request, $calculation);
    }

    private function persist(Request $request, Calculation $calc)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255', 'company_id' => ['nullable', Rule::exists('companies', 'id')], 'rfq_id' => ['nullable', Rule::exists('rfqs', 'id')],
            'inputs' => 'required|json', 'variant' => 'nullable|string|max:40',
        ]);
        $inputs = $this->scalars(json_decode($data['inputs'], true) ?: []);
        $calc->fill(['title' => $data['title'], 'company_id' => $data['company_id'] ?? null, 'rfq_id' => $data['rfq_id'] ?? null]);
        try {
            $this->calc->save($calc, $inputs, $data['variant'] ?? null, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditLog::record('calcul.salvat', $calc, ['model' => $calc->model_key, 'stare' => $calc->state]);

        return redirect()->route('admin.calc.show', $calc)->with('status', 'Calculul '.$calc->reference.' a fost salvat.');
    }

    public function duplicate(Request $request, Calculation $calculation)
    {
        $copy = new Calculation(['model_key' => $calculation->model_key, 'reference' => Numbering::next('calc', 'CALC'), 'title' => $calculation->title.' (copie)', 'company_id' => $calculation->company_id, 'rfq_id' => $calculation->rfq_id]);
        $this->calc->save($copy, $calculation->inputs ?? [], $calculation->variant, $request->user());

        return redirect()->route('admin.calc.show', $copy)->with('status', 'Copie creată cu modelul și parametrii activi acum.');
    }

    public function destroy(Calculation $calculation)
    {
        abort_if($calculation->isLocked(), 403);
        $calculation->delete();

        return redirect()->route('admin.dashboard')->with('status', 'Calculul a fost șters.');
    }

    /** New active parameter set for a calculator (tariffs, coefficients). */
    public function params(Request $request, string $key)
    {
        $this->registry->find($key);
        $data = $request->validate(['params' => 'required|json', 'note' => 'nullable|string|max:255']);
        $version = CalcModelVersion::active($key) ?? abort(409);
        $set = $this->calc->saveParameters($version, $this->scalars(json_decode($data['params'], true) ?: []), $data['note'] ?? null, $request->user());
        AuditLog::record('parametri.modificati', $set, ['model' => $key, 'versiune' => $set->version, 'celule' => array_keys($set->overrides)]);

        return back()->with('status', 'Parametrii au fost salvați ca versiunea '.$set->version.'. Calculele deja salvate își păstrează parametrii vechi.');
    }

    // ---- Excel models (import, validation, activation) ----

    public function models()
    {
        return view('admin.calc.models', [
            'defs' => $this->registry->all(),
            'versions' => CalcModelVersion::with('uploader')->latest('id')->get()->groupBy('model_key'),
        ]);
    }

    public function upload(Request $request, ModelValidator $validator)
    {
        $data = $request->validate([
            'model_key' => ['required', Rule::in(array_keys($this->registry->all()))],
            'file' => 'required|file|max:'.config('simex.uploads.workbook_max_kb'), 'label' => 'nullable|string|max:60',
        ]);
        $file = $request->file('file');
        if (strtolower($file->getClientOriginalExtension()) !== 'xlsx') {
            return back()->with('error', 'Se acceptă doar fișiere .xlsx (fără macro-uri).');
        }
        $def = $this->registry->find($data['model_key']);
        $sha = hash_file('sha256', $file->getRealPath());
        if (CalcModelVersion::where('model_key', $def->key())->where('sha256', $sha)->exists()) {
            return back()->with('error', 'Acest fișier a fost deja încărcat pentru calculatorul ales.');
        }
        $report = $validator->validate($file->getRealPath(), $def);
        $path = $file->storeAs('calc-models/'.$def->key(), $sha.'.xlsx', 'local');
        $version = CalcModelVersion::create([
            'model_key' => $def->key(), 'label' => $data['label'] ?? null, 'original_name' => mb_substr($file->getClientOriginalName(), 0, 250), 'path' => $path, 'sha256' => $sha,
            'size' => $file->getSize(), 'status' => $report['ok'] ? 'validat' : 'respins', 'formulas' => $report['formulas'], 'mismatches' => $report['mismatches'],
            'report' => $report, 'uploaded_by' => $request->user()->id,
        ]);
        AuditLog::record('model.incarcat', $version, ['model' => $def->key(), 'valid' => $report['ok'], 'formule' => $report['formulas'], 'diferente' => $report['mismatches']]);

        return back()->with($report['ok'] ? 'status' : 'error', $report['ok']
            ? "Model validat: {$report['formulas']} formule recalculate, 0 diferențe față de Excel. Îl puteți activa."
            : 'Modelul a fost respins: '.implode(' ', $report['errors']));
    }

    public function activate(Request $request, CalcModelVersion $version)
    {
        abort_unless(in_array($version->status, ['validat', 'retras'], true), 409);
        CalcModelVersion::where('model_key', $version->model_key)->where('status', 'activ')->update(['status' => 'retras']);
        $version->update(['status' => 'activ', 'activated_by' => $request->user()->id, 'activated_at' => now()]);
        Cache::store('file')->forget('calc-model:'.$version->sha256.':'.config('simex.engine_version'));
        AuditLog::record('model.activat', $version, ['model' => $version->model_key]);

        return back()->with('status', 'Modelul este activ. Calculele noi îl folosesc; cele salvate rămân pe versiunea lor.');
    }

    /** Only scalars reach the engine, keyed by "Sheet!A1". */
    private function scalars(array $values): array
    {
        return array_filter($values, fn ($v, $k) => is_string($k) && strlen($k) <= 80 && (is_scalar($v) || $v === null), ARRAY_FILTER_USE_BOTH);
    }
}
