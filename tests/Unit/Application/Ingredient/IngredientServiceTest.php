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
use BarAssistant\Domain\Ingredient\MaterializedPath;
use Tests\Infrastructure\InMemoryIngredientRepository;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use Tests\Infrastructure\InMemoryPriceCategoryRepository;
use BarAssistant\Application\Ingredient\IngredientService;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use BarAssistant\Application\Ingredient\DTO\CreateIngredient;
use BarAssistant\Application\Ingredient\DTO\IngredientResult;
use BarAssistant\Application\Exception\EntityNotFoundException;
use Tests\Infrastructure\InMemoryIngredientHierarchyRepository;
use BarAssistant\Application\Ingredient\DTO\ComplexIngredientPart;
use BarAssistant\Application\Ingredient\DTO\CreateIngredientPrice;
use BarAssistant\Application\Ingredient\DTO\UpdateIngredientRequest;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyNode;
use BarAssistant\Domain\IngredientHierarchy\IngredientHierarchyRepository;

final class IngredientServiceTest extends TestCase
{
    private IngredientRepository $ingredientRepository;
    private IngredientHierarchyRepository $ingredientHierarchyRepository;
    private PriceCategoryRepository $priceCategoryRepository;

    protected function setUp(): void
    {
        $this->ingredientRepository = new InMemoryIngredientRepository([
            542 => (Ingredient::create(barId: new BarId(65), name: Name::fromString('Existing ingredient 65-1'), recordTimestamps: RecordTimestamps::createdNow(), authors: Authors::createdBy(new UserId(45))))->setId(new IngredientId(542)),
            543 => (Ingredient::create(barId: new BarId(65), name: Name::fromString('Existing ingredient 65-2'), recordTimestamps: RecordTimestamps::createdNow(), authors: Authors::createdBy(new UserId(45))))->setId(new IngredientId(543)),
            544 => (Ingredient::create(barId: new BarId(55), name: Name::fromString('Existing ingredient 55-1'), recordTimestamps: RecordTimestamps::createdNow(), authors: Authors::createdBy(new UserId(32))))->setId(new IngredientId(544)),
            545 => (Ingredient::create(barId: new BarId(55), name: Name::fromString('Existing ingredient 55-2'), recordTimestamps: RecordTimestamps::createdNow(), authors: Authors::createdBy(new UserId(33))))->setId(new IngredientId(545)),
        ]);

        $this->ingredientHierarchyRepository = new InMemoryIngredientHierarchyRepository([
            542 => IngredientHierarchyNode::fromPersistence(
                barId: new BarId(65),
                id: new IngredientId(542),
                parentId: null,
                materializedPath: MaterializedPath::root(),
            ),
            543 => IngredientHierarchyNode::fromPersistence(
                barId: new BarId(65),
                id: new IngredientId(543),
                parentId: null,
                materializedPath: MaterializedPath::root(),
            ),
            544 => IngredientHierarchyNode::fromPersistence(
                barId: new BarId(55),
                id: new IngredientId(544),
                parentId: null,
                materializedPath: MaterializedPath::root(),
            ),
            545 => IngredientHierarchyNode::fromPersistence(
                barId: new BarId(55),
                id: new IngredientId(545),
                parentId: null,
                materializedPath: MaterializedPath::root(),
            ),
        ]);

        $this->priceCategoryRepository = new InMemoryPriceCategoryRepository([
            301 => (new PriceCategory(barId: new BarId(65), name: Name::fromString('Amazon EU'), currency: Currency::of('EUR')))->setId(new PriceCategoryId(301)),
        ]);
    }

    public function test_creates_ingredient(): void
    {
        $service = new IngredientService($this->ingredientRepository, $this->ingredientHierarchyRepository, $this->priceCategoryRepository);
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
            complexIngredientParts: [
                new ComplexIngredientPart(ingredientId: 542, amount: 200.0, units: 'ml'),
                new ComplexIngredientPart(ingredientId: 543, amount: 100.0, units: 'g'),
            ],
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
        $service = new IngredientService($this->ingredientRepository, $this->ingredientHierarchyRepository, $this->priceCategoryRepository);
        $createRequest = new CreateIngredient(
            barId: 65,
            name: 'Gin',
            userId: 22,
            parentIngredientId: 543,
        );

        $result = $service->createIngredient($createRequest);

        $this->assertNotNull($result->id);

        $createdIngredient = $this->ingredientRepository->findById(new IngredientId($result->id));

        $this->assertNotNull($createdIngredient);
        $this->assertSame(543, $createdIngredient->getParentIngredientId()?->value);
        $this->assertSame('543/', $createdIngredient->getMaterializedPath()->toString());
    }

    public function test_cannot_find_parent_ingredient_on_create(): void
    {
        $service = new IngredientService($this->ingredientRepository, $this->ingredientHierarchyRepository, $this->priceCategoryRepository);
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
        $service = new IngredientService($this->ingredientRepository, $this->ingredientHierarchyRepository, $this->priceCategoryRepository);
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

    public function test_updates_ingredient_parent_through_hierarchy_repository(): void
    {
        $service = new IngredientService($this->ingredientRepository, $this->ingredientHierarchyRepository, $this->priceCategoryRepository);
        $updateRequest = new UpdateIngredientRequest(
            ingredientId: 542,
            name: 'Gin',
            userId: 22,
            parentIngredientId: 543,
        );

        $result = $service->updateIngredient($updateRequest);

        $this->assertSame(542, $result->id);

        $updatedNode = $this->ingredientHierarchyRepository->findById(new IngredientId(542));

        $this->assertNotNull($updatedNode);
        $this->assertSame(543, $updatedNode->getParentId()?->value);
        $this->assertSame('543/', $updatedNode->getMaterializedPath()->toString());
    }

    public function test_cannot_update_non_existing_ingredient(): void
    {
        $service = new IngredientService($this->ingredientRepository, $this->ingredientHierarchyRepository, $this->priceCategoryRepository);
        $updateRequest = new UpdateIngredientRequest(
            ingredientId: 999,
            name: 'Gin',
            userId: 22,
        );

        $this->expectException(EntityNotFoundException::class);
        $service->updateIngredient($updateRequest);
    }
}
