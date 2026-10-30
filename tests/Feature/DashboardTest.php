<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_escaped_name_role_and_logout_form(): void
    {
        $user = User::factory()->create([
            'name' => '<script>alert("xss")</script>',
            'role' => UserRole::Owner,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $html = $response->getContent();

        $response
            ->assertOk()
            ->assertSeeText('Chủ sở hữu')
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertDontSeeText('Tải xuống');
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_login_page_contains_private_login_form_without_secrets_or_registration(): void
    {
        config([
            'app.key' => 'base64:QUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUE=',
            'database.connections.pgsql.host' => 'private-neon-host.example',
            'filesystems.disks.r2.endpoint' => 'https://private-r2.example',
        ]);

        $response = $this->get('/login');
        $html = $response->getContent();

        $response
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('name="_token"', false)
            ->assertSeeText('Đăng nhập')
            ->assertDontSeeText('Đăng ký');
        $this->assertStringNotContainsString('QUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUE=', $html);
        $this->assertStringNotContainsString('private-neon-host.example', $html);
        $this->assertStringNotContainsString('private-r2.example', $html);
    }

    public function test_public_home_page_links_to_login(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('href="'.route('login').'"', false)
            ->assertSeeText('Đăng nhập');
    }
}
