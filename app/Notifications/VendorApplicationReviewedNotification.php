<?php

namespace App\Notifications;

use App\Enums\VendorApplicationStatus;
use App\Models\VendorApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VendorApplicationReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VendorApplication $application,
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
        $approved = $this->application->status === VendorApplicationStatus::Approved;

        return [
            'type' => $approved ? 'application_approved' : 'application_rejected',
            'title' => $approved ? 'Application approved' : 'Application not approved',
            'body' => $approved
                ? "Your shop “{$this->application->shop_name}” is live. Start listing products."
                : 'Your seller application was not approved.'.(
                    filled($this->application->rejection_reason)
                        ? ' '.$this->application->rejection_reason
                        : ''
                ),
            'url' => route('vendor.dashboard', [], false),
            'application_id' => $this->application->id,
        ];
    }
}
