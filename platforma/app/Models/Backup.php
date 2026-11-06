<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['verified_at' => 'datetime'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function absolutePath(): string { return storage_path('app/private/backups/'.$this->filename); }
}
