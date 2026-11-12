<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['price_breaks' => 'array', 'meta' => 'array', 'is_optional' => 'boolean', 'quantity' => 'float', 'unit_price' => 'float', 'unit_cost' => 'float', 'discount_percent' => 'float', 'line_total' => 'float'];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function calculation()
    {
        return $this->belongsTo(Calculation::class);
    }

    public function toolProject()
    {
        return $this->belongsTo(ToolProject::class);
    }
}
