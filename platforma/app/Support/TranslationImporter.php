<?php

namespace App\Support;

use App\Models\Capability;
use App\Models\Faq;
use App\Models\Industry;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Section;
use App\Models\Service;
use App\Models\Setting;

/**
 * Imports the German, English and Hungarian content shipped in database/translations/*.php.
 * Idempotent: content that already exists in a language (same translation key) is never overwritten,
 * so edits made in the admin are preserved.
 */
class TranslationImporter
{
    private array $media = [];

    public function run(array $locales = ['de', 'en', 'hu']): array
    {
        $this->backfillKeys();
        if (! Page::where('locale', Locales::default())->exists()) {
            return []; // fresh database: the seeder runs the import after creating the Romanian content
        }
        foreach (Media::all() as $m) {
            $this->media[pathinfo($m->original_name, PATHINFO_FILENAME)] = $m->id;
        }
        $report = [];
        foreach ($locales as $loc) {
            $file = database_path('translations/'.$loc.'.php');
            if (! is_file($file)) {
                continue;
            }
            $report[$loc] = $this->import($loc, require $file);
        }
        $enabled = array_values(array_unique(array_merge((array) Setting::get('locales.enabled', [Locales::default()]), [Locales::default()], array_keys($report))));
        Setting::put('locales.enabled', $enabled);
        Setting::put('locales.review_notice', true);

        return $report;
    }

    /** Romanian rows get a translation key so translations can be linked to them. */
    public function backfillKeys(): void
    {
        $ro = Locales::default();
        foreach (Page::where('locale', $ro)->whereNull('translation_key')->get() as $p) {
            $p->forceFill(['translation_key' => $p->slug === '/' ? 'acasa' : $p->slug])->saveQuietly();
        }
        foreach (Service::where('locale', $ro)->whereNull('translation_key')->get() as $s) {
            $s->forceFill(['translation_key' => $s->slug])->saveQuietly();
        }
        foreach (Capability::where('locale', $ro)->whereNull('translation_key')->get() as $c) {
            $c->forceFill(['translation_key' => $c->key ?: 'cap-'.$c->id])->saveQuietly();
        }
        foreach (Faq::where('locale', $ro)->whereNull('translation_key')->orderBy('sort')->orderBy('id')->get()->values() as $i => $f) {
            $f->forceFill(['translation_key' => 'faq-'.$i])->saveQuietly();
        }
        foreach (Industry::where('locale', $ro)->whereNull('translation_key')->orderBy('sort')->orderBy('id')->get()->values() as $i => $f) {
            $f->forceFill(['translation_key' => 'ind-'.$i])->saveQuietly();
        }
    }

    private function resolve(mixed $v, string $loc, array $slugs): mixed
    {
        if (is_array($v)) {
            return array_map(fn ($x) => $this->resolve($x, $loc, $slugs), $v);
        }
        if (is_string($v) && str_starts_with($v, 'img:')) {
            return $this->media[substr($v, 4)] ?? null;
        }
        if (is_string($v) && str_starts_with($v, 'page:')) {
            $slug = $slugs[substr($v, 5)] ?? null;

            return $slug === null ? lurl('/', $loc) : Locales::prefix($loc).($slug === '/' ? '' : '/'.$slug);
        }

        return $v;
    }

