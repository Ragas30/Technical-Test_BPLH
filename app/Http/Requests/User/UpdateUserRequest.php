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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user')?->id)->withoutTrashed()],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['exists:roles,name'],
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
