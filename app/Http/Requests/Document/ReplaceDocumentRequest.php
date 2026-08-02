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
            /**
             * Berkas pengganti dokumen (format multipart/form-data).
             * Ekstensi yang diizinkan: pdf, doc, docx, xlsx.
             *
             * @example {"name":"dokumen-baru.pdf","type":"application/pdf"}
             */
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
