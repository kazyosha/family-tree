<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $authenticated = Auth::attempt([
            'email' => $this->string('email')->toString(),
            'password' => $this->string('password')->toString(),
            'is_active' => true,
        ], remember: false);

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập không hợp lệ.',
            ]);
        }
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->string('email')->trim()->lower()->toString(),
        ]);
    }
}
