<?php

namespace App\Notifications;

use App\Enums\ReviewStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReviewDecisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly User $reviewer,
        private readonly ReviewStatus $status,
        private readonly ?string $notes,
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
        $labels = [
            ReviewStatus::Approved->value => ['title' => 'Review Disetujui', 'type' => 'review_approved', 'message' => 'Project '.$this->project->project_number.' disetujui.'],
            ReviewStatus::Rejected->value => ['title' => 'Review Ditolak', 'type' => 'review_rejected', 'message' => 'Project '.$this->project->project_number.' ditolak.'],
            ReviewStatus::Revision->value => ['title' => 'Revisi Diminta', 'type' => 'revision_requested', 'message' => 'Revisi diminta untuk project '.$this->project->project_number.'.'],
        ];

        $label = $labels[$this->status->value] ?? ['title' => 'Hasil Review', 'type' => 'review_decision', 'message' => 'Project '.$this->project->project_number.' telah di-review.'];

        return [
            'title' => $label['title'],
            'message' => $label['message'],
            'type' => $label['type'],
            'notes' => $this->notes,
            'reviewer' => $this->reviewer->name,
            'project_id' => $this->project->id,
            'project_number' => $this->project->project_number,
            'action_url' => '/projects/'.$this->project->id,
        ];
    }
}
