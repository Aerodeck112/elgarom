<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TwoFactor;
use App\Support\PasswordRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index()
    {
        return view('admin.users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User(['role' => 'editor', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);
        $temp = Str::password(20, true, true, false);
        $user = new User(['name' => $d['name'], 'email' => Str::lower($d['email']), 'role' => $d['role'], 'is_active' => true]);
        $user->password = Hash::make($temp);
        $user->save();
        AuditLog::record('utilizator.creat', $user, ['rol' => $d['role']]);

        return redirect()->route('admin.users.edit', $user)->with('temp_password', $temp)->with('status', 'Contul a fost creat. Transmiteți parola temporară pe un canal sigur; la prima autentificare utilizatorul configurează 2FA.');
    }

    public function edit(User $user)
    {
        $sessions = DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->get();

        return view('admin.users.form', ['user' => $user, 'sessions' => $sessions]);
    }

    public function update(Request $request, User $user)
    {
        $d = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => 'nullable|boolean',
        ]);
        $d['is_active'] = $request->boolean('is_active');
        $d['email'] = Str::lower($d['email']);
        if ($user->id === $request->user()->id && ($d['role'] !== 'admin' || ! $d['is_active'])) {
            return back()->withErrors(['role' => 'Nu vă puteți retrage singur drepturile de administrator sau dezactiva propriul cont.']);
        }
        if ($user->role === 'admin' && ($d['role'] !== 'admin' || ! $d['is_active']) && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['role' => 'Trebuie să rămână cel puțin un administrator activ.']);
        }
        $user->fill($d);
        $changed = array_keys($user->getDirty());
        $user->save();
        if (! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        AuditLog::record('utilizator.modificat', $user, ['campuri' => $changed]);

        return back()->with('status', 'Utilizatorul a fost actualizat.');
    }

    public function resetPassword(User $user)
    {
        $temp = Str::password(20, true, true, false);
        $user->forceFill(['password' => Hash::make($temp), 'password_changed_at' => null])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        AuditLog::record('utilizator.parola_resetata', $user);

        return back()->with('temp_password', $temp)->with('status', 'Parola a fost resetată și sesiunile utilizatorului au fost închise.');
    }

    public function reset2fa(Request $request, User $user)
    {
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null, 'last_totp_step' => null])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        AuditLog::record('utilizator.2fa_resetat', $user);

        return back()->with('status', '2FA a fost resetat. La următoarea autentificare utilizatorul trebuie să îl configureze din nou.');
    }

    public function revokeSessions(Request $request, User $user)
    {
        $q = DB::table('sessions')->where('user_id', $user->id);
        if ($user->id === $request->user()->id) {
            $q->where('id', '!=', $request->session()->getId());
        }
        $n = $q->delete();
        AuditLog::record('utilizator.sesiuni_revocate', $user, ['numar' => $n]);

        return back()->with('status', $n.' sesiuni au fost închise.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['role' => 'Nu vă puteți șterge propriul cont.']);
        }
        if ($user->role === 'admin' && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['role' => 'Trebuie să rămână cel puțin un administrator activ.']);
        }
        DB::table('sessions')->where('user_id', $user->id)->delete();
        \App\Models\QuoteRequest::where('assignee_id', $user->id)->update(['assignee_id' => null]);
        AuditLog::record('utilizator.sters', $user, ['email' => $user->email]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Utilizatorul a fost șters.');
    }

    /* ---------- own account ---------- */

    public function account(Request $request)
    {
        return view('admin.users.account', [
            'user' => $request->user(),
            'sessions' => DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get(),
            'codesLeft' => app(TwoFactor::class)->remainingRecoveryCodes($request->user()),
        ]);
    }

    public function accountRevoke(Request $request)
    {
        return $this->revokeSessions($request, $request->user());
    }

    public function password(Request $request)
    {
        $user = $request->user();
        $d = $request->validate([
            'current_password' => 'required|string',
            'password' => PasswordRules::rules($user->email),
        ], PasswordRules::messages());
        if (! Hash::check($d['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Parola actuală este incorectă.']);
        }
        $user->forceFill(['password' => Hash::make($d['password']), 'password_changed_at' => now()])->save();
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        $request->session()->regenerate();
        AuditLog::record('cont.parola_schimbata', $user);

        return back()->with('status', 'Parola a fost schimbată. Celelalte sesiuni au fost închise.');
    }

    public function recoveryCodes(Request $request)
    {
        $codes = app(TwoFactor::class)->generateRecoveryCodes($request->user());
        AuditLog::record('cont.coduri_regenerate', $request->user());

        return redirect()->route('admin.2fa.codes')->with('recovery_codes', $codes);
    }

    public function reconfigure2fa(Request $request)
    {
        $request->session()->forget('admin.2fa_pending');

        return redirect()->route('admin.2fa.setup');
    }
}
