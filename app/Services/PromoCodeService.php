<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Support\AppLog;
use App\Support\LogSanitizer;
use Illuminate\Validation\ValidationException;

class PromoCodeService
{
    public function findByCode(?string $code): ?PromoCode
    {
        $normalized = $this->normalizeCode($code);

        if ($normalized === '') {
            return null;
        }

        return PromoCode::query()->where('code', $normalized)->first();
    }

    public function findActiveByCode(?string $code): ?PromoCode
    {
        $promo = $this->findByCode($code);

        if ($promo === null || ! $promo->is_active) {
            return null;
        }

        return $promo;
    }

    /**
     * @return array{promo: PromoCode, discount_cents: int, description: string}
     */
    public function apply(?string $code, int $subtotalCents): array
    {
        $normalized = $this->normalizeCode($code);

        if ($normalized === '') {
            return [
                'promo' => null,
                'discount_cents' => 0,
                'description' => '',
            ];
        }

        AppLog::debug('[PromoCode] Apply requested.', [
            'code' => $normalized,
            'subtotal_cents' => $subtotalCents,
        ]);

        $promo = $this->findActiveByCode($normalized);

        if ($promo === null) {
            AppLog::warning('[PromoCode] Invalid code.', ['code' => $normalized]);

            throw ValidationException::withMessages([
                'promo_code' => 'This promo code is not valid.',
            ]);
        }

        $this->assertUsable($promo, $subtotalCents);

        $discountCents = $promo->discountCentsForSubtotal($subtotalCents);

        if ($discountCents < 1) {
            AppLog::warning('[PromoCode] Code does not apply to order.', [
                'code' => $normalized,
                'promo_id' => $promo->id,
                'subtotal_cents' => $subtotalCents,
            ]);

            throw ValidationException::withMessages([
                'promo_code' => 'This promo code does not apply to your order.',
            ]);
        }

        AppLog::info('[PromoCode] Applied successfully.', [
            'code' => $normalized,
            'promo_id' => $promo->id,
            'discount_cents' => $discountCents,
            'subtotal_cents' => $subtotalCents,
        ]);

        return [
            'promo' => $promo,
            'discount_cents' => $discountCents,
            'description' => $promo->description(),
        ];
    }

    public function incrementUsage(?PromoCode $promo): void
    {
        if ($promo === null) {
            return;
        }

        AppLog::info('[PromoCode] Incrementing usage.', [
            'promo_id' => $promo->id,
            'code' => $promo->code,
            'uses_count_before' => $promo->uses_count,
        ]);

        $promo->increment('uses_count');
    }

    private function assertUsable(PromoCode $promo, int $subtotalCents): void
    {
        $now = now();

        if ($promo->starts_at !== null && $promo->starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'promo_code' => 'This promo code is not active yet.',
            ]);
        }

        if ($promo->ends_at !== null && $promo->ends_at->isPast()) {
            throw ValidationException::withMessages([
                'promo_code' => 'This promo code has expired.',
            ]);
        }

        if ($promo->max_uses !== null && $promo->uses_count >= $promo->max_uses) {
            throw ValidationException::withMessages([
                'promo_code' => 'This promo code has reached its usage limit.',
            ]);
        }

        if ($promo->min_subtotal_cents !== null && $subtotalCents < $promo->min_subtotal_cents) {
            $minimum = 'GHS '.number_format($promo->min_subtotal_cents / 100, 2);

            throw ValidationException::withMessages([
                'promo_code' => "This promo code requires a minimum order of {$minimum}.",
            ]);
        }
    }

    private function normalizeCode(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }
}
