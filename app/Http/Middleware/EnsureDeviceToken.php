<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceToken
{
    public const ATTRIBUTE = 'device_token';

    public const COOKIE = 'device_token';

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie(self::COOKIE);
        $shouldIssueCookie = ! is_string($token) || strlen($token) !== 64;

        if ($shouldIssueCookie) {
            $token = Str::random(64);
        }

        $request->attributes->set(self::ATTRIBUTE, $token);
        $response = $next($request);

        if ($shouldIssueCookie) {
            $response->headers->setCookie(Cookie::make(
                self::COOKIE,
                $token,
                60 * 24 * 365,
                '/',
                null,
                config('session.secure'),
                true,
                false,
                'lax',
            ));
        }

        return $response;
    }
}
