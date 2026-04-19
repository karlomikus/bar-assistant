<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Image;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\File;
use BarAssistant\Domain\Image\Image;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;

final class ImageTest extends TestCase
{
    public function test_creates_image_with_file_and_authors(): void
    {
        $file = File::from('/uploads/image.jpg', 'jpg');
        $authors = Authors::createdBy(new UserId(1));
        $timestamps = RecordTimestamps::createdNow();

        $image = Image::create(
            file: $file,
            authors: $authors,
            recordTimestamps: $timestamps
        );

        $this->assertNull($image->getId());
        $this->assertTrue($image->isTransient());
        $this->assertEquals($file, $image->getFile());
        $this->assertEquals($authors, $image->getAuthors());
        $this->assertEquals($timestamps, $image->getRecordTimestamps());
    }

    public function test_creates_image_with_all_properties(): void
    {
        $file = File::from('/uploads/image.jpg', 'jpg', 'placeholder123');
        $authors = Authors::createdBy(new UserId(5));
        $timestamps = RecordTimestamps::createdNow();
        $copyright = 'Copyright © 2024';
        $sort = 3;

        $image = Image::create(
            file: $file,
            authors: $authors,
            recordTimestamps: $timestamps,
            copyright: $copyright,
            sort: $sort,
        );

        $this->assertEquals($copyright, $image->getCopyright());
        $this->assertEquals($sort, $image->getSort());
    }

    public function test_sets_image_id(): void
    {
        $file = File::from('/uploads/image.jpg', 'jpg');
        $authors = Authors::createdBy(new UserId(1));
        $timestamps = RecordTimestamps::createdNow();
        $image = Image::create($file, $authors, $timestamps);

        $imageId = new ImageId(42);
        $result = $image->setId($imageId);

        $this->assertSame($image, $result);
        $this->assertEquals($imageId, $image->getId());
        $this->assertFalse($image->isTransient());
    }

    public function test_cannot_change_id_on_existing_image(): void
    {
        $file = File::from('/uploads/image.jpg', 'jpg');
        $authors = Authors::createdBy(new UserId(1));
        $timestamps = RecordTimestamps::createdNow();
        $image = Image::create($file, $authors, $timestamps);
        $image->setId(new ImageId(42));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot change the ID of an existing image');

        $image->setId(new ImageId(43));
    }

    public function test_updates_image_details(): void
    {
        $file = File::from('/uploads/image.jpg', 'jpg');
        $authors = Authors::createdBy(new UserId(1));
        $timestamps = RecordTimestamps::createdNow();
        $image = Image::create($file, $authors, $timestamps);

        $newCopyright = 'Updated Copyright © 2025';
        $newSort = 5;
        $newUserId = new UserId(2);
        $updatedAt = new DateTimeImmutable();

        $result = $image->updateDetails(
            userId: $newUserId,
            updatedAt: $updatedAt,
            copyright: $newCopyright,
            sort: $newSort
        );

        $this->assertSame($image, $result);
        $this->assertEquals($newCopyright, $image->getCopyright());
        $this->assertEquals($newSort, $image->getSort());
        $this->assertTrue($image->getAuthors()->isUpdated());
        $this->assertEquals($newUserId, $image->getAuthors()->getUpdatedBy());
        $this->assertEquals($updatedAt, $image->getRecordTimestamps()->getUpdatedAt());
    }
}
