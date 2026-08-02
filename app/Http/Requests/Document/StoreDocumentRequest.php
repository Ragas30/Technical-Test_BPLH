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
            /**
             * Daftar berkas dokumen yang diunggah (multiple upload, format multipart/form-data).
             * Ekstensi yang diizinkan: pdf, doc, docx, xlsx.
             */
            'documents' => ['required', 'array', 'min:1', 'max:'.config('documents.max_files_per_upload')],

            /**
             * Berkas individual dalam daftar dokumen.
             *
             * @example {"name":"dokumen.pdf","type":"application/pdf"}
             */
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
