<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeEntry extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'reviewed_at' => 'datetime'];
}
