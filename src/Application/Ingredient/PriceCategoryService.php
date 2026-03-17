<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient;

use Brick\Money\Currency;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Ingredient\DTO\PriceCategoryResult;
use BarAssistant\Application\Exception\ApplicationServiceException;
use BarAssistant\Application\Ingredient\DTO\CreatePriceCategoryRequest;
use BarAssistant\Application\Ingredient\DTO\UpdatePriceCategoryRequest;

final readonly class PriceCategoryService
{
    public function __construct(
        private PriceCategoryRepository $priceCategoryRepository,
    ) {
    }

    /**
     * Creates a new price category based on the provided request data.
     */
    public function createPriceCategory(CreatePriceCategoryRequest $request): PriceCategoryResult
    {
        $priceCategory = new PriceCategory(
            barId: new BarId($request->barId),
            name: Name::fromString($request->name),
            currency: Currency::of($request->currency),
            description: $request->description,
        );

        $priceCategory = $this->priceCategoryRepository->save($priceCategory);

        if ($priceCategory->isTransient()) {
            throw new ApplicationServiceException('Failed to create price category');
        }

        return PriceCategoryResult::fromPriceCategory($priceCategory);
    }

    public function updatePriceCategory(UpdatePriceCategoryRequest $request): PriceCategoryResult
    {
        $priceCategory = $this->priceCategoryRepository->findById(new PriceCategoryId($request->priceCategoryId));
        if ($priceCategory === null) {
            throw new EntityNotFoundException('Price category not found');
        }

        $priceCategory
            ->updateDetails(
                name: Name::fromString($request->name),
                description: $request->description,
            )
            ->changeCurrency(Currency::of($request->currency));

        $priceCategory = $this->priceCategoryRepository->save($priceCategory);

        return PriceCategoryResult::fromPriceCategory($priceCategory);
    }
}
