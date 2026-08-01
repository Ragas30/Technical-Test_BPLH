<?php

namespace App\Enums;

use App\Traits\HasEnumValues;

enum Role: string
{
    use HasEnumValues;

    case Admin = 'admin';
    case Reviewer = 'reviewer';
    case Applicant = 'applicant';
}
