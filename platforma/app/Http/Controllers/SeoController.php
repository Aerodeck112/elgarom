<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Page;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SeoController extends Controller
{
    public function sitemap()
    {
        $enabled = \App\Support\Locales::enabled();
        $entries = []; // [url, lastmod, alternates]
        foreach (Page::where('status', 'published')->where('noindex', false)->whereIn('locale', $enabled)->orderBy('sort')->get() as $p) {
            $entries[] = [$p->url(), $p->published_at ?? $p->updated_at, $p->alternates()];
        }
        foreach (Service::where('is_published', true)->whereIn('locale', $enabled)->orderBy('sort')->get() as $s) {
            $alt = $s->translation_key ? Service::where('translation_key', $s->translation_key)->where('is_published', true)->whereIn('locale', $enabled)->get()->mapWithKeys(fn ($t) => [$t->locale => service_url($t)])->all() : [];
            $entries[] = [service_url($s), $s->updated_at, $alt];
        }
        foreach (Project::where('is_published', true)->whereIn('locale', $enabled)->orderBy('sort')->get() as $p) {
            $entries[] = [project_url($p), $p->updated_at, []];
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach ($entries as [$loc, $mod, $alt]) {
            $xml .= '  <url><loc>'.e($loc).'</loc>'.($mod ? '<lastmod>'.$mod->toAtomString().'</lastmod>' : '');
            if (count($alt) > 1) {
                foreach ($alt as $code => $href) {
                    $xml .= '<xhtml:link rel="alternate" hreflang="'.e($code).'" href="'.e($href).'"/>';
                }
            }
            $xml .= '</url>'."\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /asistent/', 'Disallow: /solicita-oferta/trimisa', '', 'Sitemap: '.url('/sitemap.xml')];
        if (app()->environment('staging')) {
            $lines = ['User-agent: *', 'Disallow: /'];
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function document(Document $document)
    {
        abort_unless($document->is_public && $document->file_path, 404);
        $path = storage_path('app/private/'.$document->file_path);
        abort_unless(is_file($path), 404);

        return response()->download($path, $document->file_name, [
            'Content-Type' => $document->file_mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** Fallback for unknown URLs: database redirects (301/302/410), otherwise 404. */
    public function fallback(Request $request)
    {
        app()->setLocale(\App\Support\Locales::fromPath($request->getPathInfo()));
        $path = Redirect::normalize($request->getPathInfo());
        $r = Redirect::where('is_active', true)->where('from_path', $path)->first();
        if ($r) {
            Redirect::whereKey($r->id)->update(['hits' => $r->hits + 1, 'last_hit_at' => now()]);
            if ($r->status_code === 410) {
                return response()->view('errors.410', [], 410);
            }

            return redirect()->to(safe_url($r->to_url, '/'), in_array($r->status_code, [301, 302, 307, 308], true) ? $r->status_code : 301);
        }
        abort(404);
    }

    public function health(Request $request)
    {
        $token = (string) @file_get_contents(storage_path('app/private/health.token'));
        if ($token !== '' && hash_equals(trim($token), (string) $request->query('token'))) {
            $checks = ['db' => false, 'storage' => is_writable(storage_path('framework')), 'version' => config('elgarom.version')];
            try {
                \DB::select('select 1');
                $checks['db'] = Page::where('status', 'published')->exists();
            } catch (\Throwable) {
            }

            return response()->json(['ok' => $checks['db'] && $checks['storage']] + $checks);
        }

        return response()->json(['ok' => true]);
    }
}
