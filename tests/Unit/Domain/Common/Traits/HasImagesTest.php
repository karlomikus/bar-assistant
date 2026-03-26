<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common\Traits;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Traits\HasImages;

final class HasImagesTest extends TestCase
{
    use HasImages;

    public function test_getImages_returns_empty_array_initially(): void
    {
        $images = $this->getImages();

        $this->assertSame([], $images);
    }

    public function test_addImage_adds_single_image(): void
    {
        $imageId = new ImageId(1);
        $this->addImage($imageId);

        $images = $this->getImages();

        $this->assertCount(1, $images);
        $this->assertTrue($images[0]->equals($imageId));
    }

    public function test_addImage_prevents_duplicates(): void
    {
        $imageId = new ImageId(1);
        $this->addImage($imageId);
        $this->addImage($imageId);

        $images = $this->getImages();

        $this->assertCount(1, $images);
    }

    public function test_removeImage_removes_correct_item(): void
    {
        $imageId1 = new ImageId(1);
        $imageId2 = new ImageId(2);
        $imageId3 = new ImageId(3);

        $this->addImage($imageId1);
        $this->addImage($imageId2);
        $this->addImage($imageId3);

        $this->removeImage($imageId2);

        $images = $this->getImages();

        $this->assertCount(2, $images);
        $this->assertTrue($images[0]->equals($imageId1));
        $this->assertTrue($images[1]->equals($imageId3));
    }

    public function test_removeImage_with_nonexistent_id_does_nothing(): void
    {
        $imageId = new ImageId(1);
        $this->addImage($imageId);

        $this->removeImage(new ImageId(999));

        $images = $this->getImages();

        $this->assertCount(1, $images);
    }

    public function test_removeAllImages_clears_array(): void
    {
        $this->addImage(new ImageId(1));
        $this->addImage(new ImageId(2));
        $this->addImage(new ImageId(3));

        $this->removeAllImages();

        $images = $this->getImages();

        $this->assertSame([], $images);
    }

    public function test_setImages_replaces_all_images(): void
    {
        $this->addImage(new ImageId(1));
        $this->addImage(new ImageId(2));

        $newImages = [
            new ImageId(3),
            new ImageId(4),
            new ImageId(5),
        ];
        $this->setImages($newImages);

        $images = $this->getImages();

        $this->assertCount(3, $images);
        $this->assertTrue($images[0]->equals(new ImageId(3)));
        $this->assertTrue($images[1]->equals(new ImageId(4)));
        $this->assertTrue($images[2]->equals(new ImageId(5)));
    }

    public function test_setImages_with_empty_array_clears_images(): void
    {
        $this->addImage(new ImageId(1));
        $this->addImage(new ImageId(2));

        $this->setImages([]);

        $images = $this->getImages();

        $this->assertSame([], $images);
    }

    public function test_hasImage_returns_true_for_existing(): void
    {
        $imageId = new ImageId(1);
        $this->addImage($imageId);

        $this->assertTrue($this->hasImage($imageId));
    }

    public function test_hasImage_returns_false_for_missing(): void
    {
        $this->addImage(new ImageId(1));

        $this->assertFalse($this->hasImage(new ImageId(999)));
    }

    public function test_hasImage_returns_false_when_no_images(): void
    {
        $this->assertFalse($this->hasImage(new ImageId(1)));
    }

    public function test_methods_support_fluent_interface(): void
    {
        $result = $this->addImage(new ImageId(1))
            ->addImage(new ImageId(2))
            ->removeImage(new ImageId(1))
            ->setImages([new ImageId(3)])
            ->removeAllImages();

        $this->assertSame($this, $result);
    }
}
