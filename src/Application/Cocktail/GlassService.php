<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Cocktail\Glass;
use BarAssistant\Domain\Cocktail\GlassId;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\GlassRepository;
use BarAssistant\Application\Cocktail\DTO\CreateGlass;
use BarAssistant\Application\Cocktail\DTO\GlassResult;
use BarAssistant\Application\Cocktail\DTO\UpdateGlass;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class GlassService
{
    public function __construct(private GlassRepository $glassRepository)
    {
    }

    public function createGlass(CreateGlass $request): GlassResult
    {
        $glass = Glass::create(
            barId: new BarId($request->barId),
            name: Name::fromString($request->name),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: $request->description,
            volume: $request->units ? AmountWithUnits::from($request->volume ?? 0, Unit::from($request->units)) : null,
        );

        if (count($request->images) > 0) {
            $glass = $this->assignImages($glass, $request->images);
        }

        $glass = $this->glassRepository->save($glass);

        return GlassResult::fromGlass($glass);
    }

    public function updateGlass(UpdateGlass $request): GlassResult
    {
        $glass = $this->glassRepository->findById(new GlassId($request->glassId));
        if ($glass === null) {
            throw new EntityNotFoundException('Glass not found');
        }

        $glass->updateDetails(
            name: Name::fromString($request->name),
            description: $request->description,
            volume: $request->units ? AmountWithUnits::from($request->volume ?? 0, Unit::from($request->units)) : null,
        );

        if (count($request->images) > 0) {
            $glass->removeAllImages();
            $glass = $this->assignImages($glass, $request->images);
        }

        $glass = $this->glassRepository->save($glass);

        return GlassResult::fromGlass($glass);
    }

    public function deleteGlass(int $glassId): void
    {
        $id = new GlassId($glassId);
        $glass = $this->glassRepository->findById($id);
        if ($glass === null) {
            throw new EntityNotFoundException('Glass not found');
        }

        $this->glassRepository->delete($id);
    }

    /**
     * @param non-empty-array<int> $imageIds
     */
    private function assignImages(Glass $glass, array $imageIds): Glass
    {
        foreach ($imageIds as $imageId) {
            $glass->addImage(new ImageId($imageId));
        }

        return $glass;
    }
}
