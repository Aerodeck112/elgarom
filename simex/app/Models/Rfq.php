<?php

namespace App\Models;

use App\Support\Vocab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['services' => 'array', 'received_at' => 'date', 'due_at' => 'date'];

    public const OPEN = ['noua', 'calificare', 'calcul', 'aprobare'];

    public function company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function engineer()
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    public function items()
    {
        return $this->hasMany(RfqItem::class)->orderBy('position');
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function calculations()
    {
        return $this->hasMany(Calculation::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    public function statusLabel(): string
    {
        return Vocab::RFQ_STATUSES[$this->status] ?? $this->status;
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast() && in_array($this->status, self::OPEN, true);
    }
}
