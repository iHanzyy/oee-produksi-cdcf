<?php

namespace Tests\Feature;

use App\Http\Middleware\AddSecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_login_with_security_headers(): void
    {
        $this->get('/')
            ->assertRedirect('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
    }

    public function test_production_https_responses_include_csp_and_hsts(): void
    {
        $this->app['env'] = 'production';

        $response = (new AddSecurityHeaders)->handle(
            Request::create('https://oee.example.test'),
            fn () => new Response,
        );

        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertSame('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
    }

    public function test_json_errors_do_not_expose_internal_details(): void
    {
        $this->getJson('/route-that-does-not-exist')
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Data atau sesi produksi tidak ditemukan.',
            ]);
    }

    public function test_json_authentication_error_is_easy_to_understand(): void
    {
        $this->getJson('/production-sessions/status')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Sesi masuk sudah berakhir. Silakan masuk kembali.',
            ]);
    }
}
