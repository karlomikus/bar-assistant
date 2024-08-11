<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Image;

use Tests\TestCase;
use Intervention\Image\ImageManager;
use Kami\Cocktail\Services\Image\ImageHashingService;

class ImageHashingServiceTest extends TestCase
{
    public function testUploadAndSaveImages(): void
    {
        $imageService = new ImageHashingService();

        $service = ImageManager::imagick();
        $interventionImageInstance = $service->read(base_path('tests/fixtures/cocktail.jpg'));
        $hashKey = $imageService->generatePlaceholderHash($interventionImageInstance);

        $this->assertNotNull($hashKey);
    }
}
