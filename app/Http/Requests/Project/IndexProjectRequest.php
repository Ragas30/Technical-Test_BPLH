<?php

namespace App\Http\Requests\Project;

use App\DTO\Project\ProjectQueryDTO;
use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;

class IndexProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'in:'.implode(',', ProjectStatus::values())],
            'sort_by' => ['sometimes', 'in:title,project_number,status,created_at,updated_at'],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDTO(): ProjectQueryDTO
    {
        return new ProjectQueryDTO(
            search: $this->string('search')->toString() !== '' ? $this->string('search')->toString() : null,
            status: $this->string('status')->toString() !== '' ? $this->string('status')->toString() : null,
            sortBy: $this->string('sort_by', 'created_at')->toString(),
            sortDir: $this->string('sort_dir', 'desc')->toString(),
            perPage: (int) $this->input('per_page', 15),
        );
    }
}
