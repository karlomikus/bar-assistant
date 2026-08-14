<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use Brick\Money\Currency;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Ingredient\PriceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use Kami\Cocktail\Models\PriceCategory as ModelPriceCategory;
use Kami\Cocktail\Infrastructure\EloquentPriceCategoryRepository;

final class EloquentPriceCategoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_price_category(): void
    {
        $membership = $this->setupBarMembership();

        $priceCategory = new PriceCategory(
            barId: new BarId($membership->bar_id),
            name: Name::fromString('Local shop'),
            currency: Currency::of('EUR'),
            description: 'Current prices from the local store',
        );

        $repository = new EloquentPriceCategoryRepository();
        $priceCategory = $repository->save($priceCategory);

        $this->assertDatabaseCount('price_categories', 1);
        $this->assertDatabaseHas('price_categories', [
            'id' => $priceCategory->getId()?->value,
            'bar_id' => $membership->bar_id,
            'name' => 'Local shop',
            'currency' => 'EUR',
            'description' => 'Current prices from the local store',
        ]);
    }

    public function test_it_updates_price_category(): void
    {
        $membership = $this->setupBarMembership();
        $model = ModelPriceCategory::factory()->recycle($membership->bar)->create([
            'name' => 'Original name',
            'currency' => 'USD',
            'description' => 'Original description',
        ]);

        $priceCategory = new PriceCategory(
            barId: new BarId($membership->bar_id),
            name: Name::fromString('Updated name'),
            currency: Currency::of('JPY'),
            description: 'Updated description',
        );
        $priceCategory->setId(new PriceCategoryId($model->id));

        $repository = new EloquentPriceCategoryRepository();
        $repository->save($priceCategory);

        $this->assertDatabaseCount('price_categories', 1);
        $this->assertDatabaseHas('price_categories', [
            'id' => $model->id,
            'bar_id' => $membership->bar_id,
            'name' => 'Updated name',
            'currency' => 'JPY',
            'description' => 'Updated description',
        ]);
    }

    public function test_it_finds_price_category_by_id(): void
    {
        $membership = $this->setupBarMembership();
        $model = ModelPriceCategory::factory()->recycle($membership->bar)->create([
            'name' => 'Find me',
            'currency' => 'GBP',
            'description' => 'Lookup description',
        ]);

        $repository = new EloquentPriceCategoryRepository();
        $priceCategory = $repository->findById(new PriceCategoryId($model->id));

        $this->assertNotNull($priceCategory);
        $this->assertSame($model->id, $priceCategory->getId()?->value);
        $this->assertSame($membership->bar_id, $priceCategory->getBarId()->value);
        $this->assertSame('Find me', $priceCategory->getName()->toString());
        $this->assertSame('GBP', $priceCategory->getCurrency()->getCurrencyCode());
        $this->assertSame('Lookup description', $priceCategory->getDescription());
    }

    public function test_it_finds_many_price_categories_for_specific_bar(): void
    {
        $membership = $this->setupBarMembership();
        $otherMembership = $this->setupBarMembership();
        $first = ModelPriceCategory::factory()->recycle($membership->bar)->create(['name' => 'First']);
        $second = ModelPriceCategory::factory()->recycle($membership->bar)->create(['name' => 'Second']);
        $otherBarPriceCategory = ModelPriceCategory::factory()->recycle($otherMembership->bar)->create(['name' => 'Other']);

        $repository = new EloquentPriceCategoryRepository();
        $priceCategories = $repository->findMany(
            new BarId($membership->bar_id),
            [
                new PriceCategoryId($first->id),
                new PriceCategoryId($second->id),
                new PriceCategoryId($otherBarPriceCategory->id),
            ],
        );

        $this->assertCount(2, $priceCategories);
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            array_values(array_map(
                static fn (PriceCategory $priceCategory): int => $priceCategory->getId()?->value ?? 0,
                $priceCategories,
            )),
        );
    }
}
