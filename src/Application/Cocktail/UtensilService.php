<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Cocktail\Utensil;
use BarAssistant\Domain\Cocktail\UtensilId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\UtensilRepository;
use BarAssistant\Application\Cocktail\DTO\CreateUtensil;
use BarAssistant\Application\Cocktail\DTO\UpdateUtensil;
use BarAssistant\Application\Cocktail\DTO\UtensilResult;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class UtensilService
{
    public function __construct(private UtensilRepository $utensilRepository)
    {
    }

    public function createUtensil(CreateUtensil $request): UtensilResult
    {
        $utensil = Utensil::create(
            barId: new BarId($request->barId),
            name: Name::fromString($request->name),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: $request->description,
        );

        $utensil = $this->utensilRepository->save($utensil);

        return UtensilResult::fromUtensil($utensil);
    }

    public function updateUtensil(UpdateUtensil $request): UtensilResult
    {
        $utensil = $this->utensilRepository->findById(new UtensilId($request->utensilId));
        if ($utensil === null) {
            throw new EntityNotFoundException('Utensil not found');
        }

        $utensil->updateDetails(
            name: Name::fromString($request->name),
            description: $request->description,
        );

        $utensil = $this->utensilRepository->save($utensil);

        return UtensilResult::fromUtensil($utensil);
    }

    public function deleteUtensil(int $utensilId): void
    {
        $id = new UtensilId($utensilId);
        $utensil = $this->utensilRepository->findById($id);
        if ($utensil === null) {
            throw new EntityNotFoundException('Utensil not found');
        }

        $this->utensilRepository->delete($id);
    }
}
