<?php

declare(strict_types=1);

namespace BarAssistant\Application\Image;

use BarAssistant\Application\Image\DTO\CreateImage;
use BarAssistant\Application\Image\DTO\ImageResult;
use BarAssistant\Domain\Image\Image;
use BarAssistant\Domain\Image\ImageRepository;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\User\UserId;

final readonly class ImageService
{
    public function __construct(
        private ImageRepository $imageRepository,
    ) {
    }

    public function createImage(CreateImage $imageRequest): ImageResult
    {
        $image = Image::create(
            path: $imageRequest->imageFilePath,
            fileExtension: $imageRequest->imageFileExtension,
            authors: Authors::createdBy(new UserId($imageRequest->userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
            placeholderHash: $imageRequest->placeholderHash,
            sort: $imageRequest->sort,
        );

        $image = $this->imageRepository->save($image);

        return ImageResult::fromImage($image);
    }
}
