<?php

namespace App\Calc;

use App\Calc\Engine\XlsxModelReader;
use App\Models\CalcModelVersion;
use App\Models\CalcParameterSet;
use App\Models\Calculation;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Deterministic costing service. Opens the active, validated workbook of a calculator, applies the
 * active parameter set and the user's inputs, and returns results computed on the server.
 */
final class CalcService
{
    public function __construct(private readonly ModelRegistry $registry) {}

    public function isReady(string $modelKey): bool
    {
        return CalcModelVersion::active($modelKey) !== null;
    }

    /** Parsed workbook, cached by file hash and engine version (parsing is the only part worth caching). */
    public function model(ModelDefinition $def, CalcModelVersion $version): array
    {
        $key = 'calc-model:'.$version->sha256.':'.config('simex.engine_version');

        return Cache::store('file')->rememberForever($key, function () use ($def, $version) {
            $path = $version->absolutePath();
            if (! is_file($path) || hash_file('sha256', $path) !== $version->sha256) {
                throw new RuntimeException('Fișierul modelului de calcul lipsește sau a fost modificat pe disc.');
            }

            return (new XlsxModelReader)->read($path, $def->input_fill);
        });
    }

    /**
     * @param  array|null  $paramOverrides  explicit overrides (a saved calculation's frozen set); null = active set
     */
    public function open(string $modelKey, ?CalcModelVersion $version = null, ?array $paramOverrides = null, ?string $calcDate = null): CalcSession
    {
        $def = $this->registry->find($modelKey);
        $version ??= CalcModelVersion::active($modelKey);
        if ($version === null) {
            throw new RuntimeException('Calculatorul nu are încă un model Excel activ.');
        }
        $session = new CalcSession($def, $this->model($def, $version));
        $session->wb->today = \App\Calc\Engine\Workbook::dateSerial(strtotime(($calcDate ?? now()->toDateString()).' 12:00:00 UTC'));
        $paramOverrides ??= $version->activeParameterSet()?->overrides ?? [];
        if ($paramOverrides !== []) {
            $session->apply($paramOverrides, ['params']);
        }

        return $session;
    }

    /** Roles of sheets the user may write to in an interactive session. */
    public function writableRoles(User $user): array
    {
        return $user->can_('rates.edit') ? ['input', 'params'] : ['input'];
    }

    /** Sheets the user may open. Parameter sheets hold tariffs and need the "rates" permission. */
    public function visibleSheets(ModelDefinition $def, User $user): array
    {
        $out = [];
        foreach ($def->sheets() as $name => $meta) {
            if ($meta['role'] === 'params' && ! $user->can_('rates.view')) {
                continue;
            }
            if (! empty($meta['internal']) && ! $user->can_('costs.view')) {
                continue;
            }
            $out[$name] = $meta;
        }

        return $out;
    }

    /** Summary enriched with display strings and, for hour models, the machine cost at the configured rate. */
    public function present(CalcSession $session, User $user, ?array $frozenMachine = null): array
    {
        $def = $session->def;
        $machine = $frozenMachine ?? $this->machineSnapshot($def);
        $summary = $session->summary();
        foreach ($summary as &$v) {
            $v['hours_display'] = $v['hours'] === null ? null : Display::number($v['hours'], 2);
            $v['total_display'] = $v['total'] === null ? null : Display::number($v['total'], 2);
            $v['machine_cost_display'] = null;
            if ($v['hours'] !== null && $machine && $machine['hourly_rate'] !== null && $user->can_('costs.view')) {
                $v['machine_cost_display'] = Display::number($v['hours'] * $machine['hourly_rate'], 2).' '.$machine['currency'];
            }
            if ($def->kind === 'cost' && ! $user->can_('costs.view')) {
                // Without the "internal costs" permission only the commercial result is shown.
                $v['outputs'] = array_values(array_filter($v['outputs'], fn ($o) => ! str_contains(mb_strtolower($o['label']), 'cost')));
            }
        }
        unset($v);

        return [
            'summary' => $summary,
            'machine' => $machine ? [
                'name' => $machine['name'],
                'rate_note' => $machine['hourly_rate'] === null ? 'Tarif orar neconfigurat pentru '.$machine['name'] : null,
            ] : null,
            'checks' => $session->checks(),
        ];
    }

