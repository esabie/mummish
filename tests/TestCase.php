<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Local .env may enable free shipping for manual testing; keep suite on real rates.
        config(['marketplace.shipping_free' => false]);
    }
}
