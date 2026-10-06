<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($request->user()?->role, $roles, true)) {
            if ($request->isMethod('GET') && ! $request->expectsJson()) {
                $route = match ($request->user()?->role) {
                    'operator' => 'dashboard',
                    'spv', 'manager' => 'history.index',
                    default => null,
                };

                if ($route !== null) {
                    return redirect()->route($route);
                }
            }

            abort(403, 'Akun ini tidak memiliki akses ke tindakan tersebut.');
        }

        return $next($request);
    }
}
