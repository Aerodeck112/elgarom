<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Form;
use App\Admin\Resources;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/** List / create / edit / delete for the library resources declared in App\Admin\Resources. */
class CrudController extends Controller
{
    private function resource(Request $request, string $key, bool $manage = false): array
    {
        $res = Resources::get($key);
        abort_unless($request->user()->can_($manage ? $res['manage'] : $res['view']), 403);

        return $res;
    }

    public function index(Request $request, string $key)
    {
        $res = $this->resource($request, $key);
        $query = $res['model']::query()->with($res['with'] ?? []);
        if (($term = trim((string) $request->query('q'))) !== '') {
            $query->where(function ($w) use ($res, $term) {
                foreach ($res['search'] as $col) {
                    $w->orWhere($col, 'like', '%'.addcslashes($term, '%_\\').'%');
                }
            });
        }
        foreach ($res['filters'] ?? [] as $col => $filter) {
            $value = $request->query($col);
            if (is_string($value) && isset($filter['options'][$value])) {
                $query->where($col, $value);
            }
        }

        return view('admin.crud.index', ['res' => $res, 'rows' => $query->orderBy($res['order'])->paginate(30)->withQueryString()]);
    }

    public function create(Request $request, string $key)
    {
        $res = $this->resource($request, $key, true);

        return view('admin.crud.form', ['res' => $res, 'row' => new $res['model'](['is_active' => true, 'currency' => setting('quotes.currency', 'EUR')])]);
    }

    public function store(Request $request, string $key)
    {
        $res = $this->resource($request, $key, true);
        $row = new $res['model'];
        $row->fill($this->validated($request, $res, $row))->save();
        AuditLog::record($key.'.creat', $row);

        return redirect()->route('admin.res.index', $key)->with('status', ucfirst($res['singular']).' adăugat(ă).');
    }

    public function edit(Request $request, string $key, int $id)
    {
        $res = $this->resource($request, $key, true);
        $row = $res['model']::findOrFail($id);

        return view('admin.crud.form', ['res' => $res, 'row' => $row, 'history' => ! empty($res['history']) && $request->user()->can_('costs.view') ? $row->priceHistory()->limit(20)->get() : null]);
    }

    public function update(Request $request, string $key, int $id)
    {
        $res = $this->resource($request, $key, true);
        $row = $res['model']::findOrFail($id);
        $row->fill($this->validated($request, $res, $row))->save();
        AuditLog::record($key.'.modificat', $row, ['campuri' => array_keys($row->getChanges())]);

        return redirect()->route('admin.res.index', $key)->with('status', 'Modificările au fost salvate.');
    }

    public function destroy(Request $request, string $key, int $id)
    {
        $res = $this->resource($request, $key, true);
        $row = $res['model']::findOrFail($id);
        try {
            $row->delete();
        } catch (QueryException) {
            return back()->with('error', 'Înregistrarea este folosită în alte documente și nu poate fi ștearsă. O puteți dezactiva.');
        }
        AuditLog::record($key.'.sters', $row);

        return redirect()->route('admin.res.index', $key)->with('status', 'Înregistrarea a fost ștearsă.');
    }

    /** Validated data, limited to the fields this user is allowed to edit. */
    private function validated(Request $request, array $res, $row): array
    {
        $fields = $res['fields'];
        $table = $row->getTable();

        return validator(Form::normalise($fields, $request->only(array_column($fields, 'name'))), Form::rules($fields, $table, $row->getKey()))->validate();
    }
}
