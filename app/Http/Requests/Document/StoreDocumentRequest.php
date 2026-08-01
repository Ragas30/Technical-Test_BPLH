<?php

namespace App\Http\Requests\Document;

use App\DTO\Document\UploadDocumentDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:'.config('documents.max_files_per_upload')],
            'documents.*' => [
                'required',
                'file',
                'extensions:'.implode(',', config('documents.allowed_extensions')),
                'max:'.config('documents.max_size_kb'),
            ],
        ];
    }

    /**
     * @return array<int, UploadDocumentDTO>
     */
    public function toDTO(): array
    {
        return array_map(
            static fn (UploadedFile $file): UploadDocumentDTO => new UploadDocumentDTO($file),
            (array) $this->file('documents'),
        );
    }
}
