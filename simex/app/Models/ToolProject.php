<?php

namespace App\Models;

use App\Support\Vocab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ToolProject extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function rfq()
    {
        return $this->belongsTo(Rfq::class);
    }

    public function lines()
    {
        return $this->hasMany(ToolCostLine::class)->orderBy('sort')->orderBy('id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function typeLabel(): string
    {
        return Vocab::TOOL_TYPES[$this->type] ?? $this->type;
    }

    /**
     * Sum of quantity × unit cost per phase. A line without a unit cost makes its phase (and the total) incomplete:
     * the figure is then reported as null instead of a misleading partial sum.
     *
     * @return array{phases: array<string, array{total: ?float, lines: int, missing: int}>, total: ?float, missing: int}
     */
    public function totals(): array
    {
        $phases = [];
        $missing = 0;
        $sum = 0.0;
        foreach (array_keys(Vocab::TOOL_PHASES) as $phase) {
            $phases[$phase] = ['total' => 0.0, 'lines' => 0, 'missing' => 0];
        }
        foreach ($this->lines as $line) {
            $p = &$phases[$line->phase];
            $p['lines']++;
            if ($line->unit_cost === null) {
                $p['missing']++;
                $missing++;
            } else {
                $p['total'] += round((float) $line->quantity * (float) $line->unit_cost, 2);
            }
            unset($p);
        }
        foreach ($phases as $key => $p) {
            if ($p['missing'] > 0) {
                $phases[$key]['total'] = null;
            } else {
                $sum += $p['total'];
            }
        }

        return ['phases' => $phases, 'total' => $missing > 0 ? null : round($sum, 2), 'missing' => $missing];
    }

    /** Tooling cost carried by each part when spread over the configured amortisation quantity. */
    public function amortisationPerPiece(): ?float
    {
        $total = $this->totals()['total'];

        return $total !== null && $this->amortization_quantity > 0 ? round($total / $this->amortization_quantity, 4) : null;
    }
}
