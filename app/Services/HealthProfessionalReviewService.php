<?php

namespace App\Services;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Jobs\SendHealthProfessionalStatusSms;
use App\Models\HealthProfessional;
use App\Models\User;
use App\Notifications\HealthProfessionalReviewedNotification;
use App\Support\AppLog;
use InvalidArgumentException;

class HealthProfessionalReviewService
{
    public function approve(HealthProfessional $professional, User $reviewer): void
    {
        if (! in_array($professional->approval_status, [
            HealthProfessionalApprovalStatus::Pending,
            HealthProfessionalApprovalStatus::Rejected,
            HealthProfessionalApprovalStatus::Suspended,
        ], true)) {
            throw new InvalidArgumentException('Only pending, rejected, or suspended professionals can be approved.');
        }

        $professional->update([
            'approval_status' => HealthProfessionalApprovalStatus::Approved,
            'rejection_reason' => null,
            'is_active' => true,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewer->id,
        ]);

        AppLog::info('[HealthProfessional] Approved.', [
            'health_professional_id' => $professional->id,
            'user_id' => $professional->user_id,
            'reviewer_user_id' => $reviewer->id,
        ]);

        $fresh = $professional->fresh();

        SendHealthProfessionalStatusSms::dispatch(
            $fresh,
            HealthProfessionalApprovalStatus::Approved,
        );

        $fresh->user?->notify(new HealthProfessionalReviewedNotification($fresh));
    }

    public function reject(HealthProfessional $professional, User $reviewer, string $reason): void
    {
        if ($professional->approval_status !== HealthProfessionalApprovalStatus::Pending) {
            throw new InvalidArgumentException('Only pending professionals can be rejected.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        $professional->update([
            'approval_status' => HealthProfessionalApprovalStatus::Rejected,
            'rejection_reason' => $reason,
            'is_active' => false,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewer->id,
        ]);

        AppLog::info('[HealthProfessional] Rejected.', [
            'health_professional_id' => $professional->id,
            'user_id' => $professional->user_id,
            'reviewer_user_id' => $reviewer->id,
            'reason_length' => strlen($reason),
        ]);

        $fresh = $professional->fresh();

        SendHealthProfessionalStatusSms::dispatch(
            $fresh,
            HealthProfessionalApprovalStatus::Rejected,
        );

        $fresh->user?->notify(new HealthProfessionalReviewedNotification($fresh));
    }

    public function suspend(HealthProfessional $professional, User $reviewer, ?string $reason = null): void
    {
        if ($professional->approval_status !== HealthProfessionalApprovalStatus::Approved) {
            throw new InvalidArgumentException('Only approved professionals can be suspended.');
        }

        $reason = trim((string) $reason);
        if ($reason === '') {
            $reason = 'Your Health Services profile was suspended by an administrator.';
        }

        $professional->update([
            'approval_status' => HealthProfessionalApprovalStatus::Suspended,
            'rejection_reason' => $reason,
            'is_active' => false,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewer->id,
        ]);

        AppLog::info('[HealthProfessional] Suspended.', [
            'health_professional_id' => $professional->id,
            'user_id' => $professional->user_id,
            'reviewer_user_id' => $reviewer->id,
            'reason_length' => strlen($reason),
        ]);

        $fresh = $professional->fresh();

        SendHealthProfessionalStatusSms::dispatch(
            $fresh,
            HealthProfessionalApprovalStatus::Suspended,
        );

        $fresh->user?->notify(new HealthProfessionalReviewedNotification($fresh));
    }
}
