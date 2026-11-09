<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_login_with_security_headers(): void
    {
        $this->get('/')
            ->assertRedirect('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'same-origin');
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
                'message' => 'Sesi login sudah berakhir. Silakan masuk kembali.',
            ]);
    }
}
