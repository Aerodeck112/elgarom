<?php

namespace App\Http\Middleware;

use App\Services\Mail\Outbox;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

/**
 * Every admin request requires: an authenticated, active user who has 2FA configured
 * and has passed the 2FA challenge in this session. There is no way around 2FA.
 */
class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('admin.login')->withErrors(['email' => 'Contul este dezactivat.']);
        }
        if (! $user->hasTwoFactor()) {
            return redirect()->route('admin.2fa.setup');
        }
        if (! $request->session()->get('admin.2fa_passed')) {
            return redirect()->route('admin.2fa.challenge');
        }
        // Idle timeout for the admin area.
        $last = (int) $request->session()->get('admin.last_seen', time());
        $idle = (int) setting('security.idle_minutes', 30) * 60;
        if (time() - $last > $idle) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors(['email' => 'Sesiunea a expirat după inactivitate. Autentificați-vă din nou.']);
        }
        $request->session()->put('admin.last_seen', time());

        // Without a cron job, pending emails are retried opportunistically after admin requests.
        defer(fn () => app(Outbox::class)->processIfStale());

        return $next($request);
    }
}
