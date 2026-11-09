<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureDeviceToken;
use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AddSecurityHeaders::class);

        $middleware->alias([
            'device' => EnsureDeviceToken::class,
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson() || $e instanceof ValidationException) {
                return null;
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Sesi login sudah berakhir. Silakan masuk kembali.',
                ], 401);
            }

            if ($e instanceof TokenMismatchException || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 419)) {
                return response()->json([
                    'message' => 'Halaman sudah terlalu lama dibuka. Muat ulang halaman lalu coba lagi.',
                ], 419);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => 'Data atau sesi produksi tidak ditemukan.',
                ], 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                if ($e->getStatusCode() >= 500) {
                    return response()->json([
                        'message' => 'Terjadi kendala pada sistem. Coba beberapa saat lagi.',
                    ], 500);
                }

                return $e->getMessage() !== '' ? null : response()->json([
                    'message' => 'Permintaan belum bisa diproses saat ini.',
                ], $e->getStatusCode());
            }

            return response()->json([
                'message' => 'Terjadi kendala pada sistem. Coba beberapa saat lagi.',
            ], 500);
        });
    })->create();
