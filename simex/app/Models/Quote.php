<?php

namespace App\Models;

use App\Support\Vocab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['public_token_hash'];

    protected $casts = [
        'snapshot' => 'array', 'issued_at' => 'date', 'valid_until' => 'date', 'exchange_rate_date' => 'date', 'approved_at' => 'datetime',
        'sent_at' => 'datetime', 'decided_at' => 'datetime', 'public_expires_at' => 'datetime', 'is_current' => 'boolean',
        'subtotal' => 'float', 'total_net' => 'float', 'vat_amount' => 'float', 'total_gross' => 'float', 'cost_total' => 'float',
        'discount_percent' => 'float', 'vat_rate' => 'float', 'exchange_rate' => 'float', 'confirmed_revenue' => 'float',
    ];

    /** Statuses in which the commercial content can still be edited. */
    public const EDITABLE = ['ciorna'];

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function rfq()
    {
        return $this->belongsTo(Rfq::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class)->orderBy('position')->orderBy('id');
    }

    public function events()
    {
        return $this->hasMany(QuoteEvent::class)->latest('id');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /** All revisions of the same quote, newest first. */
    public function revisions()
    {
        return self::where('number', $this->number)->orderByDesc('revision');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE, true);
    }

    public function label(): string
    {
        return $this->number.($this->revision > 0 ? ' rev. '.$this->revision : '');
    }

    public function statusLabel(): string
    {
        return Vocab::QUOTE_STATUSES[$this->status] ?? $this->status;
    }

    /** Gross margin on the net total, when every line has an internal cost. */
    public function marginPercent(): ?float
    {
        if ($this->cost_total === null || $this->total_net <= 0) {
            return null;
        }

        return round(($this->total_net - $this->cost_total) / $this->total_net * 100, 2);
    }
}
