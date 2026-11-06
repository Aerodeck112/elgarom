<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private MediaService $svc) {}

    public function index(Request $request)
    {
        $q = Media::query()->latest('id');
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('alt', 'like', "%$s%")->orWhere('original_name', 'like', "%$s%"));
        }
        if ($f = $request->query('folder')) {
            $q->where('folder', $f);
        }

        return view('admin.media.index', [
            'items' => $q->paginate(48)->withQueryString(),
            'folders' => Media::distinct()->orderBy('folder')->pluck('folder'),
            'missingAlt' => Media::where(fn ($w) => $w->whereNull('alt')->orWhere('alt', ''))->count(),
        ]);
    }

    public function json(Request $request)
    {
        $q = Media::where('mime', 'like', 'image/%')->latest('id');
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('alt', 'like', "%$s%")->orWhere('original_name', 'like', "%$s%"));
        }

        return response()->json($q->limit(120)->get()->map(fn ($m) => ['id' => $m->id, 'thumb' => $m->variantUrl(480), 'alt' => $m->alt, 'name' => $m->original_name]));
    }

    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array|max:20',
            'files.*' => 'file|max:'.config('elgarom.uploads.media_max_kb').'|mimes:jpg,jpeg,png,webp',
            'folder' => 'nullable|string|max:60|regex:/^[a-z0-9\-]+$/',
        ], ['files.*.mimes' => 'Sunt acceptate doar imagini JPG, PNG sau WebP.']);
        $ok = 0;
        $errors = [];
        foreach ($request->file('files') as $file) {
            try {
                $m = $this->svc->storeImage($file->getRealPath(), $file->getClientOriginalName(), $request->input('folder') ?: 'general', null, $request->user()->id);
                AuditLog::record('media.incarcat', $m, ['nume' => $m->original_name]);
                $ok++;
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName().': '.$e->getMessage();
            }
        }
        $msg = $ok.' imagini încărcate și optimizate (WebP 480–2400 px). Completați textul alternativ.';

        return back()->with($errors ? 'warning' : 'status', $errors ? $msg.' Erori: '.implode(' | ', $errors) : $msg);
    }

    public function update(Request $request, Media $media)
    {
        $d = $request->validate([
            'alt' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:255',
            'folder' => 'nullable|string|max:60|regex:/^[a-z0-9\-]+$/',
            'focal' => ['nullable', 'regex:/^\d{1,3}% \d{1,3}%$/'],
        ]);
        $media->update(array_filter($d, fn ($v) => $v !== null) + ['alt' => $d['alt'] ?? null, 'caption' => $d['caption'] ?? null]);
        AuditLog::record('media.modificat', $media);

        return back()->with('status', 'Imaginea a fost actualizată.');
    }

    public function regenerate(Media $media)
    {
        try {
            $this->svc->regenerate($media);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Variantele optimizate au fost regenerate.');
    }

    public function destroy(Media $media)
    {
        $used = $this->usage($media);
        if ($used) {
            return back()->with('error', 'Imaginea este folosită în: '.implode(', ', $used).'. Înlocuiți-o acolo înainte de ștergere.');
        }
        $this->svc->delete($media);
        AuditLog::record('media.sters', $media);

        return back()->with('status', 'Imaginea a fost ștearsă.');
    }

    private function usage(Media $m): array
    {
        $id = $m->id;
        $used = [];
        foreach (\App\Models\Section::all() as $s) {
            if (str_contains(json_encode($s->data), ':'.$id.',') || str_contains(json_encode($s->data), ':'.$id.'}') || in_array($id, (array) ($s->data['images'] ?? []), true)) {
                $used[] = 'pagina „'.$s->page?->title.'”';
            }
        }
        foreach (\App\Models\Page::whereNotNull('published_data')->get() as $p) {
            if (preg_match('/"(image|og_image_id)":'.$id.'[,}]/', json_encode($p->published_data)) || preg_match('/"images":\[[^\]]*\b'.$id.'\b/', json_encode($p->published_data))) {
                $used[] = 'pagina publicată „'.$p->title.'”';
            }
        }
        if (\App\Models\Service::where('image_id', $id)->exists()) {
            $used[] = 'servicii';
        }
        if (\App\Models\Project::where('cover_id', $id)->exists() || \App\Models\Project::whereJsonContains('gallery', $id)->exists()) {
            $used[] = 'proiecte';
        }
        foreach (['company.logo_id', 'company.logo_dark_id', 'seo.default_og_image_id'] as $k) {
            if ((int) setting($k) === $id) {
                $used[] = 'setări ('.$k.')';
            }
        }

        return array_values(array_unique($used));
    }
}
