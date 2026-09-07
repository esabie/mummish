<?php

namespace App\Models;

use App\Enums\SmsAudience;
use App\Enums\SmsCampaignStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCampaign extends Model
{
    protected $fillable = [
        'audience',
        'message',
        'custom_phones',
        'recipient_count',
        'sent_count',
        'failed_count',
        'status',
        'created_by_user_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'audience' => SmsAudience::class,
        'status' => SmsCampaignStatus::class,
        'custom_phones' => 'array',
        'recipient_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
