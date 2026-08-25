<?php

namespace App\Models;

use App\Enums\HealthProfessionalApprovalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthProfessional extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'title',
        'specialty',
        'about',
        'location',
        'phone',
        'email',
        'payment_method',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'mobile_money_provider',
        'mobile_money_name',
        'mobile_money_number',
        'visit_modes',
        'languages',
        'highlights',
        'booking_note',
        'response_time',
        'experience',
        'image_path',
        'image_url',
        'review_count',
        'rating',
        'is_active',
        'approval_status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    protected $casts = [
        'visit_modes' => 'array',
        'languages' => 'array',
        'highlights' => 'array',
        'review_count' => 'integer',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
        'approval_status' => HealthProfessionalApprovalStatus::class,
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

    public function services(): HasMany
    {
        return $this->hasMany(HealthProfessionalService::class)->orderBy('sort_order')->orderBy('id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(HealthProfessionalAvailability::class)->where('is_active', true);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(HealthBooking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(HealthProfessionalReview::class)->latest();
    }

    public function isApproved(): bool
    {
        return $this->approval_status === HealthProfessionalApprovalStatus::Approved;
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === HealthProfessionalApprovalStatus::Pending;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_active && $this->isApproved();
    }

    public function paymentMethodLabel(): ?string
    {
        return match ($this->payment_method) {
            'bank' => 'Bank',
            'mobile_money' => 'Mobile money',
            default => null,
        };
    }

    public function hasPaymentDetails(): bool
    {
        return filled($this->payment_method);
    }

    /**
     * @param  array{
     *     payment_method: string,
     *     bank_name?: string|null,
     *     bank_account_name?: string|null,
     *     bank_account_number?: string|null,
     *     mobile_money_provider?: string|null,
     *     mobile_money_name?: string|null,
     *     mobile_money_number?: string|null
     * }  $data
     */
    public function applyPayoutDetails(array $data): void
    {
        if (($data['payment_method'] ?? null) === 'bank') {
            $data['mobile_money_provider'] = null;
            $data['mobile_money_name'] = null;
            $data['mobile_money_number'] = null;
        } else {
            $data['bank_name'] = null;
            $data['bank_account_name'] = null;
            $data['bank_account_number'] = null;
        }

        $this->update($data);
    }

    /**
     * Profiles that appear on public Health Services listings and can accept bookings.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('approval_status', HealthProfessionalApprovalStatus::Approved);
    }

    /**
     * @return array{amount: int|null, unit: string}
     */
    public static function parseExperience(?string $experience): array
    {
        if ($experience !== null && preg_match('/^(\d+)\s+(day|days|month|months|year|years)\b/i', trim($experience), $matches) === 1) {
            $singular = strtolower(rtrim($matches[2], 's'));

            return [
                'amount' => (int) $matches[1],
                'unit' => match ($singular) {
                    'day' => 'days',
                    'month' => 'months',
                    default => 'years',
                },
            ];
        }

        return [
            'amount' => null,
            'unit' => 'years',
        ];
    }

    public static function formatExperience(int $amount, string $unit): string
    {
        $unit = strtolower($unit);
        $singular = match ($unit) {
            'days' => 'day',
            'months' => 'month',
            default => 'year',
        };
        $plural = match ($unit) {
            'days' => 'days',
            'months' => 'months',
            default => 'years',
        };

        return $amount.' '.($amount === 1 ? $singular : $plural);
    }
}
