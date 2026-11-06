<?php

namespace App\Http\Controllers\Admin;

use App\Admin\FormData;
use App\Admin\Resources;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\UploadInspector;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResourceController extends Controller
{
    private function res(Request $request, string $key): array
    {
        $r = Resources::get($key);
        abort_unless($request->user()->can_($r['perm']), 403);

        return $r;
    }

    public function index(Request $request, string $key)
    {
        $r = $this->res($request, $key);
        $q = $r['model']::query();
        if (in_array('locale', (new $r['model'])->getConnection()->getSchemaBuilder()->getColumnListing((new $r['model'])->getTable()), true)) {
            $q->where('locale', $request->query('locale', config('elgarom.default_locale')));
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($r, $s) {
                foreach ($r['search'] as $col) {
                    $w->orWhere($col, 'like', '%'.addcslashes($s, '%_\\').'%');
                }
            });
        }
        foreach ($r['filter'] ?? [] as $col => $opts) {
            if (($v = $request->query($col)) && isset($opts[$v])) {
                $q->where($col, $v);
            }
        }
        $items = $q->orderBy($r['order'])->orderBy('id')->paginate(50)->withQueryString();

        return view('admin.resources.index', ['r' => $r, 'items' => $items]);
    }

    public function create(Request $request, string $key)
    {
        $r = $this->res($request, $key);
        $item = new $r['model'];
        $item->forceFill(['is_published' => false, 'is_visible' => true, 'is_active' => true, 'status_code' => 301]);

        return view('admin.resources.form', ['r' => $r, 'item' => $item]);
    }

    public function edit(Request $request, string $key, int $id)
    {
        $r = $this->res($request, $key);

        return view('admin.resources.form', ['r' => $r, 'item' => $r['model']::findOrFail($id)]);
    }

    public function store(Request $request, string $key)
    {
        $r = $this->res($request, $key);

        return $this->save($request, $r, new $r['model']);
    }

    public function update(Request $request, string $key, int $id)
    {
        $r = $this->res($request, $key);

        return $this->save($request, $r, $r['model']::findOrFail($id));
    }

    private function save(Request $request, array $r, $item)
    {
        $fields = $r['fields'];
        $validated = $request->validate(FormData::rules($fields));
        $validated = FormData::checkboxesFromRequest($fields, $request, $validated);
        $data = FormData::values($fields, $validated);

        foreach ($fields as $f) {
            if ($f['type'] === 'slug') {
                $slug = $data[$f['name']] ?: Str::slug($data[$f['from']] ?? '');
                $base = $slug ?: 'element';
                $i = 1;
                while ($r['model']::where('slug', $slug)->where('id', '!=', $item->id ?? 0)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $data[$f['name']] = $slug;
            }
            if ($f['type'] === 'file' && $request->hasFile($f['name'])) {
                $file = $request->file($f['name']);
                if ($p = app(UploadInspector::class)->problem($file, ['pdf', 'jpg', 'jpeg', 'png'])) {
                    return back()->withInput()->withErrors([$f['name'] => $p]);
                }
                $ext = strtolower($file->getClientOriginalExtension());
                $name = Str::random(40).'.'.$ext;
                $dir = storage_path('app/private/documents');
                if (! is_dir($dir)) {
                    mkdir($dir, 0700, true);
                }
                $mime = app(UploadInspector::class)->detectedMime($file->getRealPath());
                $orig = app(UploadInspector::class)->name($file);
                $file->move($dir, $name);
                if ($item->file_path) {
                    @unlink(storage_path('app/private/'.$item->file_path));
                }
                $data += ['file_path' => 'documents/'.$name, 'file_name' => $orig, 'file_mime' => $mime, 'file_size' => filesize($dir.'/'.$name)];
            }
        }
        if ($r['key'] === 'knowledge') {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = $request->user()->id;
        }
        if ($r['key'] === 'redirects') {
            $data['from_path'] = \App\Models\Redirect::normalize($data['from_path']);
            $data['status_code'] = (int) $data['status_code'];
        }
        $isNew = ! $item->exists;
        $before = $item->getAttributes();
        $item->fill($data);
        $changed = array_keys($item->getDirty());
        $item->save();
        AuditLog::record($r['key'].($isNew ? '.creat' : '.modificat'), $item, ['campuri' => $changed]);
        if (in_array($r['key'], ['menu_items'], true)) {
            cache()->forget('menus');
        }

        return redirect()->route('admin.res.edit', [$r['key'], $item->id])->with('status', 'Modificările au fost salvate.');
    }

    public function destroy(Request $request, string $key, int $id)
    {
        $r = $this->res($request, $key);
        $item = $r['model']::findOrFail($id);
        if (! empty($item->file_path)) {
            @unlink(storage_path('app/private/'.$item->file_path));
        }
        $item->delete();
        AuditLog::record($r['key'].'.sters', $item, ['id' => $id]);

        return redirect()->route('admin.res.index', $key)->with('status', 'Element șters.');
    }

    public function move(Request $request, string $key, int $id)
    {
        $r = $this->res($request, $key);
        abort_unless($r['order'] === 'sort', 404);
        $items = $r['model']::orderBy('sort')->orderBy('id')->get()->values();
        $idx = $items->search(fn ($m) => $m->id === $id);
        $swap = $request->input('dir') === 'up' ? $idx - 1 : $idx + 1;
        if ($idx !== false && isset($items[$swap])) {
            $tmp = $items[$idx];
            $items[$idx] = $items[$swap];
            $items[$swap] = $tmp;
            foreach ($items as $i => $m) {
                if ($m->sort !== $i) {
                    $m->forceFill(['sort' => $i])->save();
                }
            }
        }

        return back();
    }

    public function document(Request $request, int $id)
    {
        abort_unless($request->user()->can_('content'), 403);
        $doc = \App\Models\Document::findOrFail($id);
        abort_unless($doc->file_path && is_file(storage_path('app/private/'.$doc->file_path)), 404);

        return response()->download(storage_path('app/private/'.$doc->file_path), $doc->file_name);
    }
}
