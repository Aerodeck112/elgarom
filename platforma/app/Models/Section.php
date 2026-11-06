<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['data' => 'array', 'is_visible' => 'boolean'];

    protected static function booted(): void
    {
        $mark = fn (Section $s) => Page::whereKey($s->page_id)->update(['has_unpublished_changes' => true]);
        static::saved($mark);
        static::deleted($mark);
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
