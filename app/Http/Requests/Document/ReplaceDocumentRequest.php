<?php

namespace App\Http\Requests\Document;

use App\DTO\Document\ReplaceDocumentDTO;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'extensions:'.implode(',', config('documents.allowed_extensions')),
                'max:'.config('documents.max_size_kb'),
            ],
        ];
    }

    public function toDTO(): ReplaceDocumentDTO
    {
        return new ReplaceDocumentDTO($this->file('file'));
    }
}
