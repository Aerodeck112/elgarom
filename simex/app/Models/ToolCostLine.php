<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolCostLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['quantity' => 'float', 'unit_cost' => 'float'];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function calculation()
    {
        return $this->belongsTo(Calculation::class);
    }

    public function total(): ?float
    {
        return $this->unit_cost === null ? null : round($this->quantity * $this->unit_cost, 2);
    }
}
