<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'visit_modes' => 'array',
        'languages' => 'array',
        'highlights' => 'array',
        'review_count' => 'integer',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
