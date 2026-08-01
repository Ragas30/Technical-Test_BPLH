<?php

namespace App\Enums;

use App\Traits\HasEnumValues;

enum ReviewStatus: string
{
    use HasEnumValues;

    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revision = 'revision';
}
