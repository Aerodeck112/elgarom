<?php

namespace App\Models;

use App\Support\Vocab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['interests' => 'array', 'next_follow_up_at' => 'datetime', 'do_not_contact' => 'boolean'];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function rfqs()
    {
        return $this->hasMany(Rfq::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class)->latest();
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function stageLabel(): string
    {
        return Vocab::COMPANY_STAGES[$this->stage] ?? $this->stage;
    }

    public function countryName(): string
    {
        return Vocab::countries()[$this->country] ?? (string) $this->country;
    }
}
