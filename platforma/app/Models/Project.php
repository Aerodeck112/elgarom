<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_published' => 'boolean', 'client_consent' => 'boolean', 'gallery' => 'array'];

    public function cover() { return $this->belongsTo(Media::class, 'cover_id'); }

    public function galleryMedia() { return Media::whereIn('id', $this->gallery ?? [])->get()->sortBy(fn ($m) => array_search($m->id, $this->gallery ?? [])); }

    public function publicClient(): ?string { return $this->client_consent ? $this->client : null; }

    public function scopePublished($q) { return $q->where('is_published', true)->where('locale', app()->getLocale())->orderBy('sort'); }
}
