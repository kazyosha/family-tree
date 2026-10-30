<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_sees_login_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSeeText('Đăng nhập');
    }

    public function test_active_user_can_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'viewer@example.com',
            'password' => 'password-2026',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'viewer@example.com',
            'password' => 'password-2026',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_receive_generic_failure(): void
    {
        User::factory()->create([
            'email' => 'viewer@example.com',
            'password' => 'correct-password',
            'is_active' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'viewer@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Thông tin đăng nhập không hợp lệ.']);
        $this->assertGuest();
    }

    public function test_inactive_account_receives_generic_failure(): void
    {
        User::factory()->create([
            'email' => 'viewer@example.com',
            'password' => 'password-2026',
            'is_active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'viewer@example.com',
            'password' => 'password-2026',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Thông tin đăng nhập không hợp lệ.']);
        $this->assertGuest();
    }

    public function test_email_is_normalized_before_authentication(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password-2026',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => '  OWNER@EXAMPLE.COM ',
            'password' => 'password-2026',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_regenerates_the_session(): void
    {
        User::factory()->create([
            'email' => 'viewer@example.com',
            'password' => 'password-2026',
            'is_active' => true,
        ]);
        $this->withSession(['login-probe' => true]);
        $sessionIdBeforeLogin = session()->getId();

        $this->post('/login', [
            'email' => 'viewer@example.com',
            'password' => 'password-2026',
        ]);

        $this->assertNotSame($sessionIdBeforeLogin, session()->getId());
    }

    public function test_authenticated_user_visiting_login_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_logout_invalidates_authentication_and_redirects_home(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_logout_rejects_get_requests(): void
    {
        $this->get('/logout')->assertMethodNotAllowed();
    }

    public function test_public_registration_route_does_not_exist(): void
    {
        $this->assertFalse(Route::has('register'));
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $credentials = [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', $credentials)->assertSessionHasErrors('email');
        }

        $this->post('/login', $credentials)->assertTooManyRequests();
    }
}
