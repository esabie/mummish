<?php

namespace App\Enums;

enum VendorReferralRewardStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending payout',
            self::Paid => 'Paid',
        };
    }
}
