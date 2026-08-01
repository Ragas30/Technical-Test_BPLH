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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function index(IndexReviewRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Review::class);

        $reviewerId = $request->user()->hasRole(Role::Reviewer) ? $request->user()->id : null;

        return ReviewResource::collection($this->reviewService->paginate($request->toDTO(), $reviewerId));
    }

    public function show(Review $review): ReviewResource
    {
        $this->authorize('view', $review);

        return ReviewResource::make($this->reviewService->find($review->id));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('start', [Review::class, $project]);

        return ReviewResource::make($this->reviewService->start($project, $request->user()))
            ->response()
            ->setStatusCode(201);
    }

    public function approve(ReviewDecisionRequest $request, Review $review): ReviewResource
    {
        $this->authorize('decide', $review);

        return ReviewResource::make($this->reviewService->approve($review, $request->user(), $request->toDTO()->notes));
    }

    public function reject(ReviewDecisionRequest $request, Review $review): ReviewResource
    {
        $this->authorize('decide', $review);

        return ReviewResource::make($this->reviewService->reject($review, $request->user(), (string) $request->toDTO()->notes));
    }

    public function revision(ReviewDecisionRequest $request, Review $review): ReviewResource
    {
        $this->authorize('decide', $review);

        return ReviewResource::make($this->reviewService->requestRevision($review, $request->user(), (string) $request->toDTO()->notes));
    }

    public function comment(CommentRequest $request, Review $review): ReviewResource
    {
        $this->authorize('comment', $review);

        return ReviewResource::make($this->reviewService->comment($review, $request->user(), $request->toDTO()->notes));
    }
}
