<?php

namespace App\Http\Resources;

use App\Models\Review;
use App\Models\ReviewLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_number' => $this->project_number,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews
                ->map(fn (Review $review) => $this->reviewPayload($review))
                ->values()),
            'latest_review' => $this->when(
                $this->relationLoaded('reviews'),
                fn () => $this->reviews->isEmpty() ? null : $this->reviewPayload($this->reviews->first()),
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewPayload(Review $review): array
    {
        return [
            'id' => $review->id,
            'status' => $review->status->value,
            'notes' => $review->notes,
            'reviewed_at' => $review->reviewed_at?->toISOString(),
            'created_at' => $review->created_at?->toISOString(),
            'reviewer' => $review->relationLoaded('reviewer') && $review->reviewer ? [
                'id' => $review->reviewer->id,
                'name' => $review->reviewer->name,
            ] : null,
            'logs' => $review->relationLoaded('logs')
                ? $review->logs
                    ->map(fn (ReviewLog $log) => [
                        'id' => $log->id,
                        'action' => $log->action->value,
                        'notes' => $log->notes,
                        'created_at' => $log->created_at?->toISOString(),
                        'reviewer' => $log->relationLoaded('reviewer') && $log->reviewer ? [
                            'id' => $log->reviewer->id,
                            'name' => $log->reviewer->name,
                        ] : null,
                    ])
                    ->values()
                : [],
        ];
    }
}
