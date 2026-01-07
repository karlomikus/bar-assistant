<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient;

use BarAssistant\Application\Exception\ApplicationServiceException;
use BarAssistant\Application\Ingredient\DTO\CreatePriceCategory;
use BarAssistant\Application\Ingredient\DTO\PriceCategoryResult;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use Brick\Money\Currency;

final readonly class PriceCategoryService
{
    public function __construct(
        private PriceCategoryRepository $priceCategoryRepository,
    ) {
    }

    /**
     * Creates a new price category based on the provided request data.
     */
    public function createPriceCategory(CreatePriceCategory $request): PriceCategoryResult
    {
        $priceCategory = new PriceCategory(
            barId: new BarId($request->barId),
            name: $request->name,
            currency: Currency::of($request->currency),
            description: $request->description,
        );

        $priceCategory = $this->priceCategoryRepository->save($priceCategory);

        if ($priceCategory->isTransient()) {
            throw new ApplicationServiceException('Failed to create price category');
        }

        return PriceCategoryResult::fromPriceCategory($priceCategory);
    }
}
