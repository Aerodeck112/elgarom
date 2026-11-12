<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Companies of the group: public presentation, logo and the legal identity used on quotes. */
class EntitiesController extends Controller
{
    public function index()
    {
        return view('admin.entities.index', ['rows' => Entity::orderBy('sort')->get()]);
    }

    public function edit(Entity $entity)
    {
        return view('admin.entities.form', ['entity' => $entity]);
    }

    public function update(Request $request, Entity $entity)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'activity' => 'nullable|string|max:255', 'description' => 'nullable|string|max:2000', 'website' => 'nullable|url:http,https|max:255',
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'sort' => 'nullable|integer|min:0|max:1000',
            'legal_name' => 'nullable|string|max:255', 'tax_id' => 'nullable|string|max:40', 'reg_no' => 'nullable|string|max:40', 'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120', 'county' => 'nullable|string|max:120', 'postal_code' => 'nullable|string|max:20', 'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:190', 'bank' => 'nullable|string|max:255', 'iban' => 'nullable|string|max:40', 'quote_prefix' => ['nullable', 'regex:/^[A-Z0-9]{1,10}$/'],
            'logo' => 'nullable|file|max:'.config('simex.uploads.logo_max_kb'),
        ]);
        $data['show_on_site'] = $request->boolean('show_on_site');
        $data['is_issuer'] = $request->boolean('is_issuer');
        unset($data['logo']);
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = (string) $file->getMimeType();
            $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true) && str_starts_with($mime, 'image/') && @getimagesize($file->getRealPath()) !== false;
            $isSvg = $ext === 'svg' && ! preg_match('/<script|on\w+\s*=|javascript:|<foreignObject|<iframe|<!ENTITY/i', (string) file_get_contents($file->getRealPath()));
            if (! $isImage && ! $isSvg) {
                return back()->withInput()->withErrors(['logo' => 'Sigla trebuie să fie o imagine PNG, JPG, WebP sau un SVG fără scripturi.']);
            }
            if ($entity->logo_path) {
                Storage::disk('local')->delete($entity->logo_path);
            }
            $data['logo_path'] = $file->storeAs('logos', $entity->code.'-'.substr(hash_file('sha256', $file->getRealPath()), 0, 12).'.'.$ext, 'local');
        }
        $legalBefore = $entity->only(['legal_name', 'tax_id', 'reg_no', 'address', 'city']);
        $entity->fill($data);
        // Any change to the legal identity withdraws the confirmation until an administrator confirms it again.
        if ($entity->only(['legal_name', 'tax_id', 'reg_no', 'address', 'city']) != $legalBefore) {
            $entity->legal_confirmed_at = null;
            $entity->legal_confirmed_by = null;
        }
        $entity->save();
        AuditLog::record('entitate.modificata', $entity, ['campuri' => array_keys($entity->getChanges())]);

        return back()->with('status', 'Datele companiei au fost salvate.');
    }

    public function confirm(Request $request, Entity $entity)
    {
        if (! filled($entity->legal_name) || ! filled($entity->tax_id) || ! filled($entity->address)) {
            return back()->with('error', 'Completați denumirea juridică, codul fiscal și adresa înainte de confirmare.');
        }
        $request->validate(['confirm' => 'accepted'], ['confirm.accepted' => 'Bifați declarația de verificare.']);
        $entity->update(['legal_confirmed_at' => now(), 'legal_confirmed_by' => $request->user()->id]);
        AuditLog::record('entitate.date_confirmate', $entity, $entity->only(['legal_name', 'tax_id', 'reg_no']));

        return back()->with('status', 'Datele juridice au fost confirmate. Compania poate emite oferte.');
    }
}
