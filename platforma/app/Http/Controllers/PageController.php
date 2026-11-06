<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Support\StructuredData;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /** Old WordPress page ids => new paths. */
    private const LEGACY_IDS = ['824' => '/', '11' => '/despre', '19' => '/procesul-de-zincare', '24' => '/contact'];

    public function home(Request $request)
    {
        foreach (['p', 'page_id'] as $param) {
            if ($request->has($param)) {
                $to = self::LEGACY_IDS[(string) $request->query($param)] ?? '/';

                return redirect($to, 301);
            }
        }
        $page = Page::findPublished('/');
        abort_unless($page, 404);

        return $this->render($page, $page->published_data);
    }

    public function show(string $slug)
    {
        $page = Page::findPublished($slug);
        if (! $page || ! $page->published_data) {
            return app(SeoController::class)->fallback(request());
        }
        if ($page->template === 'home') {
            return redirect('/', 301);
        }

        return $this->render($page, $page->published_data);
    }

    public function render(Page $page, array $data, bool $preview = false)
    {
        $crumbs = [[$data['title'], null]];
        $structured = [];
        if ($page->slug !== '/') {
            $structured[] = StructuredData::breadcrumbs([[__('Acasă'), lurl('/')], [$data['title'], $page->url()]]);
        }
        if ($page->template === 'faq') {
            $faqs = Faq::published()->get();
            if ($faqs->isNotEmpty()) {
                $structured[] = StructuredData::faq($faqs);
            }
        }
        $view = $page->template === 'home' ? 'pages.home' : 'pages.page';

        return response()->view($view, [
            'page' => $page,
            'data' => $data,
            'crumbs' => $crumbs,
            'structured' => $structured,
            'metaTitle' => ($data['meta_title'] ?? null) ?: $data['title'].' | '.setting('seo.site_name', 'ELGAROM'),
            'metaDescription' => ($data['meta_description'] ?? null) ?: setting('seo.default_description'),
            'ogImageId' => $data['og_image_id'] ?? null,
            'canonical' => $page->url(),
            'noindex' => $page->noindex || $preview,
            'alternates' => $preview ? [] : $page->alternates(),
            'preview' => $preview,
        ]);
    }

    public function service(string $slug)
    {
        $service = Service::published()->where('slug', $slug)->firstOrFail();
        $others = Service::published()->where('id', '!=', $service->id)->get();

        return view('pages.service', [
            'service' => $service,
            'others' => $others,
            'metaTitle' => $service->title.' | '.setting('seo.site_name', 'ELGAROM'),
            'metaDescription' => $service->summary,
            'ogImageId' => $service->image_id,
            'canonical' => service_url($service),
            'alternates' => $this->alternatesFor(Service::class, $service),
            'structured' => [
                StructuredData::service($service),
                StructuredData::breadcrumbs([[__('Acasă'), lurl('/')], [__('Servicii'), page_url('servicii')], [$service->title, service_url($service)]]),
            ],
        ]);
    }

    public function project(string $slug)
    {
        $project = Project::published()->where('slug', $slug)->firstOrFail();

        return view('pages.project', [
            'project' => $project,
            'metaTitle' => $project->title.' | '.setting('seo.site_name', 'ELGAROM'),
            'metaDescription' => $project->summary,
            'ogImageId' => $project->cover_id,
            'canonical' => project_url($project),
            'alternates' => $this->alternatesFor(Project::class, $project),
        ]);
    }

    private function alternatesFor(string $model, $item): array
    {
        if (! $item->translation_key) {
            return [];
        }
        $out = [];
        foreach ($model::where('translation_key', $item->translation_key)->where('is_published', true)->whereIn('locale', \App\Support\Locales::enabled())->get() as $t) {
            $out[$t->locale] = $model === Service::class ? service_url($t) : project_url($t);
        }

        return $out;
    }
}
