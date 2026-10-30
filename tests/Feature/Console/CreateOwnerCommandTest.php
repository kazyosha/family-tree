<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateOwnerCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_create_owner_command_creates_an_active_owner_without_echoing_password(): void
    {
        $password = 'Mat-khau-rieng-2026!';

        $this->artisan('app:create-owner')
            ->expectsQuestion('Họ và tên', 'Chủ gia đình')
            ->expectsQuestion('Email', '  OWNER@EXAMPLE.COM ')
            ->expectsQuestion('Mật khẩu', $password)
            ->expectsQuestion('Nhập lại mật khẩu', $password)
            ->doesntExpectOutputToContain($password)
            ->assertSuccessful();

        $owner = User::query()->sole();

        $this->assertSame('Chủ gia đình', $owner->name);
        $this->assertSame('owner@example.com', $owner->email);
        $this->assertSame(UserRole::Owner, $owner->role);
        $this->assertTrue($owner->is_active);
        $this->assertTrue(Hash::check($password, $owner->password));
    }

    public function test_create_owner_command_updates_an_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'old-password',
        ]);
        $password = 'Mat-khau-moi-2026!';

        $this->artisan('app:create-owner')
            ->expectsQuestion('Họ và tên', 'Tên chủ mới')
            ->expectsQuestion('Email', 'OWNER@example.com')
            ->expectsQuestion('Mật khẩu', $password)
            ->expectsQuestion('Nhập lại mật khẩu', $password)
            ->assertSuccessful();

        $this->assertSame(1, User::query()->count());
        $user->refresh();
        $this->assertSame('Tên chủ mới', $user->name);
        $this->assertSame(UserRole::Owner, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_user_factory_defaults_to_an_inactive_viewer(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Viewer, $user->role);
        $this->assertFalse($user->is_active);
    }
}
