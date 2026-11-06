<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Server-side permission check: admin.can:permission */
class AdminCan
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($request->user()?->can_($permission), 403);

        return $next($request);
    }
}
