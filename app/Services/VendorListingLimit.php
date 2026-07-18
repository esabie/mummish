<?php

namespace App\Services;

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorApplication;

class VendorListingLimit
{
    public function maxListingsFor(User $user): ?int
    {
        $application = $user->vendorApplication;

        if ($application === null) {
            return 0;
        }

        if ($application->status === VendorApplicationStatus::Approved) {
            return null;
        }

        if ($application->status === VendorApplicationStatus::Rejected) {
            return 0;
        }

        $limit = config('marketplace.max_listings_while_pending');

        return is_int($limit) && $limit >= 0 ? $limit : null;
    }

    public function currentListingCount(User $user): int
    {
        return $user->products()->count();
    }

    public function canAddListing(User $user): bool
    {
        $max = $this->maxListingsFor($user);

        if ($max === null) {
            return true;
        }

        return $this->currentListingCount($user) < $max;
    }

    public function remainingListings(User $user): ?int
    {
        $max = $this->maxListingsFor($user);

        if ($max === null) {
            return null;
        }

        return max(0, $max - $this->currentListingCount($user));
    }

    public function assertCanAddListing(User $user): void
    {
        if ($this->canAddListing($user)) {
            return;
        }

        $application = $user->vendorApplication;
        $max = $this->maxListingsFor($user) ?? 0;

        if ($application?->status === VendorApplicationStatus::Rejected) {
            throw new \InvalidArgumentException('Your vendor application was not approved. You cannot list products.');
        }

        if ($application === null) {
            throw new \InvalidArgumentException('Submit a vendor application before listing products.');
        }

        throw new \InvalidArgumentException($this->limitMessage($user));
    }

    public function limitMessage(User $user): string
    {
        $application = $user->vendorApplication;
        $max = $this->maxListingsFor($user) ?? 0;

        if ($application?->status === VendorApplicationStatus::Rejected) {
            return 'Your vendor application was not approved. You cannot list products.';
        }

        if ($application === null) {
            return 'Submit a vendor application before listing products.';
        }

        if ($max === 0) {
            return 'You cannot list products at this time.';
        }

        return "While your shop is pending approval you can list up to {$max} products. Remove a listing or wait for approval to add more.";
    }
}
