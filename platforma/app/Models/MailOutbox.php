<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailOutbox extends Model
{
    protected $guarded = ['id'];

    protected $table = 'mail_outbox';

    protected $casts = ['payload' => 'array', 'next_attempt_at' => 'datetime', 'sent_at' => 'datetime'];
}
