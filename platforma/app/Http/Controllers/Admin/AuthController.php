<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private TwoFactor $tf) {}

    private function throttleKey(Request $r, string $scope): string
    {
        return 'admin-'.$scope.':'.Str::lower((string) $r->input('email', $r->user()?->email)).'|'.$r->ip();
    }

    private function tooMany(string $key, int $max): ?string
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return 'Prea multe încercări. Încercați din nou peste '.ceil(RateLimiter::availableIn($key) / 60).' minute.';
        }

        return null;
    }

    public function showLogin(Request $request)
    {
        if (Auth::check() && $request->session()->get('admin.2fa_passed')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string', 'max:200']], [
            'email.required' => 'Completați emailul.', 'password.required' => 'Completați parola.',
        ]);
        $key = $this->throttleKey($request, 'login');
        $ipKey = 'admin-login-ip:'.$request->ip();
        if ($msg = $this->tooMany($key, 5) ?? $this->tooMany($ipKey, 25)) {
            AuditLog::record('autentificare.blocata', null, ['email' => $data['email']]);

            return back()->withErrors(['email' => $msg])->onlyInput('email');
        }
        $user = User::where('email', Str::lower($data['email']))->first();
        // Constant-time-ish: always run a hash check.
        $ok = $user ? Hash::check($data['password'], $user->password) : Hash::check($data['password'], '$2y$12$'.str_repeat('a', 53));
        if (! $ok || ! $user->is_active) {
            RateLimiter::hit($key, 900);
            RateLimiter::hit($ipKey, 3600);
            AuditLog::record('autentificare.esuata', $user, ['email' => $data['email']], $user?->id);

            return back()->withErrors(['email' => 'Email sau parolă incorecte.'])->onlyInput('email');
        }
        RateLimiter::clear($key);
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => Hash::make($data['password'])])->save();
        }
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('admin.last_seen', time());
        AuditLog::record('autentificare.parola_ok', $user);

        return $user->hasTwoFactor() ? redirect()->route('admin.2fa.challenge') : redirect()->route('admin.2fa.setup');
    }

    public function showChallenge(Request $request)
    {
        abort_unless($request->user()?->hasTwoFactor(), 403);

        return view('admin.auth.challenge');
    }

    public function challenge(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->hasTwoFactor(), 403);
        $key = $this->throttleKey($request, '2fa');
        if ($msg = $this->tooMany($key, 5)) {
            return back()->withErrors(['code' => $msg]);
        }
        $code = (string) $request->input('code', '');
        $recovery = (string) $request->input('recovery_code', '');
        $ok = $recovery !== '' ? $this->tf->useRecoveryCode($user, $recovery) : $this->tf->verifyUser($user, $code);
        if (! $ok) {
            RateLimiter::hit($key, 900);
            AuditLog::record('2fa.esuat', $user);

            return back()->withErrors(['code' => 'Cod incorect sau deja folosit. Așteptați următorul cod din aplicație.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('admin.2fa_passed', true);
        $request->session()->put('admin.confirmed_at', time());
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        AuditLog::record($recovery !== '' ? 'autentificare.cod_recuperare' : 'autentificare.reusita', $user, ['coduri_ramase' => $this->tf->remainingRecoveryCodes($user)]);
        if ($recovery !== '' && $this->tf->remainingRecoveryCodes($user) <= 2) {
            session()->flash('warning', 'Mai aveți '.$this->tf->remainingRecoveryCodes($user).' coduri de recuperare. Generați coduri noi din Contul meu.');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showSetup(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);
        if ($user->hasTwoFactor() && ! $request->session()->get('admin.2fa_passed')) {
            return redirect()->route('admin.2fa.challenge');
        }
        $secret = $request->session()->get('admin.2fa_pending') ?? $this->tf->newSecret();
        $request->session()->put('admin.2fa_pending', $secret);

        return view('admin.auth.setup', [
            'secret' => $secret,
            'qr' => $this->tf->qrSvg($user, $secret),
            'reset' => $user->hasTwoFactor(),
        ]);
    }

    public function setup(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);
        if ($user->hasTwoFactor() && ! $request->session()->get('admin.2fa_passed')) {
            abort(403);
        }
        $secret = (string) $request->session()->get('admin.2fa_pending');
        $key = $this->throttleKey($request, '2fa-setup');
        if ($msg = $this->tooMany($key, 8)) {
            return back()->withErrors(['code' => $msg]);
        }
        if (! $secret || ! $this->tf->verifySecret($secret, (string) $request->input('code'))) {
            RateLimiter::hit($key, 900);

            return back()->withErrors(['code' => 'Codul nu corespunde. Verificați ora telefonului și introduceți codul afișat acum.']);
        }
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'last_totp_step' => null])->save();
        $codes = $this->tf->generateRecoveryCodes($user);
        $request->session()->forget('admin.2fa_pending');
        $request->session()->regenerate();
        $request->session()->put('admin.2fa_passed', true);
        $request->session()->put('admin.confirmed_at', time());
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        AuditLog::record('2fa.activat', $user);

        return redirect()->route('admin.2fa.codes')->with('recovery_codes', $codes);
    }

    public function codes(Request $request)
    {
        $codes = $request->session()->get('recovery_codes');
        if (! $codes) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.codes', ['codes' => $codes]);
    }

    public function showConfirm()
    {
        return view('admin.auth.confirm');
    }

    /** Re-authentication: password + a TOTP code newer than any previously used one. */
    public function confirm(Request $request)
    {
        $user = $request->user();
        $key = $this->throttleKey($request, 'confirm');
        if ($msg = $this->tooMany($key, 5)) {
            return back()->withErrors(['password' => $msg]);
        }
        if (! Hash::check((string) $request->input('password'), $user->password) || ! $this->tf->verifyUser($user, (string) $request->input('code'))) {
            RateLimiter::hit($key, 900);
            AuditLog::record('confirmare.esuata', $user);

            return back()->withErrors(['password' => 'Parola sau codul 2FA sunt incorecte (codul trebuie să fie unul nou, nefolosit).']);
        }
        RateLimiter::clear($key);
        $request->session()->put('admin.confirmed_at', time());
        AuditLog::record('confirmare.reusita', $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        AuditLog::record('iesire', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'V-ați deconectat.');
    }
}
