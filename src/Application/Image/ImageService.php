<?php

declare(strict_types=1);

namespace BarAssistant\Application\Image;

use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Image\DTO\CreateImage;
use BarAssistant\Application\Image\DTO\ImageResult;
use BarAssistant\Application\Image\DTO\UpdateImageRequest;
use BarAssistant\Domain\Image\Image;
use BarAssistant\Domain\Image\ImageRepository;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\File;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\User\UserId;
use DateTimeImmutable;

final readonly class ImageService
{
    public function __construct(
        private ImageRepository $imageRepository,
    ) {
    }

    public function createImage(CreateImage $imageRequest): ImageResult
    {
        $image = Image::create(
            file: File::from($imageRequest->imageFilePath, $imageRequest->imageFileExtension, $imageRequest->placeholderHash),
            authors: Authors::createdBy(new UserId($imageRequest->userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
            sort: $imageRequest->sort,
            copyright: $imageRequest->copyright,
        );

        $image = $this->imageRepository->save($image);

        return ImageResult::fromImage($image);
    }

    public function updateImage(UpdateImageRequest $imageRequest): ImageResult
    {
        $image = $this->imageRepository->findById(new ImageId($imageRequest->id));
        if ($image === null) {
            throw new EntityNotFoundException('Image not found');
        }

        if ($imageRequest->imageFilePath !== null) {
            $image->changeFile(File::from($imageRequest->imageFilePath, $imageRequest->imageFileExtension, $imageRequest->placeholderHash));
        }

        $image->updateDetails(
            userId: new UserId($imageRequest->userId),
            updatedAt: new DateTimeImmutable(),
            sort: $imageRequest->sort,
            copyright: $imageRequest->copyright,
        );

        $image = $this->imageRepository->save($image);

        return ImageResult::fromImage($image);
    }
}
