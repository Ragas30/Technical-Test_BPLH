<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('change-password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'role:'.Role::Admin->value])->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:'.Permission::UserViewAny->value);
        Route::post('/', [UserController::class, 'store'])->middleware('permission:'.Permission::UserCreate->value);
        Route::get('{user}', [UserController::class, 'show'])->middleware('permission:'.Permission::UserView->value);
        Route::put('{user}', [UserController::class, 'update'])->middleware('permission:'.Permission::UserUpdate->value);
        Route::delete('{user}', [UserController::class, 'destroy'])->middleware('permission:'.Permission::UserDelete->value);
        Route::post('{user}/restore', [UserController::class, 'restore'])->middleware('permission:'.Permission::UserRestore->value);
        Route::put('{user}/roles', [UserController::class, 'assignRoles'])->middleware('permission:'.Permission::UserUpdate->value);
        Route::put('{user}/permissions', [UserController::class, 'assignPermissions'])->middleware('permission:'.Permission::PermissionAssign->value);
    });

    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:'.Permission::RoleViewAny->value);
    Route::get('permissions', [RoleController::class, 'permissions'])->middleware('permission:'.Permission::PermissionViewAny->value);
});

Route::get('/user', function (Request $request) {
    return UserResource::make($request->user()->load('roles', 'permissions'));
})->middleware('auth:sanctum');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware('auth:sanctum', 'permission:'.Permission::DashboardView->value);

Route::middleware('auth:sanctum')->prefix('activity-logs')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index'])->middleware('permission:'.Permission::ActivityViewAny->value);
    Route::get('mine', [ActivityLogController::class, 'mine'])->middleware('permission:'.Permission::ActivityView->value);
});

Route::middleware('auth:sanctum')->prefix('projects')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->middleware('permission:'.Permission::ProjectViewAny->value);
    Route::get('mine', [ProjectController::class, 'mine'])->middleware('permission:'.Permission::ProjectView->value);
    Route::post('/', [ProjectController::class, 'store'])->middleware('permission:'.Permission::ProjectCreate->value);
    Route::get('{project}', [ProjectController::class, 'show'])->middleware('permission:'.Permission::ProjectView->value);
    Route::put('{project}', [ProjectController::class, 'update'])->middleware('permission:'.Permission::ProjectUpdate->value);
    Route::delete('{project}', [ProjectController::class, 'destroy'])->middleware('permission:'.Permission::ProjectDelete->value);
    Route::post('{project}/submit', [ProjectController::class, 'submit'])->middleware('permission:'.Permission::ProjectSubmit->value);
    Route::get('{project}/documents', [DocumentController::class, 'index'])->middleware('permission:'.Permission::DocumentDownload->value);
    Route::post('{project}/documents', [DocumentController::class, 'store'])->middleware('permission:'.Permission::DocumentUpload->value);
    Route::post('{project}/reviews', [ReviewController::class, 'store'])->middleware('permission:'.Permission::ReviewStart->value);
});

Route::middleware('auth:sanctum')->prefix('documents')->group(function () {
    Route::get('{document}/download', [DocumentController::class, 'download'])->middleware('permission:'.Permission::DocumentDownload->value);
    Route::get('{document}/preview', [DocumentController::class, 'preview'])->middleware('permission:'.Permission::DocumentDownload->value);
    Route::post('{document}/replace', [DocumentController::class, 'replace'])->middleware('permission:'.Permission::DocumentUpload->value);
    Route::delete('{document}', [DocumentController::class, 'destroy'])->middleware('permission:'.Permission::DocumentDelete->value);
});

Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('{notification}/read', [NotificationController::class, 'markAsRead'])->whereUuid('notification');
});

Route::middleware('auth:sanctum')->prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index'])->middleware('permission:'.Permission::ReviewViewAny->value);
    Route::get('{review}', [ReviewController::class, 'show'])->middleware('permission:'.Permission::ReviewView->value);
    Route::post('{review}/approve', [ReviewController::class, 'approve'])->middleware('permission:'.Permission::ReviewApprove->value);
    Route::post('{review}/reject', [ReviewController::class, 'reject'])->middleware('permission:'.Permission::ReviewReject->value);
    Route::post('{review}/revision', [ReviewController::class, 'revision'])->middleware('permission:'.Permission::ReviewRevision->value);
    Route::post('{review}/comment', [ReviewController::class, 'comment'])->middleware('permission:'.Permission::ReviewComment->value);
});

Route::middleware('auth:sanctum')->prefix('export')->group(function () {
    Route::get('projects', [ExportController::class, 'projects'])->middleware('permission:'.Permission::ExportExcel->value);
    Route::get('projects/pdf', [ExportController::class, 'projectsPdf'])->middleware('permission:'.Permission::ExportPdf->value);
    Route::get('reviews', [ExportController::class, 'reviews'])->middleware('permission:'.Permission::ExportExcel->value);
    Route::get('reviews/pdf', [ExportController::class, 'reviewsPdf'])->middleware('permission:'.Permission::ExportPdf->value);
});
