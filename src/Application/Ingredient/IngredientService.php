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
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\Ingredient\ComplexIngredientPart;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use BarAssistant\Application\Ingredient\DTO\CreateIngredient;
use BarAssistant\Application\Ingredient\DTO\IngredientResult;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Ingredient\DTO\CreateIngredientPrice;
use BarAssistant\Application\Ingredient\DTO\UpdateIngredientRequest;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyNode;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyManager;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyRepository;

final readonly class IngredientService
{
    public function __construct(
        private IngredientRepository $ingredientRepository,
        private IngredientHierarchyRepository $ingredientHierarchyRepository,
        private PriceCategoryRepository $priceCategoryRepository,
    ) {
    }

    /**
     * Creates a new ingredient based on the provided request data.
     */
    public function createIngredient(CreateIngredient $ingredientRequest): IngredientResult
    {
        $barId = new BarId($ingredientRequest->barId);
        $hierarchyNode = IngredientHierarchyNode::createRoot($barId);

        if ($ingredientRequest->parentIngredientId !== null) {
            $parentIngredient = $this->ingredientRepository->findById(new IngredientId($ingredientRequest->parentIngredientId));
            if ($parentIngredient === null) {
                throw new EntityNotFoundException('Parent ingredient not found');
            }

            $hierarchyNode = IngredientHierarchyNode::createChild(
                barId: $barId,
                parent: $this->mapHierarchyNode($parentIngredient),
            );
        }

        $ingredient = Ingredient::create(
            barId: $barId,
            name: Name::fromString($ingredientRequest->name),
            authors: Authors::createdBy(new UserId($ingredientRequest->userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: $ingredientRequest->description,
            strength: ABV::from($ingredientRequest->strength),
            origin: $ingredientRequest->origin,
            color: $ingredientRequest->color ? Color::fromHexString($ingredientRequest->color) : null,
            calculatorId: $ingredientRequest->calculatorId ? new CalculatorId($ingredientRequest->calculatorId) : null,
            sugarContent: $ingredientRequest->sugarContent,
            acidity: $ingredientRequest->acidity,
            distillery: $ingredientRequest->distillery,
            units: $ingredientRequest->units ? Unit::from($ingredientRequest->units) : null,
            parentIngredientId: $hierarchyNode->getParentId(),
            materializedPath: $hierarchyNode->getMaterializedPath(),
        );

        if (count($ingredientRequest->complexIngredientParts) > 0) {
            $ingredient = $this->assignIngredientParts($ingredient, $ingredientRequest->complexIngredientParts, $barId);
        }

        if (count($ingredientRequest->prices) > 0) {
            $ingredient = $this->assignIngredientPrices($ingredient, $ingredientRequest->prices);
        }

        if (count($ingredientRequest->images) > 0) {
            $ingredient = $this->assignImages($ingredient, $ingredientRequest->images);
        }

        $ingredient = $this->ingredientRepository->save($ingredient);

        return IngredientResult::fromIngredient($ingredient);
    }

    public function updateIngredient(UpdateIngredientRequest $ingredientRequest): IngredientResult
    {
        $ingredient = $this->ingredientRepository->findById(new IngredientId($ingredientRequest->ingredientId));
        if ($ingredient === null) {
            throw new EntityNotFoundException('The ingredient to update was not found');
        }

        $ingredient->updateDetails(
            name: Name::fromString($ingredientRequest->name),
            updatedBy: new UserId($ingredientRequest->userId),
            description: $ingredientRequest->description,
            strength: ABV::from($ingredientRequest->strength),
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
            $ingredient = $this->assignIngredientParts($ingredient, $ingredientRequest->complexIngredientParts, $ingredient->getBarId());
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

            $this->hierarchyManager()->changeParent(
                node: $this->mapHierarchyNode($ingredient),
                newParent: $this->mapHierarchyNode($parentIngredient),
            );
        } else {
            $this->hierarchyManager()->makeRoot($this->mapHierarchyNode($ingredient));
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
                $this->hierarchyManager()->makeRoot($this->mapHierarchyNode($child));
            }
        }

        $this->ingredientRepository->delete($id);
    }

    /**
     * Find and assign ingredient parts to a complex ingredient.
     *
     * @param non-empty-array<DTO\ComplexIngredientPart> $ingredientPartRequests
     * @throws EntityNotFoundException if any ingredient part is not found
     */
    private function assignIngredientParts(Ingredient $ingredient, array $ingredientPartRequests, BarId $barId): Ingredient
    {
        $ingredientIdVOs = array_map(
            static fn (DTO\ComplexIngredientPart $part) => new IngredientId($part->ingredientId),
            $ingredientPartRequests
        );

        $ingredientPartCandidates = $this->ingredientRepository->findMany(
            $barId,
            $ingredientIdVOs
        );

        // Validate all requested parts were found
        if (count($ingredientPartCandidates) !== count($ingredientPartRequests)) {
            throw new EntityNotFoundException('One or more ingredient parts not found');
        }

        // Index by ID for quick lookup
        $candidateById = [];
        foreach ($ingredientPartCandidates as $candidate) {
            if ($candidate->isTransient()) {
                continue;
            }
            $candidateById[$candidate->getId()->value] = $candidate;
        }

        // Validate all parts belong to the same bar
        foreach ($ingredientPartCandidates as $candidate) {
            if (!$candidate->getBarId()->equals($barId)) {
                throw new EntityNotFoundException('Ingredient parts must belong to the same bar');
            }
        }

        foreach ($ingredientPartRequests as $request) {
            $ingredientId = new IngredientId($request->ingredientId);

            if (!$ingredient->isTransient() && $ingredient->getId()->equals($ingredientId)) {
                continue; // Skip self-reference
            }

            $amountWithUnits = AmountWithUnits::from($request->amount, Unit::from($request->units), $request->amountMax);

            $ingredient->addIngredientPart(ComplexIngredientPart::create(
                ingredientId: $ingredientId,
                amountWithUnits: $amountWithUnits,
                note: $request->note,
            ));
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

    private function hierarchyManager(): IngredientHierarchyManager
    {
        return new IngredientHierarchyManager($this->ingredientHierarchyRepository);
    }

    private function mapHierarchyNode(Ingredient $ingredient): IngredientHierarchyNode
    {
        $ingredientId = $ingredient->getId();
        assert($ingredientId !== null);

        return IngredientHierarchyNode::fromPersistence(
            barId: $ingredient->getBarId(),
            id: $ingredientId,
            parentId: $ingredient->getParentIngredientId(),
            materializedPath: $ingredient->getMaterializedPath(),
        );
    }
}
