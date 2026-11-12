<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    protected $table = 'price_history';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['created_at' => 'datetime'];
}
