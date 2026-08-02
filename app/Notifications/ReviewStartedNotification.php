<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReviewStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly User $reviewer,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Review Dimulai',
            'message' => 'Project '.$this->project->project_number.' sedang ditinjau oleh '.$this->reviewer->name.'.',
            'type' => 'review_started',
            'project_id' => $this->project->id,
            'project_number' => $this->project->project_number,
            'action_url' => '/projects/'.$this->project->id,
        ];
    }
}