    private function import(string $loc, array $d): array
    {
        $count = ['pagini' => 0, 'servicii' => 0, 'intrebari' => 0, 'capacitati' => 0];
        $slugs = array_map(fn ($p) => $p['slug'], $d['pages']);

        foreach ($d['pages'] as $key => $p) {
            $ro = Page::where('locale', Locales::default())->where('translation_key', $key)->first();
            if (! $ro || Page::where('locale', $loc)->where('translation_key', $key)->exists() || Page::where('locale', $loc)->where('slug', $p['slug'])->exists()) {
                continue;
            }
            $page = Page::create([
                'locale' => $loc, 'slug' => $p['slug'], 'translation_key' => $key, 'template' => $ro->template,
                'title' => $p['title'], 'nav_title' => $p['nav'] ?? null, 'lead' => $p['lead'] ?? null,
                'meta_title' => $p['meta_title'] ?? null, 'meta_description' => $p['meta_description'] ?? null,
                'og_image_id' => $ro->og_image_id, 'noindex' => $ro->noindex, 'sort' => $ro->sort, 'is_system' => $ro->is_system, 'status' => 'draft',
            ]);
            foreach ($p['sections'] as $i => [$type, $data]) {
                $data = $this->resolve($data, $loc, $slugs);
                if (isset($data['images'])) {
                    $data['images'] = array_values(array_filter($data['images']));
                }
                Section::create(['page_id' => $page->id, 'type' => $type, 'sort' => $i, 'data' => $data, 'is_visible' => true]);
            }
            if ($ro->status === 'published') {
                $page->refresh()->publish(null);
            }
            $count['pagini']++;
        }

        foreach ($d['services'] as $key => $s) {
            $ro = Service::where('locale', Locales::default())->where('translation_key', $key)->first();
            if (! $ro || Service::where('locale', $loc)->where('translation_key', $key)->exists()) {
                continue;
            }
            Service::create(['locale' => $loc, 'slug' => $s['slug'], 'translation_key' => $key, 'title' => $s['title'], 'summary' => $s['summary'],
                'body' => $s['body'], 'image_id' => $ro->image_id, 'sort' => $ro->sort, 'is_published' => $ro->is_published]);
            $count['servicii']++;
        }

        foreach ($d['faqs'] as $i => [$cat, $q, $a]) {
            $key = 'faq-'.$i;
            $ro = Faq::where('locale', Locales::default())->where('translation_key', $key)->first();
            if (Faq::where('locale', $loc)->where('translation_key', $key)->exists()) {
                continue;
            }
            Faq::create(['locale' => $loc, 'translation_key' => $key, 'category' => $cat, 'question' => $q, 'answer' => $a,
                'sort' => $ro->sort ?? $i, 'is_published' => $ro ? $ro->is_published : true]);
            $count['intrebari']++;
        }

        foreach (Capability::where('locale', Locales::default())->get() as $ro) {
            $t = $d['capabilities'][$ro->key] ?? null;
            if (! $t || Capability::where('locale', $loc)->where('translation_key', $ro->translation_key)->exists()) {
                continue;
            }
            $value = $ro->value;
            if ($ro->value !== null && isset($t['value']) && ! preg_match('/^[\d\s×x.,]+$/u', $ro->value)) {
                $value = $t['value'];
            }
            Capability::create([
                'locale' => $loc, 'translation_key' => $ro->translation_key, 'group' => $ro->group, 'key' => $ro->key, 'label' => $t['label'],
                'value' => $value, 'unit' => $ro->unit, 'note' => $ro->is_confirmed ? null : $ro->note,
                'source' => $ro->is_confirmed ? ($d['source'] ?? $ro->source) : null,
                'is_confirmed' => $ro->is_confirmed, 'show_on_home' => $ro->show_on_home, 'sort' => $ro->sort,
            ]);
            $count['capacitati']++;
        }

        foreach ($d['industries'] as $i => $title) {
            $key = 'ind-'.$i;
            if (Industry::where('locale', $loc)->where('translation_key', $key)->exists()) {
                continue;
            }
            $ro = Industry::where('locale', Locales::default())->where('translation_key', $key)->first();
            Industry::create(['locale' => $loc, 'translation_key' => $key, 'title' => $title, 'is_published' => $ro?->is_published ?? false, 'sort' => $i]);
        }

        foreach (['header', 'footer'] as $menu) {
            if (MenuItem::where('locale', $loc)->where('menu', $menu)->exists()) {
                continue;
            }
            foreach ($d['menu'][$menu] as $i => $item) {
                MenuItem::create(['locale' => $loc, 'menu' => $menu, 'label' => $item[0], 'url' => $this->resolve('page:'.$item[1], $loc, $slugs),
                    'is_button' => (bool) ($item[2] ?? false), 'sort' => ($item[2] ?? false) ? 99 : $i]);
            }
        }

        foreach ($d['media'] ?? [] as $name => $alt) {
            if (! isset($this->media[$name])) {
                continue;
            }
            $m = Media::find($this->media[$name]);
            $all = $m->alt_i18n ?? [];
            if (empty($all[$loc])) {
                $all[$loc] = $alt;
                $m->forceFill(['alt_i18n' => $all])->saveQuietly();
            }
        }

        foreach ($d['settings'] as $key => $value) {
            if (! Setting::has($key.'.'.$loc)) {
                Setting::put($key.'.'.$loc, $value);
            }
        }

        return $count;
    }
}
