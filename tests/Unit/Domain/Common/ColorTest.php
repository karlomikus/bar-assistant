<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Color;

final class ColorTest extends TestCase
{
    public function test_accepts_full_hex_with_hash(): void
    {
        $color = Color::fromHexString('#Aa33FF');

        $this->assertSame('#Aa33FF', $color->toHexString());
    }

    public function test_accepts_full_hex_without_hash(): void
    {
        $color = Color::fromHexString('00ffee');

        $this->assertSame('#00ffee', $color->toHexString());
    }

    public function test_expands_short_hex_notation(): void
    {
        $color = Color::fromHexString('#abc');

        $this->assertSame('#aabbcc', $color->toHexString());
    }

    public function test_rejects_invalid_hex_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid hex color format');

        Color::fromHexString('#abcdex');
    }
}
