<?php

namespace App\Http\Requests\User;

use App\DTO\User\AssignRolesDTO;
use Illuminate\Foundation\Http\FormRequest;

class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],
        ];
    }

    public function toDTO(): AssignRolesDTO
    {
        return new AssignRolesDTO(
            roles: $this->input('roles', []),
        );
    }
}
