<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sensitive operations (users, backups, restore, exports, secrets) require the password
 * and a fresh TOTP code entered in the last 10 minutes.
 */
class RequireRecentConfirmation
{
    public function handle(Request $request, Closure $next): Response
    {
        $at = (int) $request->session()->get('admin.confirmed_at', 0);
        if (time() - $at > 600) {
            if (! $request->isMethod('GET')) {
                $request->session()->put('url.intended', url()->previous());
            } else {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('admin.confirm');
        }

        return $next($request);
    }
}
