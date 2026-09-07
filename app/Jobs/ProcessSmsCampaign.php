<?php

namespace App\Jobs;

use App\Enums\SmsCampaignStatus;
use App\Models\SmsCampaign;
use App\Services\MnotifySmsService;
use App\Services\SmsCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessSmsCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public int $campaignId,
    ) {}

    public function handle(SmsCampaignService $campaigns, MnotifySmsService $mnotifySms): void
    {
        $campaign = SmsCampaign::query()->find($this->campaignId);

        if ($campaign === null) {
            Log::warning('ProcessSmsCampaign: campaign not found.', [
                'campaign_id' => $this->campaignId,
            ]);

            return;
        }

        if ($campaign->status === SmsCampaignStatus::Completed) {
            return;
        }

        $phones = $campaigns->resolvePhones(
            $campaign->audience,
            $campaign->custom_phones ?? [],
        );

        $campaign->forceFill([
            'status' => SmsCampaignStatus::Sending,
            'started_at' => $campaign->started_at ?? now(),
            'recipient_count' => $phones->count(),
        ])->save();

        Log::info('ProcessSmsCampaign: sending started.', [
            'campaign_id' => $campaign->id,
            'audience' => $campaign->audience->value,
            'recipient_count' => $phones->count(),
            'message_length' => strlen($campaign->message),
            'message_preview' => MnotifySmsService::messagePreviewForLog($campaign->message),
        ]);

        $sent = 0;
        $failed = 0;

        foreach ($phones as $phone) {
            try {
                $ok = $mnotifySms->send($phone, $campaign->message);
            } catch (Throwable $e) {
                Log::error('ProcessSmsCampaign: send threw.', [
                    'campaign_id' => $campaign->id,
                    'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
                $ok = false;
            }

            if ($ok) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $campaign->forceFill([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $sent === 0 && $failed > 0
                ? SmsCampaignStatus::Failed
                : SmsCampaignStatus::Completed,
            'completed_at' => now(),
        ])->save();

        Log::info('ProcessSmsCampaign: sending finished.', [
            'campaign_id' => $campaign->id,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $campaign->status->value,
        ]);
    }
}
