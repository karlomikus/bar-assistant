<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use DateTimeImmutable;
use BarAssistant\Domain\Common\File;
use BarAssistant\Domain\Image\Image;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use Kami\Cocktail\Models\Image as ModelImage;
use BarAssistant\Domain\Common\RecordTimestamps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentImageRepository;

final class EloquentImageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_and_finds_image(): void
    {
        $membership = $this->setupBarMembership();
        $repository = new EloquentImageRepository();

        $image = Image::create(
            file: File::from('/uploads/coupe.jpg', 'jpg', 'abc123'),
            authors: Authors::createdBy(new UserId($membership->user_id)),
            recordTimestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2025-01-01 10:00:00')),
            copyright: 'Bar Assistant',
            sort: 2,
        );

        $savedImage = $repository->save($image);

        $this->assertNotNull($savedImage->getId());
        $this->assertDatabaseHas('images', [
            'id' => $savedImage->getId()?->value,
            'file_path' => '/uploads/coupe.jpg',
            'file_extension' => 'jpg',
            'placeholder_hash' => 'abc123',
            'copyright' => 'Bar Assistant',
            'sort' => 2,
            'created_user_id' => $membership->user_id,
        ]);

        $foundImage = $repository->findById($savedImage->getId() ?? new ImageId(0));

        $this->assertNotNull($foundImage);
        $this->assertSame('/uploads/coupe.jpg', $foundImage->getFile()->path);
        $this->assertSame('jpg', $foundImage->getFile()->extension);
        $this->assertSame('abc123', $foundImage->getFile()->placeholderHash);
        $this->assertSame('Bar Assistant', $foundImage->getCopyright());
        $this->assertSame(2, $foundImage->getSort());
    }

    public function test_it_deletes_image(): void
    {
        $membership = $this->setupBarMembership();
        $image = ModelImage::factory()->create(['created_user_id' => $membership->user_id]);

        $repository = new EloquentImageRepository();
        $repository->delete(new ImageId($image->id));

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
    }
}
