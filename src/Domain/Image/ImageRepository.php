<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Image;

interface ImageRepository
{
    /**
     * @param ImageId[] $ids
     * @return Image[]
     */
    public function findMany(array $ids): array;

    public function findById(ImageId $id): ?Image;

    public function save(Image $image): Image;

    public function delete(ImageId $id): void;
}
