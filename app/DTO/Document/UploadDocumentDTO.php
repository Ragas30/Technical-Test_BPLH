<?php

namespace App\DTO\Document;

use Illuminate\Http\UploadedFile;

final readonly class UploadDocumentDTO
{
    public function __construct(
        public UploadedFile $file,
    ) {}
}
