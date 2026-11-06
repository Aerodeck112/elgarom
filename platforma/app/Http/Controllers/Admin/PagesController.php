<?php

namespace App\Http\Controllers\Admin;

use App\Admin\FormData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\Section;
use App\Support\Locales;
use App\Support\SectionTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PagesController extends Controller
{
    private const RESERVED = ['admin', 'servicii', 'proiecte', 'documente', 'asistent', 'media', 'build', 'health', 'mesaj', 'sitemap', 'install', 'solicita-oferta', 'de', 'en', 'hu'];

    private function locale(Request $request): string
    {
        $loc = (string) $request->input('locale', Locales::default());

        return isset(Locales::all()[$loc]) ? $loc : Locales::default();
    }

    public function index(Request $request)
    {
        $loc = $this->locale($request);
        $pages = Page::where('locale', $loc)->orderBy('sort')->get();
        // Romanian pages that have no version in this language yet
        $missing = $loc === Locales::default() ? collect() : Page::where('locale', Locales::default())
            ->whereNotIn('translation_key', Page::where('locale', $loc)->whereNotNull('translation_key')->pluck('translation_key'))->orderBy('sort')->get();

        return view('admin.pages.index', ['pages' => $pages, 'loc' => $loc, 'missing' => $missing]);
    }

    public function create(Request $request)
    {
        return view('admin.pages.create', ['templates' => Page::TEMPLATES, 'loc' => $this->locale($request)]);
    }

    /** Creates the version of a page in another language as a draft: copies the page data and all sections. */
    public function translate(Request $request, Page $page)
    {
        $loc = $this->locale($request);
        abort_if($loc === $page->locale, 422);
        if (! $page->translation_key) {
            $page->forceFill(['translation_key' => $page->slug === '/' ? 'acasa' : $page->slug])->saveQuietly();
        }
        if ($existing = Page::where('locale', $loc)->where('translation_key', $page->translation_key)->first()) {
            return redirect()->route('admin.pages.edit', $existing)->with('warning', 'Pagina are deja o versiune în această limbă.');
        }
        $slug = $page->slug;
        if ($slug !== '/') {
            $base = $slug;
            $i = 1;
            while (Page::where('locale', $loc)->where('slug', $slug)->exists()) {
                $slug = $base.'-'.(++$i);
            }
        }
        $copy = $page->replicate(['published_data', 'published_at', 'published_by', 'has_unpublished_changes', 'translation_key']);
        $copy->fill(['locale' => $loc, 'slug' => $slug, 'status' => 'draft']);
        $copy->translation_key = $page->translation_key;
        $copy->save();
        foreach ($page->sections as $sec) {
            Section::create(['page_id' => $copy->id, 'type' => $sec->type, 'sort' => $sec->sort, 'data' => $sec->data, 'is_visible' => $sec->is_visible]);
        }
        AuditLog::record('pagina.tradusa', $copy, ['din' => $page->id, 'limba' => $loc]);

        return redirect()->route('admin.pages.edit', $copy)->with('warning', 'Ciorna în '.Locales::all()[$loc]['name'].' a fost creată cu textele din original. Traduceți titlul, adresa, SEO și fiecare secțiune, apoi publicați.');
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'title' => 'required|string|max:160',
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/'],
            'template' => ['required', Rule::in(array_diff(array_keys(Page::TEMPLATES), ['home']))],
        ]);
        $loc = $this->locale($request);
        $slug = $d['slug'] ?: Str::slug($d['title']);
        if (in_array($slug, self::RESERVED, true) || in_array($slug, array_column(array_column(Locales::all(), 'segments'), 'services'), true) || Page::where('slug', $slug)->where('locale', $loc)->exists()) {
            return back()->withInput()->withErrors(['slug' => 'Adresa „'.$slug.'” este deja folosită sau rezervată.']);
        }
        $page = Page::create(['title' => $d['title'], 'slug' => $slug, 'template' => $d['template'], 'status' => 'draft', 'sort' => (int) Page::max('sort') + 10, 'locale' => $loc]);
        $page->forceFill(['translation_key' => $loc === Locales::default() ? $slug : 'pagina-'.$page->id])->saveQuietly();
        AuditLog::record('pagina.creata', $page, ['slug' => $slug]);

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Pagina a fost creată ca ciornă.');
    }

    public function edit(Page $page)
    {
        $translations = $page->translation_key
            ? Page::where('translation_key', $page->translation_key)->where('id', '!=', $page->id)->get()->keyBy('locale')->all() : [];

        return view('admin.pages.edit', ['page' => $page->load('sections'), 'types' => SectionTypes::all(), 'templates' => Page::TEMPLATES, 'translations' => $translations]);
    }

    public function update(Request $request, Page $page)
    {
        $d = $request->validate([
            'title' => 'required|string|max:160',
            'nav_title' => 'nullable|string|max:60',
            'lead' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:170',
            'og_image_id' => 'nullable|integer|exists:media,id',
            'noindex' => 'nullable|boolean',
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/'],
        ], ['meta_title.max' => 'Titlul SEO poate avea cel mult 70 de caractere.', 'meta_description.max' => 'Descrierea SEO poate avea cel mult 170 de caractere.']);
        $d['noindex'] = $request->boolean('noindex');
        if ($page->slug === '/' || ($page->is_system && $page->locale === Locales::default())) {
            unset($d['slug']);
        } elseif (! empty($d['slug']) && $d['slug'] !== $page->slug) {
            if (in_array($d['slug'], self::RESERVED, true) || Page::where('slug', $d['slug'])->where('locale', $page->locale)->where('id', '!=', $page->id)->exists()) {
                return back()->withInput()->withErrors(['slug' => 'Adresa este deja folosită sau rezervată.']);
            }
            // keep old links working (only once the page has been public)
            if ($page->status === 'published' || $page->published_at) {
                $prefix = Locales::prefix($page->locale);
                \App\Models\Redirect::updateOrCreate(['from_path' => $prefix.'/'.$page->slug], ['to_url' => $prefix.'/'.$d['slug'], 'status_code' => 301, 'is_active' => true]);
            }
        } else {
            unset($d['slug']);
        }
        $page->fill($d);
        $page->has_unpublished_changes = true;
        $page->save();
        AuditLog::record('pagina.modificata', $page, ['campuri' => array_keys($d)]);

        return back()->with('status', 'Ciorna a fost salvată. Publicați pentru a o afișa pe site.');
    }

    public function publish(Request $request, Page $page)
    {
        $page->publish($request->user()->id);
        AuditLog::record('pagina.publicata', $page);

        return back()->with('status', 'Pagina a fost publicată.');
    }

    public function unpublish(Page $page)
    {
        abort_if($page->slug === '/', 422, 'Pagina principală nu poate fi retrasă.');
        $page->update(['status' => 'draft']);
        AuditLog::record('pagina.retrasa', $page);

        return back()->with('status', 'Pagina a fost retrasă de pe site (rămâne ca ciornă).');
    }

    public function destroy(Page $page)
    {
        abort_if($page->is_system, 422, 'Paginile de sistem nu pot fi șterse; le puteți retrage.');
        $page->delete();
        AuditLog::record('pagina.stearsa', $page);

        return redirect()->route('admin.pages.index', ['locale' => $page->locale])->with('status', 'Pagina a fost ștearsă.');
    }

    public function previewLink(Page $page)
    {
        return redirect(URL::temporarySignedRoute('admin.pages.preview', now()->addMinutes(30), ['page' => $page->id]));
    }

    public function preview(Request $request, Page $page)
    {
        $page->load('sections');
        app()->setLocale($page->locale);
        \Carbon\Carbon::setLocale($page->locale);
        $resp = app(PageController::class)->render($page, $page->draftSnapshot(), true);

        return $resp;
    }

    /* ---------- sections ---------- */

    public function addSection(Request $request, Page $page)
    {
        $type = $request->validate(['type' => ['required', Rule::in(array_keys(SectionTypes::all()))]])['type'];
        $s = Section::create(['page_id' => $page->id, 'type' => $type, 'sort' => (int) $page->sections()->max('sort') + 1, 'data' => []]);
        AuditLog::record('sectiune.adaugata', $s, ['pagina' => $page->id, 'tip' => $type]);

        return redirect()->to(route('admin.pages.edit', $page).'#s'.$s->id)->with('status', 'Secțiune adăugată. Completați-o și salvați.');
    }

    public function updateSection(Request $request, Page $page, Section $section)
    {
        abort_unless($section->page_id === $page->id, 404);
        $def = SectionTypes::get($section->type);
        abort_unless($def, 404);
        $validated = $request->validate(FormData::rules($def['fields'], 'data.'));
        $data = FormData::values($def['fields'], $validated['data'] ?? []);
        $section->update(['data' => $data, 'is_visible' => $request->boolean('is_visible')]);
        AuditLog::record('sectiune.modificata', $section, ['pagina' => $page->id]);

        return redirect()->to(route('admin.pages.edit', $page).'#s'.$section->id)->with('status', 'Secțiunea a fost salvată în ciornă.');
    }

    public function moveSection(Request $request, Page $page, Section $section)
    {
        abort_unless($section->page_id === $page->id, 404);
        $list = $page->sections()->get()->values();
        $i = $list->search(fn ($s) => $s->id === $section->id);
        $j = $request->input('dir') === 'up' ? $i - 1 : $i + 1;
        if (isset($list[$j])) {
            [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
            foreach ($list as $k => $s) {
                $s->sort !== $k && $s->update(['sort' => $k]);
            }
        }

        return redirect()->to(route('admin.pages.edit', $page).'#s'.$section->id);
    }

    public function deleteSection(Page $page, Section $section)
    {
        abort_unless($section->page_id === $page->id, 404);
        $section->delete();
        AuditLog::record('sectiune.stearsa', $section, ['pagina' => $page->id]);

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Secțiunea a fost ștearsă din ciornă.');
    }
}
