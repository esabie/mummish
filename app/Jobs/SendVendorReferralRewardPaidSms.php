<?php

namespace App\Jobs;

use App\Enums\VendorReferralRewardType;
use App\Models\VendorReferralReward;
use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendVendorReferralRewardPaidSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $rewardId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendVendorReferralRewardPaidSms: job started.', [
            'attempt' => $this->attempts(),
            'reward_id' => $this->rewardId,
        ]);

        $reward = VendorReferralReward::query()
            ->with(['referrer', 'application', 'order'])
            ->find($this->rewardId);

        if (! $reward) {
            Log::warning('SendVendorReferralRewardPaidSms: reward not found.', [
                'reward_id' => $this->rewardId,
            ]);

            return;
        }

        $phone = trim((string) ($reward->referrer?->phone ?? ''));

        if ($phone === '') {
            Log::warning('SendVendorReferralRewardPaidSms: referrer phone missing.', [
                'reward_id' => $reward->id,
                'referrer_id' => $reward->vendor_referrer_id,
            ]);

            return;
        }

        $firstName = trim(strtok((string) ($reward->referrer?->name ?? ''), ' ') ?: 'there');
        $amount = 'GHS '.number_format($reward->amount_cents / 100, 2);
        $shopName = trim((string) ($reward->application?->shop_name ?? ''));
        $orderNumber = trim((string) ($reward->order?->order_number ?? ''));

        $message = match ($reward->type) {
            VendorReferralRewardType::Registration => $shopName !== ''
                ? "Hi {$firstName}, your registration referral reward of {$amount} for vendor {$shopName} has been paid. Thank you for helping Mummish grow."
                : "Hi {$firstName}, your registration referral reward of {$amount} has been paid. Thank you for helping Mummish grow.",
            VendorReferralRewardType::Transaction => $orderNumber !== ''
                ? "Hi {$firstName}, your sales commission referral reward of {$amount} for order {$orderNumber} has been paid. Thank you for supporting Mummish."
                : "Hi {$firstName}, your sales commission referral reward of {$amount} has been paid. Thank you for supporting Mummish.",
            default => "Hi {$firstName}, your referral reward of {$amount} has been paid. Thank you for growing Mummish.",
        };

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendVendorReferralRewardPaidSms: failed to send SMS.', [
                'attempt' => $this->attempts(),
                'reward_id' => $reward->id,
                'referrer_id' => $reward->vendor_referrer_id,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendVendorReferralRewardPaidSms: SMS sent.', [
            'attempt' => $this->attempts(),
            'reward_id' => $reward->id,
            'referrer_id' => $reward->vendor_referrer_id,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }
}
