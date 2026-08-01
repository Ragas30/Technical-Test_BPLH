<?php

namespace App\Http\Requests\User;

use App\DTO\User\UserQueryDTO;
use Illuminate\Foundation\Http\FormRequest;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'nullable', 'string', 'exists:roles,name'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'sort_by' => ['sometimes', 'in:name,email,is_active,created_at,updated_at'],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'with_trashed' => ['sometimes', 'boolean'],
        ];
    }

    public function toDTO(): UserQueryDTO
    {
        return new UserQueryDTO(
            search: $this->string('search')->toString() !== '' ? $this->string('search')->toString() : null,
            role: $this->string('role')->toString() !== '' ? $this->string('role')->toString() : null,
            isActive: $this->has('is_active') ? $this->boolean('is_active') : null,
            sortBy: $this->string('sort_by', 'created_at')->toString(),
            sortDir: $this->string('sort_dir', 'desc')->toString(),
            perPage: (int) $this->input('per_page', 15),
            withTrashed: $this->boolean('with_trashed', false),
        );
    }
}
