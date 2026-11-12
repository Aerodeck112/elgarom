<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Support\Facades\Storage;

/** Public website: one elegant page, the privacy policy and technical endpoints. Nothing internal is exposed here. */
class SiteController extends Controller
{
    public function home()
    {
        $entities = Entity::where('show_on_site', true)->orderBy('sort')->get();

        return view('site.home', ['group' => $entities->firstWhere('code', 'grup-simex'), 'companies' => $entities->where('code', '!=', 'grup-simex')->values()]);
    }

    public function privacy()
    {
        abort_unless(filled(setting('site.privacy')), 404);

        return view('site.privacy', ['group' => Entity::where('code', 'grup-simex')->first()]);
    }

    public function logo(Entity $entity)
    {
        abort_unless($entity->logo_path && Storage::disk('local')->exists($entity->logo_path), 404);
        $ext = strtolower(pathinfo($entity->logo_path, PATHINFO_EXTENSION));
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'][$ext] ?? 'application/octet-stream';

        return response(Storage::disk('local')->get($entity->logo_path), 200, [
            'Content-Type' => $mime, 'Cache-Control' => 'public, max-age=604800', 'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
        ]);
    }

    public function robots()
    {
        $body = setting('site.indexable') ? "User-agent: *\nDisallow: /crm\nDisallow: /oferta\nSitemap: ".url('/sitemap.xml')."\n" : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function sitemap()
    {
        $urls = [url('/')];
        if (filled(setting('site.privacy'))) {
            $urls[] = route('privacy');
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml .= '<url><loc>'.e($u).'</loc></url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function health()
    {
        return response()->json(['ok' => true, 'version' => config('simex.version')]);
    }
}
