<?php

namespace App\Models;

use App\Enums\PromoCodeType;
use App\Enums\PromoCostBearer;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_subtotal_cents',
        'max_uses',
        'uses_count',
        'starts_at',
        'ends_at',
        'is_active',
        'cost_bearer',
    ];

    protected $casts = [
        'type' => PromoCodeType::class,
        'cost_bearer' => PromoCostBearer::class,
        'value' => 'integer',
        'min_subtotal_cents' => 'integer',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function discountCentsForSubtotal(int $subtotalCents): int
    {
        if ($subtotalCents < 1) {
            return 0;
        }

        $discount = match ($this->type) {
            PromoCodeType::Percent => (int) round($subtotalCents * ($this->value / 100)),
            PromoCodeType::Fixed => $this->value,
        };

        return min($subtotalCents, max(0, $discount));
    }

    public function description(): string
    {
        return match ($this->type) {
            PromoCodeType::Percent => "{$this->value}% off",
            PromoCodeType::Fixed => 'GHS '.number_format($this->value / 100, 2).' off',
        };
    }
}
