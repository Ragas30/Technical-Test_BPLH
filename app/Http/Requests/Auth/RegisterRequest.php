<?php

namespace App\Http\Requests\Auth;

use App\DTO\Auth\RegisterUserDTO;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Nama lengkap pengguna.
             *
             * @example Budi Santoso
             */
            'name' => ['required', 'string', 'max:255'],

            /**
             * Email pengguna yang belum terdaftar.
             *
             * @example budi@docflow.test
             */
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],

            /**
             * Kata sandi minimal 8 karakter.
             *
             * @example password
             */
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }

    public function toDTO(): RegisterUserDTO
    {
        return new RegisterUserDTO(
            name: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
        );
    }
}
