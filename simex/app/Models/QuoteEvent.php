<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['data' => 'array', 'created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
