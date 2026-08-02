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

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::UnderReview => 'Sedang Ditinjau',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Revision => 'Revisi',
        };
    }
}
