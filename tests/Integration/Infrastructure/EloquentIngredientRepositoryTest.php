<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use DateTimeImmutable;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Color;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use Kami\Cocktail\Models\Image as ModelImage;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Models\Ingredient as ModelIngredient;
use BarAssistant\Domain\Ingredient\ComplexIngredientPart;
use Kami\Cocktail\Models\PriceCategory as ModelPriceCategory;
use Kami\Cocktail\Infrastructure\EloquentIngredientRepository;

final class EloquentIngredientRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_and_finds_ingredient_with_parts_prices_and_images(): void
    {
        $membership = $this->setupBarMembership();
        $partModel = ModelIngredient::factory()->recycle($membership->bar)->create([
            'name' => 'Neutral spirit',
            'created_user_id' => $membership->user_id,
        ]);
        $priceCategory = ModelPriceCategory::factory()->recycle($membership->bar)->create([
            'currency' => 'EUR',
        ]);
        $image = ModelImage::factory()->create(['created_user_id' => $membership->user_id]);

        $ingredient = Ingredient::create(
            barId: new BarId($membership->bar_id),
            name: Name::fromString('Coffee liqueur'),
            authors: Authors::createdBy(new UserId($membership->user_id)),
            recordTimestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2025-01-01 12:00:00')),
            description: 'Sweetened coffee spirit',
            strength: ABV::from(20.0),
            origin: 'Italy',
            color: Color::fromHexString('#112233'),
            sugarContent: 0.35,
            acidity: 0.1,
            distillery: 'House Distillery',
            units: Unit::from('ml'),
        );
        $ingredient->addIngredientPart(
            ComplexIngredientPart::create(
                new IngredientId($partModel->id),
                AmountWithUnits::from(1.0, Unit::from('ml')),
                'test-note',
            )
        );
        $ingredient->addPrice(
            priceCategoryId: new \BarAssistant\Domain\Ingredient\PriceCategoryId($priceCategory->id),
            price: 12.5,
            currency: 'EUR',
            amount: 700,
            units: 'ml',
            description: 'Bottle',
        );
        $ingredient->addImage(new ImageId($image->id));

        $repository = new EloquentIngredientRepository();
        $savedIngredient = $repository->save($ingredient);

        $this->assertNotNull($savedIngredient->getId());
        $this->assertDatabaseHas('ingredients', [
            'id' => $savedIngredient->getId()?->value,
            'bar_id' => $membership->bar_id,
            'name' => 'Coffee liqueur',
            'description' => 'Sweetened coffee spirit',
            'strength' => 20.0,
            'origin' => 'Italy',
            'color' => '#112233',
            'created_user_id' => $membership->user_id,
            'sugar_g_per_ml' => 0.35,
            'acidity' => 0.1,
            'distillery' => 'House Distillery',
            'units' => 'ml',
        ]);
        $this->assertDatabaseHas('complex_ingredients', [
            'main_ingredient_id' => $savedIngredient->getId()?->value,
            'ingredient_id' => $partModel->id,
        ]);
        $this->assertDatabaseHas('ingredient_prices', [
            'ingredient_id' => $savedIngredient->getId()?->value,
            'price_category_id' => $priceCategory->id,
            'price' => 1250,
            'amount' => 700,
            'units' => 'ml',
            'description' => 'Bottle',
        ]);
        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'imageable_type' => ModelIngredient::class,
            'imageable_id' => $savedIngredient->getId()?->value,
        ]);

        $foundIngredient = $repository->findById($savedIngredient->getId() ?? new IngredientId(0));

        $this->assertNotNull($foundIngredient);
        $this->assertSame('Coffee liqueur', $foundIngredient->getName()->toString());
        $this->assertCount(1, $foundIngredient->getIngredientParts());
        $this->assertSame($partModel->id, $foundIngredient->getIngredientParts()[0]->getIngredientId()->value);
        $this->assertCount(1, $foundIngredient->getPrices());
        $this->assertCount(1, $foundIngredient->getImages());
    }

    public function test_it_filters_ingredient_collections_by_bar_and_parent(): void
    {
        $membership = $this->setupBarMembership();
        $otherMembership = $this->setupBarMembership();
        $parent = ModelIngredient::factory()->recycle($membership->bar)->create(['name' => 'Amaro']);
        $firstChild = ModelIngredient::factory()->recycle($membership->bar)->create([
            'name' => 'Fernet',
            'parent_ingredient_id' => $parent->id,
            'materialized_path' => $parent->id . '/',
        ]);
        $secondChild = ModelIngredient::factory()->recycle($membership->bar)->create([
            'name' => 'Aperitivo',
            'parent_ingredient_id' => $parent->id,
            'materialized_path' => $parent->id . '/',
        ]);
        $otherBarIngredient = ModelIngredient::factory()->recycle($otherMembership->bar)->create(['name' => 'Other']);

        $repository = new EloquentIngredientRepository();

        $listedIngredients = $repository->list(new BarId($membership->bar_id));
        $manyIngredients = $repository->findMany(
            new BarId($membership->bar_id),
            [
                new IngredientId($parent->id),
                new IngredientId($firstChild->id),
                new IngredientId($otherBarIngredient->id),
            ],
        );
        $children = $repository->findChildren(new IngredientId($parent->id));

        $this->assertCount(3, $listedIngredients);
        $this->assertCount(2, $manyIngredients);
        $this->assertEqualsCanonicalizing(
            [$firstChild->id, $secondChild->id],
            array_map(static fn (Ingredient $ingredient): int => $ingredient->getId()?->value ?? 0, $children),
        );
    }

    public function test_it_deletes_ingredient(): void
    {
        $membership = $this->setupBarMembership();
        $ingredient = ModelIngredient::factory()->recycle($membership->bar)->create();

        $repository = new EloquentIngredientRepository();
        $repository->delete(new IngredientId($ingredient->id));

        $this->assertDatabaseMissing('ingredients', ['id' => $ingredient->id]);
    }

    private function makePersistedIngredientReference(int $barId, int $userId, int $id, string $name): Ingredient
    {
        return Ingredient::create(
            barId: new BarId($barId),
            name: Name::fromString($name),
            authors: Authors::createdBy(new UserId($userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
        )->setId(new IngredientId($id));
    }
}
