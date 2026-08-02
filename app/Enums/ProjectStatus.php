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

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Diajukan',
            self::UnderReview => 'Sedang Ditinjau',
            self::Revision => 'Revisi',
            self::Rejected => 'Ditolak',
            self::Approved => 'Disetujui',
        };
    }
}
