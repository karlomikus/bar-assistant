<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Image;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Image\ImageRepository;
use BarAssistant\Application\Image\ImageService;
use Tests\Infrastructure\InMemoryImageRepository;
use BarAssistant\Application\Image\DTO\CreateImage;
use BarAssistant\Application\Image\DTO\ImageResult;

final class ImageServiceTest extends TestCase
{
    private ImageRepository $imageRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageRepository = new InMemoryImageRepository();
    }

    public function test_creates_image_with_all_properties(): void
    {
        $service = new ImageService($this->imageRepository);
        $createRequest = new CreateImage(
            imageFilePath: '/uploads/images/cocktail.jpg',
            imageFileExtension: 'jpg',
            userId: 5,
            sort: 2,
            copyright: 'John Doe',
            placeholderHash: 'abc123def456'
        );

        $result = $service->createImage($createRequest);

        $this->assertInstanceOf(ImageResult::class, $result);
        $this->assertNotNull($result->id);
        $this->assertGreaterThan(0, $result->id);
        $this->assertEquals('/uploads/images/cocktail.jpg', $result->path);
    }

    public function test_creates_image_with_custom_sort_order(): void
    {
        $service = new ImageService($this->imageRepository);
        $customSort = 99;
        $createRequest = new CreateImage(
            imageFilePath: '/uploads/images/sorted.jpg',
            imageFileExtension: 'jpg',
            userId: 30,
            sort: $customSort
        );

        $result = $service->createImage($createRequest);

        $this->assertInstanceOf(ImageResult::class, $result);
        $this->assertNotNull($result->id);
    }

    public function test_creates_multiple_images(): void
    {
        $service = new ImageService($this->imageRepository);

        $firstResult = $service->createImage(new CreateImage(
            imageFilePath: '/uploads/images/first.jpg',
            imageFileExtension: 'jpg',
            userId: 35
        ));

        $secondResult = $service->createImage(new CreateImage(
            imageFilePath: '/uploads/images/second.jpg',
            imageFileExtension: 'jpg',
            userId: 35
        ));

        $this->assertNotEquals($firstResult->id, $secondResult->id);
        $this->assertEquals('/uploads/images/first.jpg', $firstResult->path);
        $this->assertEquals('/uploads/images/second.jpg', $secondResult->path);
    }
}
