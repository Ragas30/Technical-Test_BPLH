<?php

namespace App\Http\Requests\User;

use App\DTO\User\AssignPermissionsDTO;
use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Daftar permission yang ditetapkan ke pengguna.
             *
             * @example ["document.download", "review.view_any"]
             */
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ];
    }

    public function toDTO(): AssignPermissionsDTO
    {
        return new AssignPermissionsDTO(
            permissions: $this->input('permissions', []),
        );
    }
}
