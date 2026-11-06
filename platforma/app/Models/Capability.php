<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capability extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_confirmed' => 'boolean', 'show_on_home' => 'boolean'];

    /** Only confirmed values are ever rendered publicly. */
    public function scopeConfirmed($q) { return $q->where('is_confirmed', true)->where('locale', app()->getLocale())->orderBy('sort'); }

    public function display(): string { return trim(($this->value ?? '').' '.($this->unit ?? '')); }
}
