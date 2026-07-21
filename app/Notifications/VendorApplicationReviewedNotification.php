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
        return match ($this->application->status) {
            VendorApplicationStatus::Approved => [
                'type' => 'application_approved',
                'title' => 'Application approved',
                'body' => "Your shop “{$this->application->shop_name}” is live. Start listing products.",
                'url' => route('vendor.dashboard', [], false),
                'application_id' => $this->application->id,
            ],
            VendorApplicationStatus::Closed => [
                'type' => 'shop_closed',
                'title' => 'Shop closed',
                'body' => 'Your shop has been closed and your product listings have been removed from the marketplace.'.(
                    filled($this->application->rejection_reason)
                        ? ' '.$this->application->rejection_reason
                        : ''
                ),
                'url' => route('vendor.dashboard', [], false),
                'application_id' => $this->application->id,
            ],
            default => [
                'type' => 'application_rejected',
                'title' => 'Application not approved',
                'body' => 'Your seller application was not approved.'.(
                    filled($this->application->rejection_reason)
                        ? ' '.$this->application->rejection_reason
                        : ''
                ),
                'url' => route('vendor.dashboard', [], false),
                'application_id' => $this->application->id,
            ],
        };
    }
}
