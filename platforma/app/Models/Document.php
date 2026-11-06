<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_public' => 'boolean', 'valid_until' => 'date'];

    public function scopePublic($q) { return $q->where('is_public', true)->whereNotNull('file_path')->where('locale', app()->getLocale())->orderBy('sort'); }

    public function url(): string { return route('document.download', [$this->id, \Illuminate\Support\Str::slug($this->title)]); }
}
