<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Models\PriceHistory;

/** Keeps a history row and an audit entry whenever a price or tariff changes. */
trait TracksPrice
{
    public static function bootTracksPrice(): void
    {
        static::saved(function ($model) {
            foreach ($model->trackedPrices as $field) {
                if ($model->wasChanged($field) || ($model->wasRecentlyCreated && $model->{$field} !== null)) {
                    $old = $model->wasRecentlyCreated ? null : $model->getOriginal($field);
                    PriceHistory::create([
                        'priceable_type' => $model->getMorphClass(), 'priceable_id' => $model->getKey(), 'field' => $field,
                        'old_value' => $old, 'new_value' => $model->{$field}, 'currency' => $model->currency ?? null, 'user_id' => auth()->id(),
                    ]);
                    AuditLog::record('tarif.modificat', $model, ['camp' => $field, 'vechi' => $old, 'nou' => $model->{$field}]);
                }
            }
        });
    }

    public function priceHistory()
    {
        return PriceHistory::where('priceable_type', $this->getMorphClass())->where('priceable_id', $this->getKey())->latest('id');
    }
}
