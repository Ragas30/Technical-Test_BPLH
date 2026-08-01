<?php

namespace App\Enums;

use App\Traits\HasEnumValues;

enum ProjectStatus: string
{
    use HasEnumValues;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Revision = 'revision';
    case Rejected = 'rejected';
    case Approved = 'approved';
}
