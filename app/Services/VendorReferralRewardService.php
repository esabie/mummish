<?php

namespace App\Services;

use App\Enums\VendorReferralRewardStatus;
use App\Enums\VendorReferralRewardType;
use App\Jobs\SendVendorReferralRewardPaidSms;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\VendorApplication;
use App\Models\VendorReferralReward;
use App\Models\VendorReferrer;
use App\Support\AppLog;
use Illuminate\Support\Facades\DB;

class VendorReferralRewardService
{
    public function findActiveReferrer(?string $code): ?VendorReferrer
    {
        $normalized = strtoupper(trim((string) $code));

        if ($normalized === '') {
            return null;
        }

        return VendorReferrer::query()
            ->active()
            ->where('code', $normalized)
            ->first();
    }

    public function awardRegistrationReward(VendorApplication $application): ?VendorReferralReward
    {
        $application->loadMissing('referrer');

        $referrer = $application->referrer;

        if ($referrer === null) {
            return null;
        }

        $amountCents = $referrer->registrationRewardCents();

        if ($amountCents <= 0) {
            AppLog::debug('[VendorReferral] Registration reward skipped — amount is zero.', [
                'application_id' => $application->id,
                'referrer_id' => $referrer->id,
            ]);

            return null;
        }

        $existing = VendorReferralReward::query()
            ->where('vendor_application_id', $application->id)
            ->where('type', VendorReferralRewardType::Registration)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $reward = VendorReferralReward::create([
            'vendor_referrer_id' => $referrer->id,
            'vendor_application_id' => $application->id,
            'type' => VendorReferralRewardType::Registration,
            'amount_cents' => $amountCents,
            'description' => "Vendor approved: {$application->shop_name}",
            'status' => VendorReferralRewardStatus::Pending,
        ]);

        AppLog::info('[VendorReferral] Registration reward created.', [
            'reward_id' => $reward->id,
            'referrer_id' => $referrer->id,
            'application_id' => $application->id,
            'amount_cents' => $amountCents,
        ]);

        return $reward;
    }

    public function awardTransactionRewards(Order $order): void
    {
        $order->loadMissing(['items']);

        foreach ($order->items as $item) {
            $this->awardTransactionRewardForItem($order, $item);
        }
    }

    private function awardTransactionRewardForItem(Order $order, OrderItem $item): ?VendorReferralReward
    {
        if ($item->vendor_user_id === null) {
            return null;
        }

        if (VendorReferralReward::query()->where('order_item_id', $item->id)->exists()) {
            return null;
        }

        $application = VendorApplication::query()
            ->where('user_id', $item->vendor_user_id)
            ->whereNotNull('vendor_referrer_id')
            ->with('referrer')
            ->first();

        if ($application?->referrer === null) {
            return null;
        }

        $referrer = $application->referrer;
        $commissionBps = $referrer->transactionCommissionBps();

        if ($commissionBps <= 0) {
            return null;
        }

        $amountCents = (int) floor($item->line_total_cents * $commissionBps / 10000);

        if ($amountCents <= 0) {
            return null;
        }

        $reward = VendorReferralReward::create([
            'vendor_referrer_id' => $referrer->id,
            'vendor_application_id' => $application->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => VendorReferralRewardType::Transaction,
            'amount_cents' => $amountCents,
            'description' => "Sale commission: {$item->product_title} (order {$order->order_number})",
            'status' => VendorReferralRewardStatus::Pending,
        ]);

        AppLog::info('[VendorReferral] Transaction reward created.', [
            'reward_id' => $reward->id,
            'referrer_id' => $referrer->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'amount_cents' => $amountCents,
        ]);

        return $reward;
    }

    public function markPaid(VendorReferralReward $reward): VendorReferralReward
    {
        if ($reward->status === VendorReferralRewardStatus::Paid) {
            return $reward;
        }

        $reward->update([
            'status' => VendorReferralRewardStatus::Paid,
            'paid_at' => now(),
        ]);

        AppLog::info('[VendorReferral] Reward marked paid.', [
            'reward_id' => $reward->id,
            'referrer_id' => $reward->vendor_referrer_id,
            'amount_cents' => $reward->amount_cents,
        ]);

        SendVendorReferralRewardPaidSms::dispatch($reward->id)->afterCommit();

        return $reward->fresh();
    }

    /**
     * @param  iterable<int, VendorReferralReward>  $rewards
     */
    public function markManyPaid(iterable $rewards): void
    {
        DB::transaction(function () use ($rewards): void {
            foreach ($rewards as $reward) {
                $this->markPaid($reward);
            }
        });
    }
}
