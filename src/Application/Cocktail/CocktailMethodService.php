<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Cocktail\CocktailMethod;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\CocktailMethodRepository;
use BarAssistant\Application\Cocktail\DTO\CocktailMethodResult;
use BarAssistant\Application\Cocktail\DTO\CreateCocktailMethod;
use BarAssistant\Application\Cocktail\DTO\UpdateCocktailMethod;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class CocktailMethodService
{
    public function __construct(
        private CocktailMethodRepository $cocktailMethodRepository,
    ) {
    }

    /**
     * Creates a new cocktail method based on the provided request data.
     */
    public function createCocktailMethod(CreateCocktailMethod $request): CocktailMethodResult
    {
        $cocktailMethod = CocktailMethod::create(
            barId: new BarId($request->barId),
            name: Name::fromString($request->name),
            dilution: Dilution::fromFloat($request->dilutionPercentage),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: $request->description,
        );

        $cocktailMethod = $this->cocktailMethodRepository->save($cocktailMethod);

        return CocktailMethodResult::fromCocktailMethod($cocktailMethod);
    }

    /**
     * Updates an existing cocktail method based on the provided request data.
     */
    public function updateCocktailMethod(UpdateCocktailMethod $request): CocktailMethodResult
    {
        $cocktailMethod = $this->cocktailMethodRepository->findById(new MethodId($request->id));
        if ($cocktailMethod === null) {
            throw new EntityNotFoundException('Cocktail method not found');
        }

        $cocktailMethod->updateDetails(
            name: Name::fromString($request->name),
            dilution: Dilution::fromFloat($request->dilutionPercentage),
            description: $request->description,
        );

        $cocktailMethod = $this->cocktailMethodRepository->save($cocktailMethod);

        return CocktailMethodResult::fromCocktailMethod($cocktailMethod);
    }

    public function deleteCocktailMethod(int $id): void
    {
        $methodId = new MethodId($id);
        $cocktailMethod = $this->cocktailMethodRepository->findById($methodId);
        if ($cocktailMethod === null) {
            throw new EntityNotFoundException('Cocktail method not found');
        }

        $this->cocktailMethodRepository->delete($methodId);
    }
}
