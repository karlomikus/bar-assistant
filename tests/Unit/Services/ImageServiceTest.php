<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Log\LogManager;
use Intervention\Image\ImageManager;
use Kami\Cocktail\Services\ImageService;

class ImageServiceTest extends TestCase
{
    public function testGenerateThumbHash()
    {
        $logMock = $this->mock(LogManager::class);
        $imageService = new ImageService($logMock);

        $manager = ImageManager::imagick();
        $interventionImageInstance = $manager->read(base_path('tests/fixtures/cocktail.jpg'));

        $hashKey = $imageService->generateThumbHash($interventionImageInstance);

        $this->assertNotNull($hashKey);
    }
}
