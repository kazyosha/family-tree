<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('app:create-owner')]
#[Description('Tạo hoặc cập nhật tài khoản chủ sở hữu gia đình')]
class CreateOwnerCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = (string) $this->ask('Họ và tên');
        $email = Str::lower(trim((string) $this->ask('Email')));
        $password = (string) $this->secret('Mật khẩu', fallback: false);
        $passwordConfirmation = (string) $this->secret('Nhập lại mật khẩu', fallback: false);

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => UserRole::Owner,
                'is_active' => true,
            ],
        );

        $this->info('Đã lưu tài khoản OWNER.');

        return self::SUCCESS;
    }
}
