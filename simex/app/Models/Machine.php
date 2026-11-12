<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use Concerns\TracksPrice;

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'rate_date' => 'date', 'hourly_rate' => 'decimal:4'];

    protected array $trackedPrices = ['hourly_rate'];

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public static function forModel(string $modelKey): ?self
    {
        return self::where('calc_model_key', $modelKey)->where('is_active', true)->first();
    }
}
