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
        $imageBuffer = file_get_contents(base_path('tests/fixtures/cocktail.jpg'));
        $hashKey = ImageHashingService::generatePlaceholderHashFromBuffer($imageBuffer);

        $this->assertSame('q+cFHwD1eXbJQopoiJaWqWd2aHBWqQcC', $hashKey);
    }

    public function testThumbnailImage(): void
    {
        $thumbnailContent = ImageThumbnailService::generateThumbnail(base_path('tests/fixtures/cocktail.jpg'));

        $this->assertNotNull($thumbnailContent);
    }

    public function testResizeImage(): void
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter */
        $disk = Storage::fake('uploads');
        if ($contents = file_get_contents(base_path('tests/fixtures/cocktail.jpg'))) {
            $image = ImageResizeService::resizeImageTo($contents, 200);
        } else {
            $this->fail('Unable to read image');
        }

        $this->assertSame(200, $image->height);

        $image->writeToFile($disk->path('resized-result.webp'));

        $disk->assertExists('resized-result.webp');
    }
}
