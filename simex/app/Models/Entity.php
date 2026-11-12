<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A company of the group: shown on the public site and, once its legal data is confirmed, able to issue quotes. */
class Entity extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['show_on_site' => 'boolean', 'logo_on_dark' => 'boolean', 'is_issuer' => 'boolean', 'legal_confirmed_at' => 'datetime'];

    public function templates()
    {
        return $this->hasMany(QuoteTemplate::class);
    }

    /** A quote may only be issued by an entity whose legal identity an administrator has confirmed. */
    public function canIssue(): bool
    {
        return $this->is_issuer && $this->legal_confirmed_at !== null && filled($this->legal_name) && filled($this->tax_id);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? route('entity.logo', [$this, 'v' => $this->updated_at?->timestamp]) : null;
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->postal_code, $this->city, $this->county])->filter()->implode(', ');
    }
}
