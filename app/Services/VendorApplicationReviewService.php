<?php

namespace App\Services;

use App\Enums\VendorApplicationStatus;
use App\Jobs\SendVendorApplicationStatusSms;
use App\Models\User;
use App\Models\VendorApplication;
use App\Notifications\VendorApplicationReviewedNotification;
use App\Support\AppLog;
use InvalidArgumentException;

class VendorApplicationReviewService
{
    public function __construct(
        private readonly ShopSlugGenerator $shopSlugGenerator,
        private readonly VendorReferralRewardService $referralRewards,
    ) {}

    public function approve(VendorApplication $application, User $reviewer): void
    {
        $this->assertPending($application);

        $slug = $application->shop_slug
            ?? $this->shopSlugGenerator->generate($application->shop_name, $application->id);

        $application->update([
            'status' => VendorApplicationStatus::Approved,
            'shop_slug' => $slug,
            'rejection_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewer->id,
        ]);

        AppLog::info('[VendorApplication] Approved.', [
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'shop_slug' => $slug,
            'reviewer_user_id' => $reviewer->id,
        ]);

        $this->referralRewards->awardRegistrationReward($application->fresh());

        SendVendorApplicationStatusSms::dispatch(
            $application->fresh(),
            VendorApplicationStatus::Approved,
        );

        $application->user?->notify(new VendorApplicationReviewedNotification($application->fresh()));
    }

    public function reject(VendorApplication $application, User $reviewer, string $reason): void
    {
        $this->assertPending($application);

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        $application->update([
            'status' => VendorApplicationStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewer->id,
        ]);

        AppLog::info('[VendorApplication] Rejected.', [
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'reviewer_user_id' => $reviewer->id,
            'reason_length' => strlen($reason),
        ]);

        SendVendorApplicationStatusSms::dispatch(
            $application->fresh(),
            VendorApplicationStatus::Rejected,
        );

        $application->user?->notify(new VendorApplicationReviewedNotification($application->fresh()));
    }

    private function assertPending(VendorApplication $application): void
    {
        if ($application->status !== VendorApplicationStatus::Pending) {
            throw new InvalidArgumentException('Only pending applications can be reviewed.');
        }
    }
}
