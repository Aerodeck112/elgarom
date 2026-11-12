<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalcParameterSet extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['overrides' => 'array', 'is_active' => 'boolean'];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
