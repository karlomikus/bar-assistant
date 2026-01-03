<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Support\Color;
use BarAssistant\Application\Ingredient\DTO\CreateIngredientDTO;
use BarAssistant\Application\Ingredient\DTO\IngredientPriceRequest;
use BarAssistant\Application\Ingredient\DTO\IngredientResult;
use BarAssistant\Application\Ingredient\DTO\UpdateIngredientDTO;
use BarAssistant\Application\Exception\ApplicationServiceException;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientHierarchyManager;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\IngredientPrice;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use BarAssistant\Domain\Support\Unit;
use BarAssistant\Domain\User\UserId;

final readonly class IngredientService
{
    private readonly IngredientHierarchyManager $ingredientHierarchy;

    public function __construct(
        private IngredientRepository $ingredientRepository,
        private PriceCategoryRepository $priceCategoryRepository,
    ) {
        $this->ingredientHierarchy = new IngredientHierarchyManager($ingredientRepository);
    }

    /**
     * Creates a new ingredient based on the provided request data.
     */
    public function createIngredient(CreateIngredientDTO $ingredientRequest): IngredientResult
    {
        $barId = new BarId($ingredientRequest->barId);
        $ingredient = new Ingredient(
            barId: $barId,
            name: $ingredientRequest->name,
            description: $ingredientRequest->description,
            strength: $ingredientRequest->strength,
            origin: $ingredientRequest->origin,
            color: $ingredientRequest->color ? Color::fromHexString($ingredientRequest->color) : null,
            calculatorId: $ingredientRequest->calculatorId ? new CalculatorId($ingredientRequest->calculatorId) : null,
            sugarContent: $ingredientRequest->sugarContent,
            acidity: $ingredientRequest->acidity,
            distillery: $ingredientRequest->distillery,
            units: $ingredientRequest->units ? new Unit($ingredientRequest->units) : null
        );

        $ingredient->wasCreatedBy(new UserId($ingredientRequest->userId));

        if (count($ingredientRequest->complexIngredientParts) > 0) {
            $ingredient = $this->assignIngredientParts($ingredient, $ingredientRequest->complexIngredientParts);
        }

        if (count($ingredientRequest->prices) > 0) {
            $ingredient = $this->assignIngredientPrices($ingredient, $ingredientRequest->prices);
        }

        $ingredient = $this->ingredientRepository->save($ingredient);

        if ($ingredientRequest->parentIngredientId !== null) {
            $parentIngredient = $this->ingredientRepository->findById(new IngredientId($ingredientRequest->parentIngredientId));
            if ($parentIngredient === null) {
                throw new ApplicationServiceException('The specified parent ingredient was not found');
            }

            $ingredient = $this->ingredientHierarchy->changeParent($ingredient, $parentIngredient);
        }

        return IngredientResult::fromIngredient($ingredient);
    }

    public function updateIngredient(UpdateIngredientDTO $ingredientRequest): IngredientResult
    {
        $ingredient = $this->ingredientRepository->findById(new IngredientId($ingredientRequest->ingredientId));
        if ($ingredient === null) {
            throw new ApplicationServiceException('The ingredient to update was not found');
        }

        $ingredient->updateDetails(
            name: $ingredientRequest->name,
            description: $ingredientRequest->description,
            strength: $ingredientRequest->strength,
            origin: $ingredientRequest->origin,
            color: $ingredientRequest->color ? Color::fromHexString($ingredientRequest->color) : null,
            calculatorId: $ingredientRequest->calculatorId ? new CalculatorId($ingredientRequest->calculatorId) : null,
            sugarContent: $ingredientRequest->sugarContent,
            acidity: $ingredientRequest->acidity,
            distillery: $ingredientRequest->distillery,
            units: $ingredientRequest->units ? new Unit($ingredientRequest->units) : null
        );

        $ingredient->wasUpdatedBy(new UserId($ingredientRequest->userId));

        $ingredient->removeAllIngredientParts();
        if (count($ingredientRequest->complexIngredientParts) > 0) {
            $ingredient = $this->assignIngredientParts($ingredient, $ingredientRequest->complexIngredientParts);
        }

        $ingredient->removeAllPrices();
        if (count($ingredientRequest->prices) > 0) {
            $ingredient = $this->assignIngredientPrices($ingredient, $ingredientRequest->prices);
        }

        $ingredient = $this->ingredientRepository->save($ingredient);

        if ($ingredientRequest->parentIngredientId !== null) {
            $parentIngredient = $this->ingredientRepository->findById(new IngredientId($ingredientRequest->parentIngredientId));
            if ($parentIngredient === null) {
                throw new ApplicationServiceException('The specified parent ingredient was not found');
            }

            $ingredient = $this->ingredientHierarchy->changeParent($ingredient, $parentIngredient);
        } else {
            $ingredient = $this->ingredientHierarchy->makeRoot($ingredient);
        }

        return IngredientResult::fromIngredient($ingredient);
    }

    public function deleteIngredient(int $ingredientId): void
    {
        // $this->ingredientRepository->deleteById(new IngredientId($ingredientId));
    }

    /**
     * Find and assign ingredient parts to a complex ingredient.
     *
     * @param non-empty-array<int> $ingredientPartIds
     */
    private function assignIngredientParts(Ingredient $ingredient, array $ingredientPartIds): Ingredient
    {
        $ingredientPartCandidates = $this->ingredientRepository->findMany($ingredient->getBarId(), array_map(
            fn (int $id) => new IngredientId($id),
            $ingredientPartIds
        ));

        foreach ($ingredientPartCandidates as $part) {
            $ingredient->addIngredientPart($part);
        }

        return $ingredient;
    }

    /**
     * Find and assign ingredient prices to an ingredient.
     *
     * @param non-empty-array<IngredientPriceRequest> $prices
     */
    private function assignIngredientPrices(Ingredient $ingredient, array $prices): Ingredient
    {
        $priceCategories = $this->priceCategoryRepository->findMany($ingredient->getBarId(), array_map(
            fn (object $priceData) => new PriceCategoryId($priceData->priceCategoryId),
            $prices
        ));

        /** @var array<int, PriceCategory> */
        $priceCategoriesById = [];
        foreach ($priceCategories as $priceCategory) {
            if ($priceCategory->isTransient()) {
                continue;
            }

            $priceCategoriesById[$priceCategory->getId()->id] = $priceCategory;
        }

        foreach ($prices as $priceData) {
            $priceCategory = $priceCategoriesById[$priceData->priceCategoryId] ?? null;
            if ($priceCategory === null || $priceCategory->isTransient()) {
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

        return $ingredient;
    }
}
