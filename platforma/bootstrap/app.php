<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // cPanel hosts are often behind a reverse proxy / CDN that terminates TLS.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
            'admin.can' => \App\Http\Middleware\AdminCan::class,
            'admin.recent' => \App\Http\Middleware\RequireRecentConfirmation::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('asistent/*') || $request->expectsJson(),
        );
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'code', 'api_key', 'smtp_password']);
    })->create();
