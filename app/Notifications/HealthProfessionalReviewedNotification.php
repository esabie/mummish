<?php

namespace App\Notifications;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Models\HealthProfessional;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class HealthProfessionalReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public HealthProfessional $professional,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return match ($this->professional->approval_status) {
            HealthProfessionalApprovalStatus::Approved => [
                'type' => 'health_professional_approved',
                'title' => 'Profile approved',
                'body' => 'Your Health Services profile is live. Patients can find and book you.',
                'url' => route('health-professionals.dashboard', [], false),
                'health_professional_id' => $this->professional->id,
            ],
            HealthProfessionalApprovalStatus::Suspended => [
                'type' => 'health_professional_suspended',
                'title' => 'Profile suspended',
                'body' => 'Your Health Services profile was suspended and is no longer visible.'.(
                    filled($this->professional->rejection_reason)
                        ? ' '.$this->professional->rejection_reason
                        : ''
                ),
                'url' => route('health-professionals.dashboard', [], false),
                'health_professional_id' => $this->professional->id,
            ],
            default => [
                'type' => 'health_professional_rejected',
                'title' => 'Profile not approved',
                'body' => 'Your Health Services profile was not approved.'.(
                    filled($this->professional->rejection_reason)
                        ? ' '.$this->professional->rejection_reason
                        : ''
                ),
                'url' => route('health-professionals.dashboard', [], false),
                'health_professional_id' => $this->professional->id,
            ],
        };
    }
}
