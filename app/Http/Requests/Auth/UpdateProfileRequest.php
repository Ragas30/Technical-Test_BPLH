<?php

namespace App\Http\Requests\Auth;

use App\DTO\Auth\UpdateProfileDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
             * Email pengguna.
             *
             * @example budi@docflow.test
             */
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()?->id)->withoutTrashed()],
        ];
    }

    public function toDTO(): UpdateProfileDTO
    {
        return new UpdateProfileDTO(
            name: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
        );
    }
}
