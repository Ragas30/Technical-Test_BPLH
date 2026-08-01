<?php

namespace App\Http\Requests\ActivityLog;

use App\DTO\ActivityLog\ActivityLogQueryDTO;
use App\Enums\ActivityAction;
use Illuminate\Foundation\Http\FormRequest;

class IndexActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'action' => ['sometimes', 'nullable', 'in:'.implode(',', ActivityAction::values())],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDTO(): ActivityLogQueryDTO
    {
        return new ActivityLogQueryDTO(
            search: $this->string('search')->toString() !== '' ? $this->string('search')->toString() : null,
            action: $this->string('action')->toString() !== '' ? $this->string('action')->toString() : null,
            perPage: (int) $this->input('per_page', 15),
        );
    }
}
