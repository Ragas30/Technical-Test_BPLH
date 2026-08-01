<?php

namespace App\Enums;

use App\Traits\HasEnumValues;

enum Permission: string
{
    use HasEnumValues;

    case DashboardView = 'dashboard.view';

    case UserViewAny = 'user.view_any';
    case UserView = 'user.view';
    case UserCreate = 'user.create';
    case UserUpdate = 'user.update';
    case UserDelete = 'user.delete';
    case UserRestore = 'user.restore';

    case RoleViewAny = 'role.view_any';
    case RoleView = 'role.view';
    case RoleCreate = 'role.create';
    case RoleUpdate = 'role.update';
    case RoleDelete = 'role.delete';

    case PermissionViewAny = 'permission.view_any';
    case PermissionView = 'permission.view';
    case PermissionAssign = 'permission.assign';

    case ProjectViewAny = 'project.view_any';
    case ProjectView = 'project.view';
    case ProjectCreate = 'project.create';
    case ProjectUpdate = 'project.update';
    case ProjectDelete = 'project.delete';
    case ProjectSubmit = 'project.submit';

    case DocumentUpload = 'document.upload';
    case DocumentDownload = 'document.download';
    case DocumentDelete = 'document.delete';

    case ReviewViewAny = 'review.view_any';
    case ReviewView = 'review.view';
    case ReviewStart = 'review.start';
    case ReviewApprove = 'review.approve';
    case ReviewReject = 'review.reject';
    case ReviewRevision = 'review.revision';
    case ReviewComment = 'review.comment';

    case ActivityViewAny = 'activity.view_any';
    case ActivityView = 'activity.view';

    case NotificationViewAny = 'notification.view_any';
    case NotificationView = 'notification.view';

    case ExportExcel = 'export.excel';
    case ExportPdf = 'export.pdf';
}