    public function machineSnapshot(ModelDefinition $def): ?array
    {
        if (! $def->machine) {
            return null;
        }
        $machine = Machine::forModel($def->key());

        return [
            'code' => $def->machine['code'],
            'name' => $machine?->name ?? $def->machine['name'],
            'hourly_rate' => $machine?->hourly_rate !== null ? (float) $machine->hourly_rate : null,
            'currency' => $machine?->currency ?? 'EUR',
            'rate_date' => $machine?->rate_date?->toDateString(),
        ];
    }

    /** Computes and stores a calculation with a frozen copy of everything its result depends on. */
    public function save(Calculation $calc, array $inputs, ?string $variant, User $user): Calculation
    {
        $def = $this->registry->find($calc->model_key);
        $version = $calc->exists ? $calc->version : CalcModelVersion::active($calc->model_key);
        if ($version === null) {
            throw new RuntimeException('Calculatorul nu are încă un model Excel activ.');
        }
        $paramSet = $calc->exists ? $calc->parameterSet : $version->activeParameterSet();
        $calcDate = $calc->calc_date?->toDateString() ?? now()->toDateString();
        $session = $this->open($calc->model_key, $version, $paramSet?->overrides ?? [], $calcDate);
        $rejected = $session->apply($inputs, ['input']);
        $inputs = array_diff_key($inputs, array_flip($rejected));
        $summary = $session->summary();
        $variant = isset($summary[$variant]) ? $variant : array_key_first($summary);
        $chosen = $summary[$variant];
        $machine = $calc->results['machine'] ?? $this->machineSnapshot($def);

        $calc->fill([
            'calc_model_version_id' => $version->id,
            'calc_parameter_set_id' => $paramSet?->id,
            'inputs' => $inputs,
            'variant' => $variant,
            'state' => $chosen['state'],
            'status_text' => mb_substr($chosen['status'], 0, 250),
            'hours' => $chosen['hours'],
            'total' => $chosen['total'],
            'currency' => $chosen['currency'],
            'calc_date' => $calcDate,
            'results' => [
                'summary' => $summary,
                'lines' => $session->lines(),
                'checks' => $session->checks(),
                'machine' => $machine,
                'model' => ['key' => $def->key(), 'title' => $def->title, 'version_id' => $version->id, 'sha256' => $version->sha256, 'label' => $version->label],
                'parameters' => ['set_id' => $paramSet?->id, 'version' => $paramSet?->version, 'overrides' => $paramSet?->overrides ?? []],
                'engine_version' => config('simex.engine_version'),
                'computed_at' => now()->toIso8601String(),
            ],
        ]);
        $calc->created_by ??= $user->id;
        $calc->save();

        return $calc;
    }

    /** Stores a new active parameter set (previous sets are kept for history and for old calculations). */
    public function saveParameters(CalcModelVersion $version, array $overrides, ?string $note, User $user): CalcParameterSet
    {
        $def = $this->registry->find($version->model_key);
        $current = $version->activeParameterSet();
        $session = new CalcSession($def, $this->model($def, $version));
        $merged = $current?->overrides ?? [];
        foreach ($overrides as $ref => $value) {
            $merged[$ref] = CalcSession::normalise($value);
        }
        $rejected = $session->apply($merged, ['params']);
        $merged = array_diff_key($merged, array_flip($rejected));

        $version->parameterSets()->update(['is_active' => false]);

        return $version->parameterSets()->create([
            'model_key' => $version->model_key,
            'version' => (int) $version->parameterSets()->max('version') + 1,
            'overrides' => $merged,
            'note' => $note,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
    }
}
