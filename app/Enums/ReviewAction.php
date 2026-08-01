<?php

namespace App\Enums;

use App\Traits\HasEnumValues;

enum ReviewAction: string
{
    use HasEnumValues;

    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revision = 'revision';
    case Comment = 'comment';
}
