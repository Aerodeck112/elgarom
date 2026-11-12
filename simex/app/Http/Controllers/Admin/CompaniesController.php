<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Support\Vocab;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompaniesController extends Controller
{
    public function index(Request $request)
    {
        $q = Company::query()->with('owner')->withCount('contacts');
        if (($term = trim((string) $request->query('q'))) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('city', 'like', $like)->orWhere('tax_id', 'like', $like)->orWhere('industry', 'like', $like));
        }
        foreach (['stage' => Vocab::COMPANY_STAGES, 'country' => Vocab::countries(), 'kind' => Vocab::COMPANY_KINDS] as $col => $allowed) {
            if (isset($allowed[$request->query($col)])) {
                $q->where($col, $request->query($col));
            }
        }

        return view('admin.companies.index', [
            'rows' => $q->orderBy('name')->paginate(30)->withQueryString(),
            'stages' => Company::selectRaw('stage, count(*) as n')->groupBy('stage')->pluck('n', 'stage'),
        ]);
    }

    public function create()
    {
        return view('admin.companies.form', ['company' => new Company(['stage' => 'lead', 'kind' => 'client', 'language' => 'ro', 'country' => 'RO', 'owner_id' => auth()->id()]), 'users' => $this->users()]);
    }

    public function store(Request $request)
    {
        $company = Company::create($this->validated($request));
        AuditLog::record('companie.creata', $company);

        return redirect()->route('admin.companies.show', $company)->with('status', 'Compania a fost adăugată.');
    }

    public function show(Company $company)
    {
        $company->load(['contacts', 'owner', 'rfqs' => fn ($q) => $q->latest()->limit(20), 'quotes' => fn ($q) => $q->latest()->limit(20), 'activities' => fn ($q) => $q->with('user')->limit(50)]);

        return view('admin.companies.show', ['company' => $company, 'users' => $this->users()]);
    }

    public function edit(Company $company)
    {
        return view('admin.companies.form', ['company' => $company, 'users' => $this->users()]);
    }

    public function update(Request $request, Company $company)
    {
        $company->update($this->validated($request));
        AuditLog::record('companie.modificata', $company, ['campuri' => array_keys($company->getChanges())]);

        return redirect()->route('admin.companies.show', $company)->with('status', 'Modificările au fost salvate.');
    }

    public function destroy(Company $company)
    {
        $company->delete();
        AuditLog::record('companie.arhivata', $company);

        return redirect()->route('admin.companies.index')->with('status', 'Compania a fost arhivată. Ofertele și cererile ei rămân în istoric.');
    }

    public function contactStore(Request $request, Company $company)
    {
        $data = $this->contactData($request);
        if ($data['is_primary']) {
            $company->contacts()->update(['is_primary' => false]);
        }
        $contact = $company->contacts()->create($data);
        AuditLog::record('contact.creat', $contact);

        return back()->with('status', 'Contactul a fost adăugat.');
    }

    public function contactUpdate(Request $request, Company $company, Contact $contact)
    {
        abort_unless($contact->company_id === $company->id, 404);
        $data = $this->contactData($request);
        if ($data['is_primary']) {
            $company->contacts()->whereKeyNot($contact->id)->update(['is_primary' => false]);
        }
        $contact->update($data);
        AuditLog::record('contact.modificat', $contact);

        return back()->with('status', 'Contactul a fost actualizat.');
    }

    public function contactDestroy(Company $company, Contact $contact)
    {
        abort_unless($contact->company_id === $company->id, 404);
        $contact->delete();
        AuditLog::record('contact.sters', $contact);

        return back()->with('status', 'Contactul a fost șters.');
    }

    public function contactsJson(Company $company)
    {
        return response()->json($company->contacts()->orderByDesc('is_primary')->orderBy('last_name')->get()->map(fn ($c) => ['id' => $c->id, 'label' => $c->fullName().($c->email ? ' · '.$c->email : '')]));
    }

    public function activityStore(Request $request, Company $company)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(array_diff_key(Vocab::ACTIVITY_TYPES, ['sistem' => 1])))],
            'title' => 'required|string|max:255', 'body' => 'nullable|string|max:5000', 'due_at' => 'nullable|date',
            'assigned_to' => ['nullable', Rule::exists('users', 'id')], 'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('company_id', $company->id)],
        ]);
        $company->activities()->create($data + ['user_id' => $request->user()->id, 'done_at' => $data['type'] === 'sarcina' ? null : now()]);
        if ($data['type'] === 'sarcina' && ! empty($data['due_at']) && ($company->next_follow_up_at === null || $company->next_follow_up_at->gt($data['due_at']))) {
            $company->update(['next_follow_up_at' => $data['due_at']]);
        }

        return back()->with('status', 'Activitatea a fost înregistrată.');
    }

    public function activityDone(Company $company, Activity $activity)
    {
        abort_unless($activity->company_id === $company->id, 404);
        $activity->update(['done_at' => now()]);
        $next = $company->activities()->where('type', 'sarcina')->whereNull('done_at')->min('due_at');
        $company->update(['next_follow_up_at' => $next]);

        return back()->with('status', 'Sarcina a fost închisă.');
    }

    private function users()
    {
        return User::where('is_active', true)->orderBy('name')->pluck('name', 'id');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'legal_name' => 'nullable|string|max:255', 'kind' => ['required', Rule::in(array_keys(Vocab::COMPANY_KINDS))],
            'country' => ['nullable', Rule::in(array_keys(Vocab::countries()))], 'county' => 'nullable|string|max:120', 'city' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:255', 'postal_code' => 'nullable|string|max:20', 'tax_id' => 'nullable|string|max:40', 'reg_no' => 'nullable|string|max:40',
            'industry' => 'nullable|string|max:120', 'website' => 'nullable|url:http,https|max:255', 'email' => 'nullable|email|max:190', 'phone' => 'nullable|string|max:40',
            'language' => ['required', Rule::in(array_keys(config('simex.languages')))], 'source' => ['nullable', Rule::in(array_keys(Vocab::LEAD_SOURCES))],
            'interests' => 'nullable|array', 'interests.*' => [Rule::in(array_keys(Vocab::SERVICES))], 'stage' => ['required', Rule::in(array_keys(Vocab::COMPANY_STAGES))],
            'owner_id' => ['nullable', Rule::exists('users', 'id')], 'next_follow_up_at' => 'nullable|date', 'notes' => 'nullable|string|max:10000',
            'lawful_basis' => ['nullable', Rule::in(array_keys(Vocab::LAWFUL_BASES))],
        ]);
        $data['do_not_contact'] = $request->boolean('do_not_contact');
        $data['interests'] = $data['interests'] ?? [];

        return $data;
    }

    private function contactData(Request $request): array
    {
        $data = $request->validate([
            'first_name' => 'nullable|string|max:120', 'last_name' => 'required|string|max:120', 'position' => 'nullable|string|max:160', 'email' => 'nullable|email|max:190',
            'phone' => 'nullable|string|max:40', 'mobile' => 'nullable|string|max:40', 'language' => ['required', Rule::in(array_keys(config('simex.languages')))], 'notes' => 'nullable|string|max:2000',
        ]);

        return $data + ['is_primary' => $request->boolean('is_primary'), 'do_not_contact' => $request->boolean('do_not_contact')];
    }
}
