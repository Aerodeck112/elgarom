<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use Concerns\TracksPrice;

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'price_date' => 'date', 'price' => 'decimal:4', 'density' => 'decimal:4'];

    protected array $trackedPrices = ['price'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
