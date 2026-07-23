<?php

namespace App\Services;

class ShippingCalculator
{
    public function centsForLocation(string $region, string $city): int
    {
        if ($this->isFree()) {
            return 0;
        }

        $region = trim($region);
        $city = trim($city);

        if ($region === '' || $city === '') {
            return 0;
        }

        $cityRates = config('marketplace.shipping_rates_by_city', []);
        $cityKey = $this->cityKey($region, $city);

        if (isset($cityRates[$cityKey])) {
            return max(0, (int) $cityRates[$cityKey]);
        }

        $regionRates = config('marketplace.shipping_rates_by_region', []);

        if (isset($regionRates[$region])) {
            return max(0, (int) $regionRates[$region]);
        }

        return max(0, (int) config('marketplace.checkout_shipping_cents', 0));
    }

    /**
     * @return array<string, int>
     */
    public function regionRates(): array
    {
        if ($this->isFree()) {
            return collect(config('marketplace.shipping_rates_by_region', []))
                ->map(fn () => 0)
                ->all();
        }

        return collect(config('marketplace.shipping_rates_by_region', []))
            ->map(fn ($cents) => max(0, (int) $cents))
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function cityRates(): array
    {
        if ($this->isFree()) {
            return collect(config('marketplace.shipping_rates_by_city', []))
                ->map(fn () => 0)
                ->all();
        }

        return collect(config('marketplace.shipping_rates_by_city', []))
            ->map(fn ($cents) => max(0, (int) $cents))
            ->all();
    }

    public function cityKey(string $region, string $city): string
    {
        return $region.'|'.$city;
    }

    public function isFree(): bool
    {
        return (bool) config('marketplace.shipping_free', false);
    }
}
