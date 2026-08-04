<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthProfessionalService extends Model
{
    protected $fillable = [
        'health_professional_id',
        'name',
        'visit_mode',
        'price_cedis',
        'duration_minutes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_cedis' => 'integer',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(HealthProfessional::class, 'health_professional_id');
    }
}
