<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** A private technical or commercial file. Served only through an authorised controller, never from the public folder. */
class Document extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['client_visible' => 'boolean'];

    protected static function booted(): void
    {
        static::deleted(fn (self $doc) => Storage::disk('local')->delete($doc->path));
    }

    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sizeLabel(): string
    {
        return $this->size >= 1048576 ? number_format($this->size / 1048576, 1, ',', '.').' MB' : number_format(max(1, $this->size / 1024), 0, ',', '.').' KB';
    }
}
