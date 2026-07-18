<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorReferrer extends Model
{
    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'registration_reward_cents',
        'transaction_commission_bps',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'registration_reward_cents' => 'integer',
        'transaction_commission_bps' => 'integer',
        'is_active' => 'boolean',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(VendorApplication::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(VendorReferralReward::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function registrationRewardCents(): int
    {
        return $this->registration_reward_cents
            ?? (int) config('marketplace.vendor_referral.registration_reward_cents', 0);
    }

    public function transactionCommissionBps(): int
    {
        return $this->transaction_commission_bps
            ?? (int) config('marketplace.vendor_referral.transaction_commission_bps', 0);
    }

    public function shareUrl(): string
    {
        return url()->route('vendor.signup', ['ref' => $this->code]);
    }

    public function formattedRegistrationReward(): string
    {
        return 'GHS '.number_format($this->registrationRewardCents() / 100, 2);
    }

    public function formattedCommissionRate(): string
    {
        $percent = $this->transactionCommissionBps() / 100;

        return rtrim(rtrim(number_format($percent, 2), '0'), '.').'%';
    }
}
