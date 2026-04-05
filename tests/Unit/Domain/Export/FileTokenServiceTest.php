<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Export;

use Tests\TestCase;
use DateTimeImmutable;
use BarAssistant\Domain\Export\FileTokenService;

class FileTokenServiceTest extends TestCase
{
    public function testGenerate(): void
    {
        $service = new FileTokenService('test');
        $token = $service->generate(1, 'test.zip', new DateTimeImmutable('+1 day'));

        $this->assertNotEmpty($token);
    }

    public function testCheckIsValid(): void
    {
        $service = new FileTokenService('test');
        $token = $service->generate(1, 'test.zip', new DateTimeImmutable('+1 day'));

        $this->assertTrue(
            $service->check($token, 1, 'test.zip', new DateTimeImmutable('+1 day'))
        );
    }

    public function testCheckIsExpired(): void
    {
        $service = new FileTokenService('test');
        $token = $service->generate(1, 'test.zip', new DateTimeImmutable('+1 day'));

        $this->assertFalse(
            $service->check($token, 1, 'test.zip', new DateTimeImmutable('+4 day'))
        );
    }

    public function testCheckIsInvalid(): void
    {
        $service = new FileTokenService('test');
        $token = $service->generate(1, 'a.zip', new DateTimeImmutable('+1 day'));

        $this->assertFalse(
            $service->check($token, 1, 'test.zip', new DateTimeImmutable('+1 day'))
        );
    }
}
