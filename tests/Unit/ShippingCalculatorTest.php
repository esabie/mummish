<?php

namespace Tests\Unit;

use App\Services\ShippingCalculator;
use Tests\TestCase;

class ShippingCalculatorTest extends TestCase
{
    public function test_uses_region_rate_for_city_without_override(): void
    {
        $calculator = new ShippingCalculator;

        // Accra within zone 5–10 km default
        $this->assertSame(3500, $calculator->centsForLocation('Greater Accra', 'Ashongman'));
        // Kumasi metro default
        $this->assertSame(3000, $calculator->centsForLocation('Ashanti', 'Ahodwo'));
    }

    public function test_uses_city_override_when_configured(): void
    {
        $calculator = new ShippingCalculator;

        // Greater Accra extended (Kasoa, Adenta, Tema corridor)
        $this->assertSame(6500, $calculator->centsForLocation('Greater Accra', 'Tema'));
        $this->assertSame(6500, $calculator->centsForLocation('Greater Accra', 'Afienya'));
        $this->assertSame(3000, $calculator->centsForLocation('Greater Accra', 'Osu'));
        $this->assertSame(4500, $calculator->centsForLocation('Greater Accra', 'East Legon'));

        // Kumasi CBD vs Greater Kumasi environs
        $this->assertSame(2500, $calculator->centsForLocation('Ashanti', 'Kumasi'));
        $this->assertSame(6000, $calculator->centsForLocation('Ashanti', 'Ejisu'));

        // Takoradi CBD vs corridor / extended
        $this->assertSame(2500, $calculator->centsForLocation('Western', 'Takoradi'));
        $this->assertSame(4000, $calculator->centsForLocation('Western', 'Sekondi'));
        $this->assertSame(6000, $calculator->centsForLocation('Western', 'Shama'));
    }

    public function test_uses_intercity_next_day_rates_for_named_destinations(): void
    {
        $calculator = new ShippingCalculator;

        $this->assertSame(5500, $calculator->centsForLocation('Central', 'Cape Coast'));
        $this->assertSame(6000, $calculator->centsForLocation('Volta', 'Ho'));
        $this->assertSame(5500, $calculator->centsForLocation('Bono', 'Sunyani'));
    }

    public function test_returns_zero_when_location_incomplete(): void
    {
        $calculator = new ShippingCalculator;

        $this->assertSame(0, $calculator->centsForLocation('', 'Tema'));
        $this->assertSame(0, $calculator->centsForLocation('Greater Accra', ''));
    }
}
