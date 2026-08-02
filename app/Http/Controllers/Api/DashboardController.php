<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    #[Response(200, 'Statistik dashboard sesuai role pengguna yang sedang login.', examples: [[
        'data' => [
            'role' => 'admin',
            'statistics' => [
                'total_users' => 3,
                'total_projects' => 19,
                'draft' => 5,
                'submitted' => 4,
                'under_review' => 2,
                'revision' => 3,
                'rejected' => 2,
                'approved' => 3,
            ],
            'monthly_stats' => [
                ['month' => '2026-03', 'total' => 2],
                ['month' => '2026-04', 'total' => 5],
                ['month' => '2026-05', 'total' => 4],
                ['month' => '2026-06', 'total' => 3],
                ['month' => '2026-07', 'total' => 5],
            ],
            'recent_activities' => [
                [
                    'id' => '3a1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                    'user' => 'Admin BPLH',
                    'action' => 'project_created',
                    'description' => 'Project baru dibuat.',
                    'created_at' => '2026-08-01T09:00:00+07:00',
                ],
            ],
        ],
    ]])]
    public function index(Request $request): DashboardResource
    {
        return DashboardResource::make($this->dashboardService->for($request->user()));
    }

    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}
}
