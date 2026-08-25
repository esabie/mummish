<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthProfessionalReview extends Model
{
    protected $fillable = [
        'health_booking_id',
        'health_professional_id',
        'patient_name',
        'rating',
        'comment',
        'is_published',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_published' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(HealthBooking::class, 'health_booking_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(HealthProfessional::class, 'health_professional_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
