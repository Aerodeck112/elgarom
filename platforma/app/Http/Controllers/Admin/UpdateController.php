<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemUpdate;
use App\Services\TwoFactor;
use App\Services\Updates\Installer;
use App\Services\Updates\PackageVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class UpdateController extends Controller
{
    public function __construct(private PackageVerifier $verifier) {}

    private function incomingDir(): string
    {
        $d = Installer::workDir().'/incoming';
        if (! is_dir($d)) {
            mkdir($d, 0700, true);
        }

        return $d;
    }

    public function index()
    {
        $incoming = array_map('basename', glob($this->incomingDir().'/*.zip') ?: []);

        return view('admin.system.updates', [
            'history' => SystemUpdate::with('user')->latest()->limit(30)->get(),
            'keys' => collect(config('elgarom.updates.public_keys'))->map(fn ($k) => substr(hash('sha256', base64_decode($k)), 0, 16)),
            'incoming' => $incoming,
            'maxUpload' => ini_get('upload_max_filesize'),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'package' => 'nullable|file|max:'.(int) (config('elgarom.updates.max_package_bytes') / 1024),
            'incoming' => 'nullable|string|max:200',
        ]);
        $target = Installer::workDir().'/pending-'.Str::random(16).'.zip';
        if ($request->hasFile('package')) {
            if (strtolower($request->file('package')->getClientOriginalExtension()) !== 'zip') {
                return back()->with('error', 'Încărcați un fișier .zip.');
            }
            $request->file('package')->move(dirname($target), basename($target));
        } elseif ($name = $request->input('incoming')) {
            $src = $this->incomingDir().'/'.basename($name);
            if (! is_file($src) || ! str_ends_with($src, '.zip')) {
                return back()->with('error', 'Pachetul ales nu există.');
            }
            rename($src, $target);
        } else {
            return back()->with('error', 'Alegeți un pachet.');
        }
        try {
            $m = $this->verifier->verify($target, config('elgarom.version'));
        } catch (\Throwable $e) {
            @unlink($target);
            AuditLog::record('actualizare.respinsa', null, ['motiv' => $e->getMessage()]);

            return back()->with('error', 'Pachet respins: '.$e->getMessage());
        }
        $request->session()->put('update.pending', ['path' => $target, 'sha' => $m['_sha256'], 'version' => $m['version']]);
        AuditLog::record('actualizare.verificata', null, ['versiune' => $m['version'], 'sha256' => $m['_sha256']]);

        return redirect()->route('admin.updates.review');
    }

    public function review(Request $request)
    {
        $p = $request->session()->get('update.pending');
        if (! $p || ! is_file($p['path'])) {
            return redirect()->route('admin.updates.index');
        }
        try {
            $m = $this->verifier->verify($p['path'], config('elgarom.version'));
        } catch (\Throwable $e) {
            $this->cancel($request);

            return redirect()->route('admin.updates.index')->with('error', 'Pachetul nu mai este valid: '.$e->getMessage());
        }
        $new = $changed = 0;
        foreach ($m['files'] as $rel => $hash) {
            $f = base_path($rel);
            if (! is_file($f)) {
                $new++;
            } elseif (hash_file('sha256', $f) !== $hash) {
                $changed++;
            }
        }

        return view('admin.system.update-review', ['m' => $m, 'new' => $new, 'changed' => $changed, 'free' => @disk_free_space(storage_path())]);
    }

    public function install(Request $request, Installer $installer, TwoFactor $tf)
    {
        $user = $request->user();
        $p = $request->session()->get('update.pending');
        if (! $p || ! is_file($p['path'])) {
            return redirect()->route('admin.updates.index')->with('error', 'Nu există un pachet verificat.');
        }
        $key = 'update-confirm:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('error', 'Prea multe încercări. Așteptați câteva minute.');
        }
        // Re-authentication: password + a TOTP code newer than any used before.
        if (! Hash::check((string) $request->input('password'), $user->password) || ! $tf->verifyUser($user, (string) $request->input('code'))) {
            RateLimiter::hit($key, 600);
            AuditLog::record('actualizare.confirmare_esuata', $user);

            return back()->with('error', 'Parola sau codul 2FA sunt incorecte. Folosiți un cod nou, apărut după ultima autentificare.');
        }
        if (! hash_equals($p['sha'], hash_file('sha256', $p['path']))) {
            return back()->with('error', 'Pachetul s-a modificat după verificare. Încărcați-l din nou.');
        }
        AuditLog::record('actualizare.pornita', null, ['versiune' => $p['version']]);
        try {
            $rec = $installer->install($p['path'], $user->id);
        } catch (\Throwable $e) {
            report($e);
            AuditLog::record('actualizare.esuata', null, ['versiune' => $p['version'], 'eroare' => $e->getMessage()]);
            $request->session()->forget('update.pending');
            @unlink($p['path']);

            return redirect()->route('admin.updates.index')->with('error', $e->getMessage());
        }
        @unlink($p['path']);
        $request->session()->forget('update.pending');
        AuditLog::record('actualizare.instalata', $rec, ['din' => $rec->version_from, 'in' => $rec->version_to]);

        return redirect()->route('admin.updates.index')->with('status', 'Actualizarea la versiunea '.$rec->version_to.' a fost instalată.');
    }

    public function cancel(Request $request)
    {
        if ($p = $request->session()->pull('update.pending')) {
            @unlink($p['path']);
        }

        return redirect()->route('admin.updates.index')->with('status', 'Pachetul a fost eliminat.');
    }
}
