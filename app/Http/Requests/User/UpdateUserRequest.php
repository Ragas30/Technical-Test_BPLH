<?php

namespace App\Http\Requests\User;

use App\DTO\User\UpdateUserDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
             * Email pengguna.
             *
             * @example siti@docflow.test
             */
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user')?->id)->withoutTrashed()],

            /**
             * Kata sandi baru (opsional, kosongkan untuk tidak mengubah).
             *
             * @example passwordBaru123
             */
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],

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
            'roles' => ['sometimes', 'array'],
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

    public function toDTO(): UpdateUserDTO
    {
        $password = $this->input('password');

        return new UpdateUserDTO(
            name: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
            password: is_string($password) && $password !== '' ? $password : null,
            isActive: $this->boolean('is_active', true),
            roles: $this->has('roles') ? $this->input('roles', []) : null,
            permissions: $this->has('permissions') ? $this->input('permissions', []) : null,
        );
    }
}
