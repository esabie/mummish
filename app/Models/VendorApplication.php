<?php

namespace App\Models;

use App\Enums\VendorApplicationStatus;
use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorApplication extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'shop_name',
        'shop_slug',
        'logo_path',
        'business_email',
        'phone',
        'ghana_card_id',
        'category',
        'referral_code',
        'vendor_referrer_id',
        'terms_accepted',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'status' => VendorApplicationStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(VendorReferrer::class, 'vendor_referrer_id');
    }

    public function referralRewards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VendorReferralReward::class);
    }

    public function isPending(): bool
    {
        return $this->status === VendorApplicationStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === VendorApplicationStatus::Approved;
    }

    public function shopLogoUrl(): ?string
    {
        return PublicStorageUrl::fromStored($this->logo_path);
    }
}
