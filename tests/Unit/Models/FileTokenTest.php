<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use DateTimeImmutable;
use Kami\Cocktail\Models\FileToken;

class FileTokenTest extends TestCase
{
    public function testGenerate(): void
    {
        $token = FileToken::generate(1, 'test.zip', new DateTimeImmutable('+1 day'));

        $this->assertNotEmpty($token);
    }

    public function testCheckIsValid(): void
    {
        $token = FileToken::generate(1, 'test.zip', new DateTimeImmutable('+1 day'));

        $this->assertTrue(
            FileToken::check($token, 1, 'test.zip', new DateTimeImmutable('+1 day'))
        );
    }

    public function testCheckIsExpired(): void
    {
        $token = FileToken::generate(1, 'test.zip', new DateTimeImmutable('+1 day'));

        $this->assertFalse(
            FileToken::check($token, 1, 'test.zip', new DateTimeImmutable('+4 day'))
        );
    }

    public function testCheckIsInvalid(): void
    {
        $token = FileToken::generate(1, 'a.zip', new DateTimeImmutable('+1 day'));

        $this->assertFalse(
            FileToken::check($token, 1, 'test.zip', new DateTimeImmutable('+1 day'))
        );
    }
}
