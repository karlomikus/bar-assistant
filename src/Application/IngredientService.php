<?php

declare(strict_types=1);

namespace BarAssistant\Application;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Support\Color;
use BarAssistant\Application\DTO\CreateIngredientRequest;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\IngredientPrice;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;

final readonly class IngredientService
{
    public function __construct(private IngredientRepository $ingredientRepository, private PriceCategoryRepository $priceCategoryRepository)
    {
    }

    public function createIngredient(CreateIngredientRequest $ingredientRequest)
    {
        $barId = new BarId($ingredientRequest->barId);
        $ingredient = new Ingredient(
            barId: $barId,
            name: $ingredientRequest->name,
            description: $ingredientRequest->description,
            strength: $ingredientRequest->strength,
            origin: $ingredientRequest->origin,
            color: $ingredientRequest->color ? Color::fromHexString($ingredientRequest->color) : null,
        );

        if (count($ingredientRequest->complexIngredientParts) > 0) {
            $ingredientPartCandidates = $this->ingredientRepository->findMany($barId, array_map(
                fn (int $id) => new IngredientId($id),
                $ingredientRequest->complexIngredientParts
            ));
            foreach ($ingredientPartCandidates as $part) {
                $ingredient->addIngredientPart($part);
            }
        }

        if (count($ingredientRequest->prices) > 0) {
            $priceCategories = $this->priceCategoryRepository->findMany($barId, array_map(
                fn (object $priceData) => new PriceCategoryId($priceData->priceCategoryId),
                $ingredientRequest->prices
            ));

            /** @var array<int, PriceCategory> */
            $priceCategoriesById = [];
            foreach ($priceCategories as $priceCategory) {
                $priceCategoriesById[$priceCategory->getId()->id] = $priceCategory;
            }

            foreach ($ingredientRequest->prices as $priceData) {
                $priceCategory = $priceCategoriesById[$priceData->priceCategoryId] ?? null;
                if ($priceCategory === null) {
                    continue;
                }

                $ingredient->addPrice(
                    IngredientPrice::create(
                        priceCategoryId: $priceCategory->getId(),
                        price: $priceData->price,
                        currency: $priceCategory->getCurrency()->getCurrencyCode(),
                        amount: $priceData->amount,
                        units: $priceData->units,
                        description: $priceData->description,
                    )
                );
            }
        }

        $ingredient = $this->ingredientRepository->save($ingredient);

        return $ingredient;
    }
}
