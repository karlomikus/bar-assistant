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

    public function save(Image $image): Image;
}
