<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An uploaded Excel workbook. Status: respins (failed validation), validat (may be activated), activ, retras. */
class CalcModelVersion extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['report' => 'array', 'activated_at' => 'datetime'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function parameterSets()
    {
        return $this->hasMany(CalcParameterSet::class);
    }

    public function activeParameterSet(): ?CalcParameterSet
    {
        return $this->parameterSets()->where('is_active', true)->first();
    }

    public static function active(string $modelKey): ?self
    {
        return self::where('model_key', $modelKey)->where('status', 'activ')->first();
    }

    public function absolutePath(): string
    {
        return storage_path('app/private/'.$this->path);
    }
}
