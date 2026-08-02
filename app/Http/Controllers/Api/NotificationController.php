<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    #[Response(200, 'Daftar notifikasi milik pengguna yang sedang login beserta jumlah belum dibaca.', examples: [[
        'data' => [
            [
                'id' => '1f0d3d2c-1a2b-3c4d-5e6f-7a8b9c0d1e2f',
                'type' => 'App\Notifications\ReviewDecisionNotification',
                'data' => [
                    'title' => 'Keputusan Review',
                    'message' => 'Review project PRJ-2026-00001 disetujui.',
                    'action_url' => '/projects/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                ],
                'read_at' => null,
                'created_at' => '2026-08-01T11:00:00+07:00',
            ],
        ],
        'meta' => [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 1,
            'path' => 'http://localhost:8000/api/notifications',
            'per_page' => 15,
            'to' => 1,
            'total' => 1,
        ],
        'unread_count' => 1,
    ]])]
    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection($this->notificationService->paginate($request->user()))
            ->additional([
                'unread_count' => $this->notificationService->unreadCount($request->user()),
            ]);
    }

    #[Response(200, 'Jumlah notifikasi yang belum dibaca.', examples: [['unread_count' => 1]])]
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    #[Response(200, 'Notifikasi ditandai telah dibaca.', examples: [['message' => 'Notifikasi ditandai telah dibaca.', 'unread_count' => 0]])]
    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $this->notificationService->markAsRead($request->user(), $notification);

        return response()->json([
            'message' => 'Notifikasi ditandai telah dibaca.',
            'unread_count' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    #[Response(200, 'Semua notifikasi ditandai telah dibaca.', examples: [['message' => 'Semua notifikasi telah dibaca.', 'unread_count' => 0]])]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'message' => 'Semua notifikasi telah dibaca.',
            'unread_count' => 0,
        ]);
    }
}
