<?php

namespace App\Http\Requests\Auth;

use App\DTO\Auth\ChangePasswordDTO;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Kata sandi aktif saat ini.
             *
             * @example password
             */
            'current_password' => ['required', 'string', 'current_password'],

            /**
             * Kata sandi baru minimal 8 karakter.
             *
             * @example passwordBaru123
             */
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }

    public function toDTO(): ChangePasswordDTO
    {
        return new ChangePasswordDTO(
            newPassword: $this->string('password')->toString(),
        );
    }
}
