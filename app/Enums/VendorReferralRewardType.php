<?php

namespace App\Enums;

enum VendorReferralRewardType: string
{
    case Registration = 'registration';
    case Transaction = 'transaction';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registration',
            self::Transaction => 'Transaction',
        };
    }
}
