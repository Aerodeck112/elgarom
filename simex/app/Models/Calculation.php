<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A saved calculation: inputs plus a frozen copy of its results and of the parameters in force when it was computed. */
class Calculation extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['inputs' => 'array', 'results' => 'array', 'calc_date' => 'date', 'locked_at' => 'datetime', 'hours' => 'float', 'total' => 'float'];

    public function version()
    {
        return $this->belongsTo(CalcModelVersion::class, 'calc_model_version_id');
    }

    public function parameterSet()
    {
        return $this->belongsTo(CalcParameterSet::class, 'calc_parameter_set_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function rfq()
    {
        return $this->belongsTo(Rfq::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    /** Cost of the machine hours at the rate frozen in the snapshot (null while no rate is configured). */
    public function machineCost(): ?float
    {
        $rate = $this->results['machine']['hourly_rate'] ?? null;

        return $this->hours !== null && $rate !== null ? round($this->hours * (float) $rate, 2) : null;
    }
}
