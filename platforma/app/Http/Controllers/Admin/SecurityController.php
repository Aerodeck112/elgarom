<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $sessions = DB::table('sessions')->whereNotNull('user_id')->orderByDesc('last_activity')->limit(100)->get();
        $users = User::whereIn('id', $sessions->pluck('user_id'))->get()->keyBy('id');

        return view('admin.system.security', [
            'checks' => $this->checks($request),
            'sessions' => $sessions,
            'users' => $users,
            'failures' => AuditLog::whereIn('action', ['autentificare.esuata', 'autentificare.blocata', '2fa.esuat', 'confirmare.esuata'])->latest('id')->limit(40)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $d = $request->validate(['idle_minutes' => 'required|integer|min:5|max:240']);
        Setting::put('security.idle_minutes', (int) $d['idle_minutes']);
        AuditLog::record('securitate.setari', null, $d);

        return back()->with('status', 'Setările de securitate au fost salvate.');
    }

    public function revoke(Request $request, string $id)
    {
        abort_if($id === $request->session()->getId(), 422, 'Pentru sesiunea curentă folosiți Ieșire.');
        DB::table('sessions')->where('id', $id)->delete();
        AuditLog::record('securitate.sesiune_revocata', null, ['sesiune' => substr(hash('sha256', $id), 0, 12)]);

        return back()->with('status', 'Sesiunea a fost închisă.');
    }

    private function checks(Request $request): array
    {
        $c = [];
        $c[] = ['HTTPS', $request->isSecure(), $request->isSecure() ? 'Conexiune securizată.' : 'Site-ul nu rulează pe HTTPS. Activați certificatul SSL din cPanel (AutoSSL / Let\'s Encrypt).'];
        $c[] = ['Mod depanare dezactivat', ! config('app.debug'), config('app.debug') ? 'APP_DEBUG=true expune detalii tehnice. Setați APP_DEBUG=false în .env.' : 'OK'];
        $c[] = ['Mediu producție', app()->environment('production'), 'APP_ENV='.app()->environment()];
        $c[] = ['Cookie de sesiune securizat', (bool) config('session.secure') || ! $request->isSecure(), config('session.secure') ? 'Secure + HttpOnly' : 'Setați SESSION_SECURE_COOKIE=true'];
        $c[] = ['Instalator blocat', is_file(base_path('install.lock')) || ! is_file(base_path('install.php')), is_file(base_path('install.php')) ? 'install.php există – este blocat de install.lock; puteți șterge fișierul.' : 'install.php a fost eliminat.'];
        $c[] = ['Administratori cu 2FA', User::where('is_active', true)->get()->every->hasTwoFactor(), 'Toate conturile active trebuie să aibă 2FA; cele fără 2FA nu pot accesa panoul.'];
        $c[] = ['PHP suportat', version_compare(PHP_VERSION, '8.3.0', '>='), 'PHP '.PHP_VERSION];
        foreach (['/.env' => 'Fișierul .env', '/storage/logs/' => 'Jurnalele', '/vendor/composer/installed.json' => 'Dependențele'] as $path => $label) {
            $c[] = [$label.' inaccesibil din web', $this->notPublic($path), 'Verificare HTTP pentru '.$path];
        }

        return $c;
    }

    private function notPublic(string $path): bool
    {
        try {
            $r = Http::timeout(4)->withoutRedirecting()->get(rtrim(config('app.url'), '/').$path);

            return ! $r->successful();
        } catch (\Throwable) {
            return true; // unreachable from the server itself: treat as not exposed
        }
    }
}
