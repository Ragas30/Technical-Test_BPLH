<?php

namespace App\Http\Requests\Auth;

use App\DTO\Auth\LoginDTO;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function toDTO(): LoginDTO
    {
        return new LoginDTO(
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
            remember: $this->boolean('remember'),
        );
    }
}
