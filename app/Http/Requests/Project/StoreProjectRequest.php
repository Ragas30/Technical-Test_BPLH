<?php

namespace App\Http\Requests\Project;

use App\DTO\Project\CreateProjectDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function toDTO(): CreateProjectDTO
    {
        return new CreateProjectDTO(
            title: $this->string('title')->toString(),
            description: $this->input('description') !== null ? $this->string('description')->toString() : null,
        );
    }
}
