<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_visible' => 'boolean', 'is_button' => 'boolean'];
}
