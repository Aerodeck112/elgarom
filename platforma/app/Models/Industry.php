<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_published' => 'boolean'];

    public function image() { return $this->belongsTo(Media::class, 'image_id'); }

    public function scopePublished($q) { return $q->where('is_published', true)->where('locale', app()->getLocale())->orderBy('sort'); }
}
