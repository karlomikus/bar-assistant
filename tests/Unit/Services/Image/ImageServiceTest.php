<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Image;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Services\Image\ImageResizeService;
use Kami\Cocktail\Services\Image\ImageHashingService;
use Kami\Cocktail\Services\Image\ImageThumbnailService;

class ImageServiceTest extends TestCase
{
    public function testGeneratePlaceholderHash(): void
    {
        $hashKey = ImageHashingService::generatePlaceholderHashFromFilepath(base_path('tests/fixtures/cocktail.jpg'));

        $this->assertSame('q+cFHwD1eXbJQopoiJaWqWd2aHBWqQcC', $hashKey);
    }

    public function testThumbnailImage(): void
    {
        $thumbnailContent = ImageThumbnailService::generateThumbnailAsJpg(base_path('tests/fixtures/cocktail.jpg'));

        $this->assertNotNull($thumbnailContent);
    }

    public function testResizeImage(): void
    {
        $disk = Storage::fake('uploads');
        $image = ImageResizeService::resizeImageTo(file_get_contents(base_path('tests/fixtures/cocktail.jpg')), 200);

        $this->assertSame(200, $image->height);

        $image->writeToFile($disk->path('resized-result.webp'));

        $disk->assertExists('resized-result.webp');
    }
}
