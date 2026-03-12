<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Color;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use BarAssistant\Application\Ingredient\DTO\CreateIngredient;
use BarAssistant\Application\Ingredient\DTO\IngredientResult;
use BarAssistant\Application\Ingredient\DTO\UpdateIngredient;
use BarAssistant\Domain\Ingredient\IngredientHierarchyManager;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Ingredient\DTO\CreateIngredientPrice;

final readonly class IngredientService
{
    private IngredientHierarchyManager $ingredientHierarchy;

    public function __construct(
        private IngredientRepository $ingredientRepository,
        private PriceCategoryRepository $priceCategoryRepository,
    ) {
        $this->ingredientHierarchy = new IngredientHierarchyManager($ingredientRepository);
    }

    /**
     * Creates a new ingredient based on the provided request data.
     */
    public function createIngredient(CreateIngredient $ingredientRequest): IngredientResult
    {
        $barId = new BarId($ingredientRequest->barId);
        $ingredient = Ingredient::create(
            barId: $barId,
            name: Name::fromString($ingredientRequest->name),
            authors: Authors::createdBy(new UserId($ingredientRequest->userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: $ingredientRequest->description,
            strength: ABV::from($ingredientRequest->strength ?? 0.0),
            origin: $ingredientRequest->origin,
            color: $ingredientRequest->color ? Color::fromHexString($ingredientRequest->color) : null,
            calculatorId: $ingredientRequest->calculatorId ? new CalculatorId($ingredientRequest->calculatorId) : null,
            sugarContent: $ingredientRequest->sugarContent,
            acidity: $ingredientRequest->acidity,
            distillery: $ingredientRequest->distillery,
            units: $ingredientRequest->units ? Unit::from($ingredientRequest->units) : null
        );

        if (count($ingredientRequest->complexIngredientParts) > 0) {
            $ingredient = $this->assignIngredientParts($ingredient, $ingredientRequest->complexIngredientParts);
        }

        if (count($ingredientRequest->prices) > 0) {
            $ingredient = $this->assignIngredientPrices($ingredient, $ingredientRequest->prices);
        }

        if (count($ingredientRequest->images) > 0) {
            $ingredient = $this->assignImages($ingredient, $ingredientRequest->images);
        }

        if ($ingredientRequest->parentIngredientId !== null) {
            $parentIngredient = $this->ingredientRepository->findById(new IngredientId($ingredientRequest->parentIngredientId));
            if ($parentIngredient === null) {
                throw new EntityNotFoundException('Parent ingredient not found');
            }

            $ingredient->setParentIngredientId($parentIngredient);
        }

        $ingredient = $this->ingredientRepository->save($ingredient);

        return IngredientResult::fromIngredient($ingredient);
    }

    public function updateIngredient(UpdateIngredient $ingredientRequest): IngredientResult
    {
        $ingredient = $this->ingredientRepository->findById(new IngredientId($ingredientRequest->ingredientId));
        if ($ingredient === null) {
            throw new EntityNotFoundException('The ingredient to update was not found');
        }

        $ingredient->updateDetails(
            name: Name::fromString($ingredientRequest->name),
            updatedBy: new UserId($ingredientRequest->userId),
            description: $ingredientRequest->description,
            strength: ABV::from($ingredientRequest->strength ?? 0.0),
            origin: $ingredientRequest->origin,
            color: $ingredientRequest->color ? Color::fromHexString($ingredientRequest->color) : null,
            calculatorId: $ingredientRequest->calculatorId ? new CalculatorId($ingredientRequest->calculatorId) : null,
            sugarContent: $ingredientRequest->sugarContent,
            acidity: $ingredientRequest->acidity,
            distillery: $ingredientRequest->distillery,
            units: $ingredientRequest->units ? Unit::from($ingredientRequest->units) : null
        );

        $ingredient->removeAllIngredientParts();
        if (count($ingredientRequest->complexIngredientParts) > 0) {
            $ingredient = $this->assignIngredientParts($ingredient, $ingredientRequest->complexIngredientParts);
        }

        $ingredient->removeAllPrices();
        if (count($ingredientRequest->prices) > 0) {
            $ingredient = $this->assignIngredientPrices($ingredient, $ingredientRequest->prices);
        }

        $ingredient->removeAllImages();
        if (count($ingredientRequest->images) > 0) {
            $ingredient = $this->assignImages($ingredient, $ingredientRequest->images);
        }

        $ingredient = $this->ingredientRepository->save($ingredient);

        if ($ingredientRequest->parentIngredientId !== null) {
            $parentIngredient = $this->ingredientRepository->findById(new IngredientId($ingredientRequest->parentIngredientId));
            if ($parentIngredient === null) {
                throw new EntityNotFoundException('The specified parent ingredient was not found');
            }

            $ingredient = $this->ingredientHierarchy->changeParent($ingredient, $parentIngredient);
        } else {
            $ingredient = $this->ingredientHierarchy->makeRoot($ingredient);
        }

        return IngredientResult::fromIngredient($ingredient);
    }

    public function deleteIngredient(int $ingredientId): void
    {
        $id = new IngredientId($ingredientId);
        $ingredient = $this->ingredientRepository->findById($id);

        if ($ingredient === null) {
            throw new EntityNotFoundException('The ingredient to delete was not found');
        }

        // Update children to be root ingredients
        $children = $this->ingredientRepository->findChildren($id);
        if (!empty($children)) {
            foreach ($children as $child) {
                $this->ingredientHierarchy->makeRoot($child);
            }
        }

        $this->ingredientRepository->delete($id);
    }

    /**
     * Find and assign ingredient parts to a complex ingredient.
     *
     * @param non-empty-array<int> $ingredientPartIds
     * @throws EntityNotFoundException if any ingredient part is not found
     */
    private function assignIngredientParts(Ingredient $ingredient, array $ingredientPartIds): Ingredient
    {
        $ingredientIdVOs = array_map(
            static fn (int $id) => new IngredientId($id),
            $ingredientPartIds
        );

        $ingredientPartCandidates = $this->ingredientRepository->findMany(
            $ingredient->getBarId(),
            $ingredientIdVOs
        );

        // Validate all requested parts were found
        if (count($ingredientPartCandidates) !== count($ingredientPartIds)) {
            throw new EntityNotFoundException('One or more ingredient parts not found');
        }

        foreach ($ingredientPartCandidates as $part) {
            $ingredient->addIngredientPart($part);
        }

        return $ingredient;
    }

    /**
     * Find and assign ingredient prices to an ingredient.
     *
     * @param non-empty-array<CreateIngredientPrice> $prices
     */
    private function assignIngredientPrices(Ingredient $ingredient, array $prices): Ingredient
    {
        $priceCategories = $this->priceCategoryRepository->findMany($ingredient->getBarId(), array_map(
            static fn (object $priceData) => new PriceCategoryId($priceData->priceCategoryId),
            $prices
        ));

        /** @var array<int, PriceCategory> */
        $priceCategoriesById = [];
        foreach ($priceCategories as $priceCategory) {
            if ($priceCategory->isTransient()) {
                continue;
            }

            $priceCategoriesById[$priceCategory->getId()->value] = $priceCategory;
        }

        foreach ($prices as $priceData) {
            $priceCategory = $priceCategoriesById[$priceData->priceCategoryId] ?? null;
            if ($priceCategory === null || $priceCategory->isTransient()) {
                continue;
            }

            $ingredient->addPrice(
                priceCategoryId: $priceCategory->getId(),
                price: $priceData->price,
                currency: $priceCategory->getCurrency()->getCurrencyCode(),
                amount: $priceData->amount,
                units: $priceData->units,
                description: $priceData->description,
            );
        }

        return $ingredient;
    }

    /**
     * Assign images to an ingredient.
     *
     * @param non-empty-array<int> $imageIds
     */
    private function assignImages(Ingredient $ingredient, array $imageIds): Ingredient
    {
        foreach ($imageIds as $imageId) {
            $ingredient->addImage(new ImageId($imageId));
        }

        return $ingredient;
    }
}
