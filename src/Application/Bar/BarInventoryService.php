<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Application\Bar\DTO\ToggleBarInventoryStatusRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\IngredientRepository;

final readonly class BarInventoryService
{
    public function __construct(
        private BarRepository $barRepository,
        private IngredientRepository $ingredientRepository,
    ) {
    }

    public function toggleIngredientStock(ToggleBarInventoryStatusRequest $toggleRequest): void
    {
        $bar = $this->barRepository->findById(new BarId($toggleRequest->barId));
        if ($bar === null) {
            throw new EntityNotFoundException('The bar was not found');
        }

        $ingredientIds = array_map(fn (int $id) => new IngredientId($id), $toggleRequest->ingredientIds);

        // Only fetch ingredients that are part of a bar
        $ingredients = $this->ingredientRepository->findMany($bar->getId(), $ingredientIds);
        foreach ($ingredients as $ingredient) {
            $bar->toggleIngredientStock($ingredient->getId());
        }

        $this->barRepository->save($bar);
    }
}
