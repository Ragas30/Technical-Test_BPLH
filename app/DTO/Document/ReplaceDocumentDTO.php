<?php

namespace App\DTO\Document;

use Illuminate\Http\UploadedFile;

final readonly class ReplaceDocumentDTO
{
    public function __construct(
        public UploadedFile $file,
    ) {}
}
