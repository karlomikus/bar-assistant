<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Services\ImageService;
use Intervention\Image\ImageManagerStatic;

class ImageServiceTest extends TestCase
{
    public function testGenerateThumbHash()
    {
        $logMock = $this->mock(LogManager::class);
        $imageService = new ImageService($logMock);

        $interventionImageInstance = ImageManagerStatic::make(base_path('tests/fixtures/cocktail.jpg'));

        $hashKey = $imageService->generateThumbHash($interventionImageInstance);

        $this->assertNotNull($hashKey);
    }
}
