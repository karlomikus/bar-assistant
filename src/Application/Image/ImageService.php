<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient;

use BarAssistant\Application\Image\DTO\CreateImage;
use BarAssistant\Application\Image\DTO\ImageResult;
use BarAssistant\Domain\Image\Image;
use BarAssistant\Domain\Image\ImageRepository;
use BarAssistant\Domain\Support\Authors;
use BarAssistant\Domain\Support\RecordTimestamps;
use BarAssistant\Domain\User\UserId;

final readonly class ImageService
{
    public function __construct(
        private ImageRepository $imageRepository,
    ) {
    }

    public function createImage(CreateImage $imageRequest): ImageResult
    {
        $image = new Image(
            path: $imageRequest->imageFilePath,
            authors: Authors::createdBy(new UserId($imageRequest->userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
            placeholderHash: $imageRequest->placeholderHash,
            sort: $imageRequest->sort,
        );

        $image = $this->imageRepository->save($image);

        return ImageResult::fromImage($image);
    }
}
