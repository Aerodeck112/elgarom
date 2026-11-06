<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['last_activity_at' => 'datetime', 'led_to_quote' => 'boolean'];

    public function messages() { return $this->hasMany(AiMessage::class)->orderBy('id'); }
}
