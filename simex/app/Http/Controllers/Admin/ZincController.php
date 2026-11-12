<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\ZincJob;
use App\Services\Numbering;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Electroplating requests. The technical data is captured and checked against the confirmed limits of the line;
 * no cost is computed until production data validates a formula.
 */
class ZincController extends Controller
{
    public const FIELDS = [
        'part_material' => ['Materialul piesei', 'text'], 'treatment' => ['Tipul tratamentului', 'text'], 'thickness_um' => ['Grosimea stratului de zinc (µm)', 'num'],
        'passivation' => ['Tipul pasivării', 'text'], 'top_coat' => ['Top coating', 'text'], 'loading' => ['Tipul de încărcare', 'select'],
        'length_mm' => ['Lungime piesă (mm)', 'num'], 'width_mm' => ['Lățime piesă (mm)', 'num'], 'height_mm' => ['Înălțime piesă (mm)', 'num'],
        'surface_dm2' => ['Suprafața tratată pe piesă (dm²)', 'num'], 'piece_weight_kg' => ['Greutatea piesei (kg)', 'num'], 'pieces' => ['Număr de piese', 'num'],
        'lot_pieces' => ['Piese pe lot', 'num'], 'process_minutes' => ['Timp de proces estimat (min)', 'num'], 'auxiliary' => ['Operații auxiliare', 'text'], 'packaging' => ['Ambalare și transport', 'text'],
    ];

    public function index()
    {
        return view('admin.zinc.index', ['rows' => ZincJob::with('company')->latest('id')->paginate(30)]);
    }

    public function create()
    {
        return view('admin.zinc.form', ['job' => new ZincJob(['inputs' => ['loading' => 'dispozitiv']]), 'companies' => Company::orderBy('name')->pluck('name', 'id')]);
    }

    public function store(Request $request)
    {
        $job = ZincJob::create($this->validated($request) + ['reference' => Numbering::next('zinc', 'ZN'), 'created_by' => $request->user()->id]);
        AuditLog::record('zincare.creata', $job);

        return redirect()->route('admin.zinc.edit', $job)->with('status', 'Fișa '.$job->reference.' a fost salvată.');
    }

    public function edit(ZincJob $zinc)
    {
        return view('admin.zinc.form', ['job' => $zinc, 'companies' => Company::orderBy('name')->pluck('name', 'id')]);
    }

    public function update(Request $request, ZincJob $zinc)
    {
        $zinc->update($this->validated($request));

        return back()->with('status', 'Fișa a fost actualizată.');
    }

    private function validated(Request $request): array
    {
        $rules = ['title' => 'required|string|max:255', 'company_id' => ['nullable', Rule::exists('companies', 'id')], 'notes' => 'nullable|string|max:10000'];
        $input = $request->all();
        foreach (self::FIELDS as $key => [$label, $type]) {
            if ($type === 'num' && isset($input['inputs'][$key]) && is_string($input['inputs'][$key])) {
                $input['inputs'][$key] = str_replace([' ', ','], ['', '.'], $input['inputs'][$key]);
            }
            $rules['inputs.'.$key] = match ($type) {
                'num' => 'nullable|numeric|min:0|max:100000000', 'select' => ['required', Rule::in(['dispozitiv', 'tambur'])], default => 'nullable|string|max:255',
            };
        }
        $data = validator($input, $rules)->validate();
        $data['inputs'] = array_filter($data['inputs'] ?? [], fn ($v) => $v !== null && $v !== '');

        return $data;
    }
}
