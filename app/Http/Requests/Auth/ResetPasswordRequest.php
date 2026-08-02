<?php

namespace App\Http\Requests\Auth;

use App\DTO\Auth\ResetPasswordDTO;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Token reset kata sandi dari email.
             *
             * @example 3c5f2a8e9b1d4f6a7c8e9f0a1b2c3d4e
             */
            'token' => ['required', 'string'],

            /**
             * Email pengguna.
             *
             * @example admin@docflow.test
             */
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],

            /**
             * Kata sandi baru minimal 8 karakter.
             *
             * @example passwordBaru123
             */
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }

    public function toDTO(): ResetPasswordDTO
    {
        return new ResetPasswordDTO(
            token: $this->string('token')->toString(),
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
        );
    }
}
