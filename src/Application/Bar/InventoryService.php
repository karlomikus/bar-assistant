<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Bar\BarInventoryRepository;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Bar\DTO\BarInventoryStockChangeRequest;

final readonly class InventoryService
{
    public function __construct(
        private BarInventoryRepository $barInventoryRepository,
    ) {
    }

    /**
     * Put multiple ingredients in stock for a bar
     */
    public function putMultipleIngredientsInStock(BarInventoryStockChangeRequest $request): void
    {
        $barInventory = $this->barInventoryRepository->findByBarId(new BarId($request->barId));
        if ($barInventory === null) {
            throw new EntityNotFoundException('The bar was not found');
        }

        foreach ($request->ingredientIds as $ingredientId) {
            $barInventory->putIngredient(new IngredientId($ingredientId), IngredientInventoryStatus::InStock);
        }

        $this->barInventoryRepository->save($barInventory);
    }

    public function removeMultipleIngredientsFromStock(BarInventoryStockChangeRequest $request): void
    {
        $barInventory = $this->barInventoryRepository->findByBarId(new BarId($request->barId));
        if ($barInventory === null) {
            throw new EntityNotFoundException('The bar was not found');
        }

        foreach ($request->ingredientIds as $ingredientId) {
            $barInventory->removeIngredient(new IngredientId($ingredientId));
        }

        $this->barInventoryRepository->save($barInventory);
    }
}
