<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CommentAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly User $reviewer,
        private readonly string $notes,
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
            'title' => 'Komentar Baru',
            'message' => 'Komentar baru dari '.$this->reviewer->name.' pada project '.$this->project->project_number.'.',
            'type' => 'review_comment',
            'notes' => $this->notes,
            'reviewer' => $this->reviewer->name,
            'project_id' => $this->project->id,
            'project_number' => $this->project->project_number,
            'action_url' => '/projects/'.$this->project->id,
        ];
    }
}
