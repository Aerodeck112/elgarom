<?php

namespace App\Http\Controllers\Admin;

use App\Admin\FormData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\Section;
use App\Support\SectionTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PagesController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => Page::where('locale', config('elgarom.default_locale'))->orderBy('sort')->get()]);
    }

    public function create()
    {
        return view('admin.pages.create', ['templates' => Page::TEMPLATES]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'title' => 'required|string|max:160',
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/'],
            'template' => ['required', Rule::in(array_diff(array_keys(Page::TEMPLATES), ['home']))],
        ]);
        $slug = $d['slug'] ?: Str::slug($d['title']);
        $reserved = ['admin', 'servicii', 'proiecte', 'documente', 'asistent', 'media', 'build', 'health', 'mesaj', 'sitemap', 'install'];
        if (in_array($slug, $reserved, true) || Page::where('slug', $slug)->where('locale', config('elgarom.default_locale'))->exists()) {
            return back()->withInput()->withErrors(['slug' => 'Adresa „'.$slug.'” este deja folosită sau rezervată.']);
        }
        $page = Page::create(['title' => $d['title'], 'slug' => $slug, 'template' => $d['template'], 'status' => 'draft', 'sort' => (int) Page::max('sort') + 10, 'locale' => config('elgarom.default_locale')]);
        AuditLog::record('pagina.creata', $page, ['slug' => $slug]);

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Pagina a fost creată ca ciornă.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', ['page' => $page->load('sections'), 'types' => SectionTypes::all(), 'templates' => Page::TEMPLATES]);
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
        if ($page->is_system || $page->slug === '/') {
            unset($d['slug']);
        } elseif (! empty($d['slug']) && $d['slug'] !== $page->slug) {
            if (Page::where('slug', $d['slug'])->where('id', '!=', $page->id)->exists()) {
                return back()->withInput()->withErrors(['slug' => 'Adresa este deja folosită.']);
            }
            // keep old links working
            \App\Models\Redirect::updateOrCreate(['from_path' => '/'.$page->slug], ['to_url' => '/'.$d['slug'], 'status_code' => 301, 'is_active' => true]);
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

        return redirect()->route('admin.pages.index')->with('status', 'Pagina a fost ștearsă.');
    }

    public function previewLink(Page $page)
    {
        return redirect(URL::temporarySignedRoute('admin.pages.preview', now()->addMinutes(30), ['page' => $page->id]));
    }

    public function preview(Request $request, Page $page)
    {
        $page->load('sections');
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
