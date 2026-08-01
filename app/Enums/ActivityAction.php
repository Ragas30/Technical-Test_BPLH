<?php

namespace App\Enums;

use App\Traits\HasEnumValues;

enum ActivityAction: string
{
    use HasEnumValues;

    case Login = 'login';
    case Logout = 'logout';
    case ProjectCreated = 'project_created';
    case ProjectUpdated = 'project_updated';
    case ProjectDeleted = 'project_deleted';
    case ProjectSubmitted = 'project_submitted';
    case DocumentUploaded = 'document_uploaded';
    case DocumentDeleted = 'document_deleted';
    case ReviewStarted = 'review_started';
    case ReviewApproved = 'review_approved';
    case ReviewRejected = 'review_rejected';
    case RevisionRequested = 'revision_requested';
}
