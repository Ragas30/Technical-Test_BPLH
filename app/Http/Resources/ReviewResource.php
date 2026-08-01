<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'reviewer_id' => $this->reviewer_id,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'project_number' => $this->project->project_number,
                'title' => $this->project->title,
                'description' => $this->project->description,
                'status' => $this->project->status?->value,
                'user' => $this->when($this->project->relationLoaded('user'), fn () => [
                    'id' => $this->project->user->id,
                    'name' => $this->project->user->name,
                    'email' => $this->project->user->email,
                ]),
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'email' => $this->reviewer->email,
            ]),
            'logs' => ReviewLogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
