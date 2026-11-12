<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['is_primary' => 'boolean', 'do_not_contact' => 'boolean', 'unsubscribed_at' => 'datetime', 'email_verified_at' => 'datetime'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Commercial email is allowed only when neither the contact, the company nor the global list excludes it. */
    public function canReceiveCommercialEmail(): bool
    {
        return filled($this->email) && ! $this->do_not_contact && $this->unsubscribed_at === null
            && ! $this->company?->do_not_contact && ! Suppression::has($this->email);
    }
}
