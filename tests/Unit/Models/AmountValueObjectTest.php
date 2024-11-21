<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\TestCase;
use Kami\Cocktail\Models\UnitValueObject;
use Kami\Cocktail\Models\AmountValueObject;

class AmountValueObjectTest extends TestCase
{
    public function testToString(): void
    {
        $result = new AmountValueObject(30.0, new UnitValueObject('ml'));

        $this->assertSame('30 ml', (string) $result);

        $result = new AmountValueObject(30.0, new UnitValueObject('ml'), 45.0);

        $this->assertSame('30 ml - 45 ml', (string) $result);
    }

    public function testConvertTo(): void
    {
        $ml = new AmountValueObject(30.0, new UnitValueObject('ml'));
        $result = $ml->convertTo(new UnitValueObject('oz'));

        $this->assertSame('1 oz', (string) $result);

        $ml = new AmountValueObject(30.0, new UnitValueObject('ml'));
        $result = $ml->convertTo(new UnitValueObject('unknown'));

        $this->assertSame('30 ml', (string) $result);

        $ml = new AmountValueObject(30.0, new UnitValueObject('unknown'));
        $result = $ml->convertTo(new UnitValueObject('oz'));

        $this->assertSame('30 unknown', (string) $result);
    }
}
