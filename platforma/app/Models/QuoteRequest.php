<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QuoteRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'marketing_consent' => 'boolean',
        'privacy_accepted_at' => 'datetime',
        'deadline' => 'date',
    ];

    public const STATUSES = [
        'nou' => 'Nouă',
        'in_analiza' => 'În analiză',
        'info_necesare' => 'Informații necesare',
        'oferta_trimisa' => 'Ofertă trimisă',
        'castigata' => 'Câștigată',
        'pierduta' => 'Pierdută',
        'anulata' => 'Anulată / spam',
    ];

    public const PART_TYPES = [
        'piese_mici_serie' => 'Piese mici în serie (șuruburi, piulițe, elemente de fixare)',
        'piese_stantate' => 'Piese ștanțate / tăiate',
        'subansamble_sudate' => 'Subansamble și construcții sudate',
        'profile_table' => 'Profile, bare, table',
        'piese_prelucrate' => 'Piese prelucrate mecanic',
        'altele' => 'Altele / nu știu',
    ];

    public const PROCESSES = [
        'nu_stiu' => 'Nu știu – recomandați-mi',
        'dispozitiv' => 'Pe dispozitive (piese agățate)',
        'tambur' => 'În tambur (piese mici, în vrac)',
    ];

    public function files()
    {
        return $this->hasMany(QuoteFile::class);
    }

    public function events()
    {
        return $this->hasMany(QuoteEvent::class)->latest();
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Unique, human readable reference: ELG-2026-00042.
     * Generated inside the insert transaction from the auto-increment id, so it can never collide.
     */
    public static function createWithReference(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $q = self::create($attributes + ['reference' => 'TMP-'.bin2hex(random_bytes(8))]);
            $q->reference = sprintf('ELG-%s-%05d', now()->format('Y'), $q->id);
            $q->save();

            return $q;
        });
    }
}
