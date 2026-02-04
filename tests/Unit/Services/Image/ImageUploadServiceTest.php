<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Image;

use Tests\TestCase;
use Psr\Log\NullLogger;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Services\Image\ImageUploadService;

class ImageUploadServiceTest extends TestCase
{
    public function test_it_uploads_image(): void
    {
        Storage::fake('uploads');
        $filesystem = Storage::disk('uploads');
        $logger = new NullLogger();

        $imageService = new ImageUploadService($filesystem, $logger);
        $fakeImageContent = $this->getFakeImageContent('jpg');

        $result = $imageService->uploadImage($fakeImageContent);

        $this->assertNotNull($result);
        $this->assertEquals('webp', $result->extension);
        $this->assertStringStartsWith('temp/', $result->path);
        $this->assertNotNull($result->placeholderHash);
        $this->assertTrue($filesystem->exists($result->path));
    }
}
