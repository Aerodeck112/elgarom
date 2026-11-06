<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'published_data' => 'array',
        'published_at' => 'datetime',
        'noindex' => 'boolean',
        'is_system' => 'boolean',
        'has_unpublished_changes' => 'boolean',
    ];

    public const TEMPLATES = [
        'home' => 'Acasă (cu scena 3D)',
        'standard' => 'Standard',
        'services' => 'Servicii (listă automată)',
        'process' => 'Procesul de zincare',
        'capabilities' => 'Capacități tehnice (listă automată)',
        'industries' => 'Aplicații și industrii',
        'projects' => 'Proiecte și galerie',
        'quality' => 'Calitate și documente',
        'faq' => 'Întrebări frecvente',
        'quote' => 'Solicită ofertă (formular)',
        'contact' => 'Contact',
        'legal' => 'Politică / document legal',
    ];

    public function sections()
    {
        return $this->hasMany(Section::class)->orderBy('sort');
    }

    public function ogImage()
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_data !== null;
    }

    /** Snapshot of the current draft (page fields + visible sections). */
    public function draftSnapshot(): array
    {
        return [
            'title' => $this->title,
            'lead' => $this->lead,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'og_image_id' => $this->og_image_id,
            'sections' => $this->sections()->where('is_visible', true)->get()
                ->map(fn ($s) => ['id' => $s->id, 'type' => $s->type, 'data' => $s->data ?? []])->values()->all(),
        ];
    }

    public function publish(?int $userId): void
    {
        $this->published_data = $this->draftSnapshot();
        $this->status = 'published';
        $this->published_at = now();
        $this->published_by = $userId;
        $this->has_unpublished_changes = false;
        $this->save();
    }

    public function path(): string
    {
        $prefix = $this->locale === config('elgarom.default_locale') ? '' : '/'.$this->locale;

        return $prefix.($this->slug === '/' ? '/' : '/'.$this->slug);
    }

    public function url(): string
    {
        return url($this->path());
    }

    public static function findPublished(string $slug, ?string $locale = null): ?self
    {
        return self::where('slug', $slug)->where('locale', $locale ?? app()->getLocale())
            ->where('status', 'published')->first();
    }
}
