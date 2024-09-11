<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use Tests\TestCase;
use Kami\Cocktail\Rules\ValidCurrency;

class ValidCurrencyTest extends TestCase
{
    public function test_validation_passes(): void
    {
        $hasFailed = false;
        $failed = function () use (&$hasFailed) {
            $hasFailed = true;
        };

        $rule = new ValidCurrency();
        $rule->validate('currency', 'USD', $failed);

        $this->assertFalse($hasFailed);
    }

    public function test_validation_fails(): void
    {
        $hasFailed = false;
        $failed = function () use (&$hasFailed) {
            $hasFailed = true;
        };

        $rule = new ValidCurrency();
        $rule->validate('currency', 'test', $failed);

        $this->assertTrue($hasFailed);
    }
}
