<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityLog\IndexActivityLogRequest;
use App\Http\Resources\ActivityLogResource;
use App\Services\ActivityLogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(IndexActivityLogRequest $request): AnonymousResourceCollection
    {
        return ActivityLogResource::collection($this->activityLogService->paginate($request->toDTO()));
    }

    public function mine(IndexActivityLogRequest $request): AnonymousResourceCollection
    {
        return ActivityLogResource::collection(
            $this->activityLogService->paginate($request->toDTO(), $request->user()->id)
        );
    }
}
