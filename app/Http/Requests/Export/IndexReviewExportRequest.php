<?php

namespace App\Http\Requests\Export;

use App\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;

class IndexReviewExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'in:'.implode(',', ReviewStatus::values())],
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
