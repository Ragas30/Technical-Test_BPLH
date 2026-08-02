<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityLog\IndexActivityLogRequest;
use App\Http\Resources\ActivityLogResource;
use App\Services\ActivityLogService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    private const LOG_PAGE_EXAMPLE = [
        'data' => [
            [
                'id' => '3a1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                'action' => 'project_created',
                'description' => 'Project PRJ-2026-00001 dibuat.',
                'properties' => ['project_number' => 'PRJ-2026-00001'],
                'created_at' => '2026-08-01T09:00:00+07:00',
                'user' => ['id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', 'name' => 'Budi Santoso'],
                'project' => [
                    'id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                    'project_number' => 'PRJ-2026-00001',
                    'title' => 'Pembangunan Instalasi Pengolahan Air Limbah',
                ],
            ],
        ],
        'meta' => [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 2,
            'path' => 'http://localhost:8000/api/activity-logs',
            'per_page' => 15,
            'to' => 15,
            'total' => 24,
        ],
    ];

    public function __construct(private readonly ActivityLogService $activityLogService) {}

    #[Response(200, 'Seluruh aktivitas pengguna dengan pagination (khusus admin).', examples: [self::LOG_PAGE_EXAMPLE])]
    public function index(IndexActivityLogRequest $request): AnonymousResourceCollection
    {
        $dto = $request->toDTO();

        return ActivityLogResource::collection(
            $request->filled('cursor')
                ? $this->activityLogService->cursorPaginate($dto)
                : $this->activityLogService->paginate($dto)
        );
    }

    #[Response(200, 'Aktivitas milik pengguna yang sedang login.', examples: [self::LOG_PAGE_EXAMPLE])]
    public function mine(IndexActivityLogRequest $request): AnonymousResourceCollection
    {
        $dto = $request->toDTO();

        return ActivityLogResource::collection(
            $request->filled('cursor')
                ? $this->activityLogService->cursorPaginate($dto, $request->user()->id)
                : $this->activityLogService->paginate($dto, $request->user()->id)
        );
    }
}
