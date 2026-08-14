<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Ingredient;

use Brick\Money\Currency;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use Tests\Infrastructure\InMemoryPriceCategoryRepository;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use BarAssistant\Application\Ingredient\PriceCategoryService;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Ingredient\DTO\PriceCategoryResult;
use BarAssistant\Application\Ingredient\DTO\CreatePriceCategoryRequest;
use BarAssistant\Application\Ingredient\DTO\UpdatePriceCategoryRequest;

final class PriceCategoryServiceTest extends TestCase
{
    private PriceCategoryRepository $priceCategoryRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->priceCategoryRepository = new InMemoryPriceCategoryRepository([
            101 => (new PriceCategory(
                barId: new BarId(10),
                name: Name::fromString('Amazon EU'),
                currency: Currency::of('EUR'),
                description: 'European online prices',
            ))->setId(new PriceCategoryId(101)),
            102 => (new PriceCategory(
                barId: new BarId(10),
                name: Name::fromString('Local store'),
                currency: Currency::of('USD'),
            ))->setId(new PriceCategoryId(102)),
        ]);
    }

    public function test_creates_price_category(): void
    {
        $service = new PriceCategoryService($this->priceCategoryRepository);
        $request = new CreatePriceCategoryRequest(
            barId: 10,
            name: 'Wholesale',
            currency: 'GBP',
            description: 'Bulk purchase prices',
        );

        $result = $service->createPriceCategory($request);

        $this->assertInstanceOf(PriceCategoryResult::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertSame(10, $result->barId);
        $this->assertSame('Wholesale', $result->name);
        $this->assertSame('GBP', $result->currency);
        $this->assertSame('Bulk purchase prices', $result->description);
    }

    public function test_creates_price_category_without_description(): void
    {
        $service = new PriceCategoryService($this->priceCategoryRepository);
        $request = new CreatePriceCategoryRequest(
            barId: 10,
            name: 'Travel retail',
            currency: 'USD',
        );

        $result = $service->createPriceCategory($request);

        $this->assertInstanceOf(PriceCategoryResult::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertSame('Travel retail', $result->name);
        $this->assertSame('USD', $result->currency);
        $this->assertNull($result->description);
    }

    public function test_creates_multiple_price_categories_with_distinct_ids(): void
    {
        $service = new PriceCategoryService($this->priceCategoryRepository);

        $firstResult = $service->createPriceCategory(new CreatePriceCategoryRequest(
            barId: 10,
            name: 'Online',
            currency: 'EUR',
        ));

        $secondResult = $service->createPriceCategory(new CreatePriceCategoryRequest(
            barId: 10,
            name: 'Duty free',
            currency: 'USD',
        ));

        $this->assertNotSame($firstResult->id, $secondResult->id);
    }

    public function test_updates_price_category(): void
    {
        $service = new PriceCategoryService($this->priceCategoryRepository);
        $request = new UpdatePriceCategoryRequest(
            priceCategoryId: 101,
            name: 'Amazon Global',
            currency: 'JPY',
            description: 'International online prices',
        );

        $result = $service->updatePriceCategory($request);

        $this->assertInstanceOf(PriceCategoryResult::class, $result);
        $this->assertSame(101, $result->id);
        $this->assertSame(10, $result->barId);
        $this->assertSame('Amazon Global', $result->name);
        $this->assertSame('JPY', $result->currency);
        $this->assertSame('International online prices', $result->description);
    }

    public function test_updates_price_category_clears_description(): void
    {
        $service = new PriceCategoryService($this->priceCategoryRepository);
        $request = new UpdatePriceCategoryRequest(
            priceCategoryId: 101,
            name: 'Amazon EU',
            currency: 'EUR',
        );

        $result = $service->updatePriceCategory($request);

        $this->assertSame(101, $result->id);
        $this->assertNull($result->description);
    }

    public function test_cannot_update_non_existing_price_category(): void
    {
        $service = new PriceCategoryService($this->priceCategoryRepository);
        $request = new UpdatePriceCategoryRequest(
            priceCategoryId: 999,
            name: 'Unknown',
            currency: 'EUR',
        );

        $this->expectException(EntityNotFoundException::class);
        $service->updatePriceCategory($request);
    }
}
