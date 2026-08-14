<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use Brick\Money\Currency;
use BarAssistant\Domain\Bar\Bar;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Bar\BarSettings;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Application\Bar\DTO\BarResult;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Application\Bar\DTO\CreateBarRequest;
use BarAssistant\Application\Bar\DTO\UpdateBarRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class BarService
{
    public function __construct(
        private BarRepository $barRepository,
    ) {
    }

    public function createBar(CreateBarRequest $request): BarResult
    {
        $barSettings = BarSettings::create(
            isInviteCodeEnabled: $request->isInviteCodeEnabled ?? false,
            defaultUnits: $request->defaultUnits ? Unit::from($request->defaultUnits) : null,
            defaultCurrency: $request->defaultCurrency ? Currency::of($request->defaultCurrency) : null,
        );

        $bar = Bar::create(
            name: Name::fromString($request->name),
            authors: Authors::createdBy(new UserId($request->createdUserId)),
            recordTimestamps: RecordTimestamps::createdNow(),
            settings: $barSettings,
            subtitle: $request->subtitle,
            description: $request->description,
        );

        if ($request->isPublic) {
            $bar->makePublic();
        } else {
            $bar->makePrivate();
        }

        if (count($request->images) > 0) {
            $this->assignImages($bar, $request->images);
        }

        $bar = $this->barRepository->save($bar);

        return new BarResult(id: $bar->getId()->value ?? 0, slug: $bar->getSlug()?->toString() ?? '');
    }

    public function updateBar(UpdateBarRequest $request): Bar
    {
        $bar = $this->barRepository->findById(new BarId($request->barId));
        if ($bar === null) {
            throw new EntityNotFoundException('Bar not found');
        }

        if ($request->isPublic) {
            $bar->makePublic();
        } else {
            $bar->makePrivate();
        }

        $bar->updateDetails(
            name: Name::fromString($request->name),
            subtitle: $request->subtitle,
            description: $request->description,
            updatedBy: new UserId($request->userId),
        );

        $bar->updateSettings(BarSettings::create(
            isInviteCodeEnabled: $request->isInviteCodeEnabled ?? false,
            defaultUnits: $request->defaultUnits ? Unit::from($request->defaultUnits) : null,
            defaultCurrency: $request->defaultCurrency ? Currency::of($request->defaultCurrency) : null,
        ));

        $bar->removeAllImages();
        if (count($request->images) > 0) {
            $this->assignImages($bar, $request->images);
        }

        $this->barRepository->save($bar);

        return $bar;
    }

    public function deleteBar(int $barId): void
    {
        $id = new BarId($barId);
        $bar = $this->barRepository->findById($id);

        if ($bar === null) {
            throw new EntityNotFoundException('Bar not found');
        }

        $this->barRepository->delete($id);
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
