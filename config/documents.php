<?php

return [

    'disk' => env('DOCUMENTS_DISK', 'local'),

    'directory' => 'project-documents',

    'allowed_extensions' => ['pdf', 'doc', 'docx', 'xlsx'],

    'max_size_kb' => 10240,

    'max_files_per_upload' => 10,

];
