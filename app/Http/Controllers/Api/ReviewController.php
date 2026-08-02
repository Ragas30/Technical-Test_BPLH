<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\CommentRequest;
use App\Http\Requests\Review\IndexReviewRequest;
use App\Http\Requests\Review\ReviewDecisionRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Project;
use App\Models\Review;
use App\Services\ReviewService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    private const REVIEW_EXAMPLE = [
        'id' => '7c1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        'project_id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        'reviewer_id' => '5f1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        'status' => 'approved',
        'notes' => 'Dokumen teknis sudah sesuai ketentuan.',
        'reviewed_at' => '2026-08-01T11:00:00+07:00',
        'project' => [
            'id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
            'project_number' => 'PRJ-2026-00001',
            'title' => 'Pembangunan Instalasi Pengolahan Air Limbah Kawasan Industri',
            'description' => 'Pembangunan IPAL komunal untuk kawasan industri dengan kapasitas 100 m3/hari.',
            'status' => 'approved',
            'user' => [
                'id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                'name' => 'Budi Santoso',
                'email' => 'budi@docflow.test',
            ],
        ],
        'reviewer' => [
            'id' => '5f1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
            'name' => 'Reviewer BPLH',
            'email' => 'reviewer@docflow.test',
        ],
        'logs' => [
            [
                'id' => '3a1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                'action' => 'approved',
                'notes' => 'Dokumen teknis sudah sesuai ketentuan.',
                'created_at' => '2026-08-01T11:00:00+07:00',
                'reviewer' => ['id' => '5f1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', 'name' => 'Reviewer BPLH'],
            ],
        ],
        'created_at' => '2026-08-01T10:00:00+07:00',
        'updated_at' => '2026-08-01T11:00:00+07:00',
    ];

    private const REVIEW_PAGE_EXAMPLE = [
        'data' => [self::REVIEW_EXAMPLE],
        'meta' => [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 2,
            'path' => 'http://localhost:8000/api/reviews',
            'per_page' => 15,
            'to' => 15,
            'total' => 12,
        ],
    ];

    public function __construct(private readonly ReviewService $reviewService) {}

    #[Response(200, 'Daftar review dengan pagination.', examples: [self::REVIEW_PAGE_EXAMPLE])]
    public function index(IndexReviewRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Review::class);

        $reviewerId = $request->user()->hasRole(Role::Reviewer) ? $request->user()->id : null;

        return ReviewResource::collection($this->reviewService->paginate($request->toDTO(), $reviewerId));
    }

    #[Response(200, 'Detail review beserta timeline.', examples: [['data' => self::REVIEW_EXAMPLE]])]
    public function show(Review $review): ReviewResource
    {
        $this->authorize('view', $review);

        return ReviewResource::make($this->reviewService->find($review->id));
    }

    #[Response(201, 'Review dimulai dan status project menjadi sedang ditinjau.', examples: [['data' => self::REVIEW_EXAMPLE]])]
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('start', [Review::class, $project]);

        return ReviewResource::make($this->reviewService->start($project, $request->user()))
            ->response()
            ->setStatusCode(201);
    }

    #[Response(200, 'Review disetujui dan project berstatus disetujui.', examples: [['data' => self::REVIEW_EXAMPLE]])]
    public function approve(ReviewDecisionRequest $request, Review $review): ReviewResource
    {
        $this->authorize('decide', $review);

        return ReviewResource::make($this->reviewService->approve($review, $request->user(), $request->toDTO()->notes));
    }

    #[Response(200, 'Review ditolak dan project berstatus ditolak.', examples: [['data' => self::REVIEW_EXAMPLE]])]
    public function reject(ReviewDecisionRequest $request, Review $review): ReviewResource
    {
        $this->authorize('decide', $review);

        return ReviewResource::make($this->reviewService->reject($review, $request->user(), (string) $request->toDTO()->notes));
    }

    #[Response(200, 'Revisi diminta dan project berstatus revisi.', examples: [['data' => self::REVIEW_EXAMPLE]])]
    public function revision(ReviewDecisionRequest $request, Review $review): ReviewResource
    {
        $this->authorize('decide', $review);

        return ReviewResource::make($this->reviewService->requestRevision($review, $request->user(), (string) $request->toDTO()->notes));
    }

    #[Response(200, 'Komentar berhasil ditambahkan ke review.', examples: [['data' => self::REVIEW_EXAMPLE]])]
    public function comment(CommentRequest $request, Review $review): ReviewResource
    {
        $this->authorize('comment', $review);

        return ReviewResource::make($this->reviewService->comment($review, $request->user(), $request->toDTO()->notes));
    }
}
