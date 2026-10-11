<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginRouteTest extends TestCase
{
    public function test_login_page_is_available_at_the_standard_login_url(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('NIM / USERNAME')
            ->assertSee('Masuk ke Sistem')
            ->assertSee('Masuk dengan Google')
            ->assertSee('Daftar Akun');
    }

    public function test_login_page_displays_google_authentication_errors(): void
    {
        $this->withSession(['error' => 'Login Google gagal.'])
            ->get('/login')
            ->assertOk()
            ->assertSee('Login Google gagal.');
    }
}
