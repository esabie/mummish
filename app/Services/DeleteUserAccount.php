<?php

namespace App\Services;

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Support\AppLog;
use App\Support\LogSanitizer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeleteUserAccount
{
    public function __construct(
        private readonly ProductImageService $productImages,
    ) {}

    public function delete(User $user): void
    {
        if ($user->isAdmin()) {
            throw new InvalidArgumentException('Admin accounts cannot be deleted from the profile page.');
        }

        $productsDeleted = 0;
        $originalEmail = (string) $user->email;
        $applicationId = null;
        $applicationStatusBefore = null;

        DB::transaction(function () use ($user, $originalEmail, &$productsDeleted, &$applicationId, &$applicationStatusBefore) {
            if ($user->isVendor()) {
                $productsDeleted = $this->productImages->deleteListingsForVendor($user);

                $application = $user->vendorApplication;
                if ($application !== null) {
                    $applicationId = $application->id;
                    $applicationStatusBefore = $application->status?->value;

                    if (! $application->isDeleted()) {
                        $application->update([
                            'status' => VendorApplicationStatus::Deleted,
                            'rejection_reason' => 'Account deleted by vendor.',
                        ]);
                    }
                }
            }

            $released = $user->releaseEmailForReuse();

            AppLog::info('[Account] Releasing email before soft-delete.', [
                'user_id' => $user->id,
                'email_masked' => LogSanitizer::maskEmail($originalEmail),
                'email_released' => $released,
                'released_email' => $user->email,
            ]);

            $user->delete();
        });

        AppLog::info('[Account] User deleted their account.', [
            'user_id' => $user->id,
            'role' => $user->role?->value,
            'email_masked' => LogSanitizer::maskEmail($originalEmail),
            'application_id' => $applicationId,
            'application_status_before' => $applicationStatusBefore,
            'products_deleted' => $productsDeleted,
            'soft_deleted' => true,
            'email_freed_for_reuse' => true,
        ]);
    }
}
