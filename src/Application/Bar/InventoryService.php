<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Bar\DTO\BarInventoryStockChangeRequest;

final readonly class InventoryService
{
    public function __construct(
        private BarRepository $barRepository,
    ) {
    }

    /**
     * Put multiple ingredients in stock for a bar
     */
    public function putMultipleIngredientsInStock(BarInventoryStockChangeRequest $request): void
    {
        $bar = $this->barRepository->findById(new BarId($request->barId));
        if ($bar === null || $bar->isTransient()) {
            throw new EntityNotFoundException('The bar was not found');
        }

        foreach ($request->ingredientIds as $ingredientId) {
            $bar->putIngredientInStock(new IngredientId($ingredientId));
        }

        $this->barRepository->save($bar);
    }

    public function removeMultipleIngredientsFromStock(BarInventoryStockChangeRequest $request): void
    {
        $bar = $this->barRepository->findById(new BarId($request->barId));
        if ($bar === null || $bar->isTransient()) {
            throw new EntityNotFoundException('The bar was not found');
        }

        foreach ($request->ingredientIds as $ingredientId) {
            $bar->removeIngredientFromStock(new IngredientId($ingredientId));
        }

        $this->barRepository->save($bar);
    }
}
