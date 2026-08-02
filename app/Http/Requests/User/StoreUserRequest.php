<?php

namespace App\Http\Requests\User;

use App\DTO\User\CreateUserDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
             * @example Siti Rahma
             */
            'name' => ['required', 'string', 'max:255'],

            /**
             * Email pengguna yang belum terdaftar.
             *
             * @example siti@docflow.test
             */
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],

            /**
             * Kata sandi awal minimal 8 karakter.
             *
             * @example password
             */
            'password' => ['required', 'string', 'min:8'],

            /**
             * Status aktif pengguna.
             *
             * @example true
             */
            'is_active' => ['sometimes', 'boolean'],

            /**
             * Daftar role yang dimiliki pengguna.
             *
             * @example ["reviewer"]
             */
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],

            /**
             * Daftar permission tambahan (opsional).
             *
             * @example ["document.download"]
             */
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ];
    }

    public function toDTO(): CreateUserDTO
    {
        return new CreateUserDTO(
            name: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
            isActive: $this->boolean('is_active', true),
            roles: $this->input('roles', []),
            permissions: $this->input('permissions', []),
        );
    }
}
