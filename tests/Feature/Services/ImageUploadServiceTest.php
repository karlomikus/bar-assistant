<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use Psr\Log\NullLogger;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Services\Image\ImageUploadService;
use Kami\Cocktail\Services\Image\ImageStorageService;
use Kami\Cocktail\Services\Image\DTO\ImageUploadResult;

class ImageUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_image(): void
    {
        Storage::fake('uploads');
        $filesystem = Storage::disk('uploads');
        $logger = new NullLogger();

        $imageService = new ImageUploadService($filesystem, $logger, new ImageStorageService());
        $fakeImageContent = $this->getFakeImageContent('jpg');

        $result = $imageService->uploadImage($fakeImageContent);

        $this->assertNotNull($result);
        $this->assertEquals('webp', $result->extension);
        $this->assertStringStartsWith('temp/', $result->path);
        $this->assertNotNull($result->placeholderHash);
        $this->assertTrue($filesystem->exists($result->path));
    }

    public function test_change_image_with_temporary_image(): void
    {
        Storage::fake('uploads');
        $filesystem = Storage::disk('uploads');
        $logger = new NullLogger();

        // Create a temporary image in database
        $image = Image::factory()->create([
            'file_path' => 'temp/some-temp-image.webp',
            'imageable_id' => null,
            'imageable_type' => null,
        ]);

        $imageService = new ImageUploadService($filesystem, $logger, new ImageStorageService());
        $newImagePath = 'temp/new-image.webp';
        $newImageResult = new ImageUploadResult(
            path: $newImagePath,
            extension: 'webp',
            placeholderHash: 'test-hash',
        );

        $result = $imageService->changeImage($image->id, $newImageResult);

        $this->assertEquals($newImagePath, $result->path);
        $this->assertEquals('webp', $result->extension);
        $this->assertEquals('test-hash', $result->placeholderHash);
    }

    public function test_change_image_with_attached_resource(): void
    {
        Storage::fake('uploads');
        $filesystem = Storage::disk('uploads');
        $logger = new NullLogger();

        // Create a cocktail with attached image
        $cocktail = Cocktail::factory()->create();
        $image = Image::factory()->create([
            'file_path' => 'cocktails/' . $cocktail->bar_id . '/old-image.webp',
            'imageable_id' => $cocktail->id,
            'imageable_type' => Cocktail::class,
        ]);

        // Create temp file for old image
        $filesystem->put($image->file_path, 'old image content');

        $imageService = new ImageUploadService($filesystem, $logger, new ImageStorageService());
        $newImagePath = 'temp/new-image.webp';
        $filesystem->put($newImagePath, 'new image content');

        $newImageResult = new ImageUploadResult(
            path: $newImagePath,
            extension: 'webp',
            placeholderHash: 'new-hash',
        );

        $result = $imageService->changeImage($image->id, $newImageResult);

        // Should move from temp to proper location
        $this->assertTrue(str_contains($result->path, 'cocktails/'));
        $this->assertFalse($filesystem->exists($newImagePath));
        $this->assertTrue($filesystem->exists($result->path));
        $this->assertEquals('webp', $result->extension);
        $this->assertEquals('new-hash', $result->placeholderHash);
    }

    public function test_change_image_deletes_old_file(): void
    {
        Storage::fake('uploads');
        $filesystem = Storage::disk('uploads');
        $logger = new NullLogger();

        // Create a cocktail with attached image
        $cocktail = Cocktail::factory()->create();
        $oldFilePath = 'cocktails/' . $cocktail->bar_id . '/old-image.webp';
        $image = Image::factory()->create([
            'file_path' => $oldFilePath,
            'imageable_id' => $cocktail->id,
            'imageable_type' => Cocktail::class,
        ]);

        // Create the old file
        $filesystem->put($oldFilePath, 'old image content');
        $this->assertTrue($filesystem->exists($oldFilePath));

        $imageService = new ImageUploadService($filesystem, $logger, new ImageStorageService());
        $newImagePath = 'temp/new-image.webp';
        $filesystem->put($newImagePath, 'new image content');

        $newImageResult = new ImageUploadResult(
            path: $newImagePath,
            extension: 'webp',
            placeholderHash: 'new-hash',
        );

        $imageService->changeImage($image->id, $newImageResult);

        // Old file should be deleted
        $this->assertFalse($filesystem->exists($oldFilePath));
    }

    public function test_change_image_handles_missing_new_file(): void
    {
        Storage::fake('uploads');
        $filesystem = Storage::disk('uploads');
        $logger = new NullLogger();

        // Create a cocktail with attached image
        $cocktail = Cocktail::factory()->create();
        $oldFilePath = 'cocktails/' . $cocktail->bar_id . '/old-image.webp';
        $image = Image::factory()->create([
            'file_path' => $oldFilePath,
            'imageable_id' => $cocktail->id,
            'imageable_type' => Cocktail::class,
        ]);

        $filesystem->put($oldFilePath, 'old image content');

        $imageService = new ImageUploadService($filesystem, $logger, new ImageStorageService());
        $newImagePath = 'temp/nonexistent-image.webp';

        $newImageResult = new ImageUploadResult(
            path: $newImagePath,
            extension: 'webp',
            placeholderHash: 'new-hash',
        );

        $result = $imageService->changeImage($image->id, $newImageResult);

        // Should still return the new path even if file doesn't exist (move would fail silently)
        $this->assertTrue(str_contains($result->path, 'cocktails/'));
        $this->assertEquals('webp', $result->extension);
    }

    public function test_change_image_handles_missing_old_file(): void
    {
        Storage::fake('uploads');
        $filesystem = Storage::disk('uploads');
        $logger = new NullLogger();

        // Create a cocktail with attached image but don't create the actual file
        $cocktail = Cocktail::factory()->create();
        $oldFilePath = 'cocktails/' . $cocktail->bar_id . '/missing-image.webp';
        $image = Image::factory()->create([
            'file_path' => $oldFilePath,
            'imageable_id' => $cocktail->id,
            'imageable_type' => Cocktail::class,
        ]);

        $imageService = new ImageUploadService($filesystem, $logger, new ImageStorageService());
        $newImagePath = 'temp/new-image.webp';
        $filesystem->put($newImagePath, 'new image content');

        $newImageResult = new ImageUploadResult(
            path: $newImagePath,
            extension: 'webp',
            placeholderHash: 'new-hash',
        );

        // Should not throw an exception even if old file doesn't exist
        $result = $imageService->changeImage($image->id, $newImageResult);

        $this->assertNotNull($result);
        $this->assertTrue(str_contains($result->path, 'cocktails/'));
    }

    public function test_change_image_keeps_catalog_object(): void
    {
        Storage::fake('uploads');
        Storage::fake('catalog');
        $filesystem = Storage::disk('uploads');
        $catalogPath = 'catalog/2026.08.21/cocktails/example/image.webp';
        Storage::disk('catalog')->put($catalogPath, 'catalog image content');
        $cocktail = Cocktail::factory()->create();
        $image = Image::factory()->for($cocktail, 'imageable')->create([
            'file_path' => $catalogPath,
            'disk' => 'catalog',
            'storage_origin' => 'catalog',
        ]);
        $filesystem->put('temp/new-image.webp', 'new image content');

        $result = (new ImageUploadService($filesystem, new NullLogger(), new ImageStorageService()))->changeImage(
            $image->id,
            new ImageUploadResult(path: 'temp/new-image.webp', extension: 'webp', placeholderHash: 'new-hash'),
        );

        Storage::disk('catalog')->assertExists($catalogPath);
        $filesystem->assertExists($result->path);
    }
}
