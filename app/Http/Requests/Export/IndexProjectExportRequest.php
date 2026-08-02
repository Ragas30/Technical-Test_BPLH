<?php

namespace App\Http\Requests\Export;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;

class IndexProjectExportRequest extends FormRequest
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
        ];
    }

    public function search(): ?string
    {
        $value = $this->string('search')->toString();

        return $value !== '' ? $value : null;
    }

    public function status(): ?string
    {
        $value = $this->string('status')->toString();

        return $value !== '' ? $value : null;
    }
}
