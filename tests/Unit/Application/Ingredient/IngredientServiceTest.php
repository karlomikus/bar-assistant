<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Ingredient;

use Brick\Money\Currency;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use Tests\Infrastructure\InMemoryIngredientRepository;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use Tests\Infrastructure\InMemoryPriceCategoryRepository;
use BarAssistant\Application\Ingredient\IngredientService;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use BarAssistant\Application\Ingredient\DTO\CreateIngredient;
use BarAssistant\Application\Ingredient\DTO\IngredientResult;
use BarAssistant\Application\Ingredient\DTO\UpdateIngredientRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Ingredient\DTO\CreateIngredientPrice;

final class IngredientServiceTest extends TestCase
{
    private IngredientRepository $ingredientRepository;
    private PriceCategoryRepository $priceCategoryRepository;

    protected function setUp(): void
    {
        $this->ingredientRepository = new InMemoryIngredientRepository([
            542 => (Ingredient::create(barId: new BarId(65), name: Name::fromString('Existing ingredient 65-1'), recordTimestamps: RecordTimestamps::createdNow(), authors: Authors::createdBy(new UserId(45))))->setId(new IngredientId(542)),
            543 => (Ingredient::create(barId: new BarId(65), name: Name::fromString('Existing ingredient 65-2'), recordTimestamps: RecordTimestamps::createdNow(), authors: Authors::createdBy(new UserId(45))))->setId(new IngredientId(543)),
            544 => (Ingredient::create(barId: new BarId(55), name: Name::fromString('Existing ingredient 55-1'), recordTimestamps: RecordTimestamps::createdNow(), authors: Authors::createdBy(new UserId(32))))->setId(new IngredientId(544)),
            545 => (Ingredient::create(barId: new BarId(55), name: Name::fromString('Existing ingredient 55-2'), recordTimestamps: RecordTimestamps::createdNow(), authors: Authors::createdBy(new UserId(33))))->setId(new IngredientId(545)),
        ]);

        $this->priceCategoryRepository = new InMemoryPriceCategoryRepository([
            301 => (new PriceCategory(barId: new BarId(65), name: Name::fromString('Amazon EU'), currency: Currency::of('EUR')))->setId(new PriceCategoryId(301)),
        ]);
    }

    public function test_creates_ingredient(): void
    {
        $service = new IngredientService($this->ingredientRepository, $this->priceCategoryRepository);
        $createPriceRequest = new CreateIngredientPrice(
            priceCategoryId: 301,
            price: 312300,
            amount: 750.0,
            units: 'ml',
            description: 'A bottle',
        );
        $createRequest = new CreateIngredient(
            barId: 65,
            name: 'Gin',
            userId: 22,
            strength: 40.0,
            description: 'Lorem ipsum',
            origin: 'United Kingdom',
            color: '#333222',
            complexIngredientParts: [542, 543],
            prices: [$createPriceRequest]
        );

        $result = $service->createIngredient($createRequest);

        $this->assertInstanceOf(IngredientResult::class, $result);
        $this->assertNotNull($result->id);
        $this->assertNull($result->updatedAt);
        $this->assertNull($result->updatedBy);
    }

    public function test_creates_variant_ingredient(): void
    {
        $service = new IngredientService($this->ingredientRepository, $this->priceCategoryRepository);
        $createRequest = new CreateIngredient(
            barId: 65,
            name: 'Gin',
            userId: 22,
            parentIngredientId: 543,
        );

        $result = $service->createIngredient($createRequest);

        $this->assertNotNull($result->id);
    }

    public function test_cannot_find_parent_ingredient_on_create(): void
    {
        $service = new IngredientService($this->ingredientRepository, $this->priceCategoryRepository);
        $createRequest = new CreateIngredient(
            barId: 65,
            name: 'Gin',
            userId: 22,
            parentIngredientId: 400,
        );

        $this->expectException(EntityNotFoundException::class);
        $service->createIngredient($createRequest);
    }

    public function test_updates_ingredient(): void
    {
        $service = new IngredientService($this->ingredientRepository, $this->priceCategoryRepository);
        $createPriceRequest = new CreateIngredientPrice(
            priceCategoryId: 301,
            price: 312300,
            amount: 750.0,
            units: 'ml',
            description: 'A bottle',
        );
        $updateRequest = new UpdateIngredientRequest(
            ingredientId: 542,
            name: 'Gin',
            userId: 22,
            strength: 40.0,
            description: 'Lorem ipsum',
            origin: 'United Kingdom',
            color: '#333222',
            prices: [$createPriceRequest]
        );

        $result = $service->updateIngredient($updateRequest);

        $this->assertSame(542, $result->id);
    }

    public function test_cannot_update_non_existing_ingredient(): void
    {
        $service = new IngredientService($this->ingredientRepository, $this->priceCategoryRepository);
        $updateRequest = new UpdateIngredientRequest(
            ingredientId: 999,
            name: 'Gin',
            userId: 22,
        );

        $this->expectException(EntityNotFoundException::class);
        $service->updateIngredient($updateRequest);
    }
}
