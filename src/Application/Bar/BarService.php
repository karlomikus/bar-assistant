<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Application\Bar\DTO\BarResult;
use BarAssistant\Domain\Bar\Bar;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Application\Bar\DTO\CreateBarRequest;
use BarAssistant\Application\Bar\DTO\UpdateBarRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Image\ImageId;

final readonly class BarService
{
    public function __construct(
        private BarRepository $barRepository,
    ) {
    }

    public function createBar(CreateBarRequest $request): BarResult
    {
        $bar = Bar::create(
            name: Name::fromString($request->name),
            authors: Authors::createdBy(new UserId($request->createdUserId)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        if (count($request->images) > 0) {
            $this->assignImages($bar, $request->images);
        }

        $bar = $this->barRepository->save($bar);

        return new BarResult(id: $bar->getId()->value);
    }

    public function updateBar(UpdateBarRequest $request, UserId $userId): Bar
    {
        $bar = $this->barRepository->findById($request->barId);

        if ($bar === null) {
            throw new EntityNotFoundException('Bar not found');
        }

        $bar = $bar->updateDetails(
            name: $request->name,
            updatedBy: $userId,
        );

        $this->barRepository->save($bar);

        return $bar;
    }

    /**
     * Assign images to a bar.
     *
     * @param non-empty-array<int> $imageIds
     */
    private function assignImages(Bar $bar, array $imageIds): Bar
    {
        foreach ($imageIds as $imageId) {
            $bar->addImage(new ImageId($imageId));
        }

        return $bar;
    }
}
