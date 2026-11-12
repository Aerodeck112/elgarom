<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    use Concerns\TracksPrice;

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'is_subcontracted' => 'boolean', 'rate' => 'decimal:4'];

    protected array $trackedPrices = ['rate'];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
