<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['due_at' => 'datetime', 'done_at' => 'datetime'];

    public function company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function isOpenTask(): bool
    {
        return $this->type === 'sarcina' && $this->done_at === null;
    }
}
