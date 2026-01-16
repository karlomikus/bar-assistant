<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Application\Bar\DTO\BarInventoryStockChangeRequest;
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

    public function putMultipleIngredientsInStock(BarInventoryStockChangeRequest $request): void
    {
        $bar = $this->barRepository->findById(new BarId($request->barId));
        if ($bar === null) {
            throw new EntityNotFoundException('The bar was not found');
        }

        $ingredientIds = array_map(fn (int $id) => new IngredientId($id), $request->ingredientIds);

        $validIngredients = $this->ingredientRepository->checkBarOwnership($bar->getId(), $ingredientIds);
        if ($validIngredients === false) {
            throw new EntityNotFoundException('One or more ingredients were not found in the specified bar');
        }

        foreach ($ingredientIds as $ingredientId) {
            $bar->putIngredientInStock($ingredientId);
        }

        $this->barRepository->save($bar);
    }
}
