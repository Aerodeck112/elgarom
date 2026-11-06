<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers for every response. The CSP uses a per-request nonce with 'strict-dynamic':
 * only scripts rendered by the application (or loaded by them, e.g. Google Tag Manager after
 * consent) can execute. Inline event handlers and injected <script> tags are blocked.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(18));
        app()->instance('csp.nonce', $nonce);
        Vite::useCspNonce($nonce);

        $response = $next($request);

        $google = 'https://*.googletagmanager.com https://*.google-analytics.com https://*.analytics.google.com https://*.g.doubleclick.net https://www.google.com https://www.googleadservices.com https://googleads.g.doubleclick.net';
        $csp = [
            "default-src 'self'",
            "script-src 'nonce-{$nonce}' 'strict-dynamic' 'self' https:",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: {$google} https://*.google.ro",
            "font-src 'self'",
            "connect-src 'self' {$google}",
            "frame-src https://www.google.com https://maps.google.com https://www.googletagmanager.com",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https://accounts.google.com",
            "frame-ancestors 'none'",
        ];
        if ($request->isSecure()) {
            $csp[] = 'upgrade-insecure-requests';
        }

        $h = $response->headers;
        $h->set('Content-Security-Policy', implode('; ', $csp));
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'DENY');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        $h->remove('X-Powered-By');
        if ($request->is('admin', 'admin/*')) {
            $h->set('Cache-Control', 'no-store, private');
            $h->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
