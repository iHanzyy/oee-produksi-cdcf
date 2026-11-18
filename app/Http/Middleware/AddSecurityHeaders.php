<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');

        if (app()->isProduction()) {
            // shortcut: Alpine standar memerlukan unsafe-eval; gunakan Alpine CSP sebelum menghapusnya.
            $policy = [
                "default-src 'self'",
                "base-uri 'self'",
                "connect-src 'self'",
                "font-src 'self' data: https://fonts.bunny.net",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "img-src 'self' data:",
                "object-src 'none'",
                "script-src 'self' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            ];

            if ($request->isSecure()) {
                $policy[] = 'upgrade-insecure-requests';
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
            }

            $response->headers->set('Content-Security-Policy', implode('; ', $policy));
        }

        return $response;
    }
}
