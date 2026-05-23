<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use BarAssistant\Domain\Bar\BarInventory;
use Kami\Cocktail\Models\ComplexIngredient;
use BarAssistant\Domain\Ingredient\IngredientId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentBarInventoryRepository;

final class EloquentBarInventoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_null_for_non_existent_bar(): void
    {
        $repository = new EloquentBarInventoryRepository();

        $inventory = $repository->findByBarId(new BarId(9999));

        $this->assertNull($inventory);
    }

    public function test_it_saves_inventory_and_returns_makeable_complex_ingredients(): void
    {
        $membership = $this->setupBarMembership();
        $repository = new EloquentBarInventoryRepository();

        $requiredIngredientA = Ingredient::factory()->for($membership->bar)->create();
        $requiredIngredientB = Ingredient::factory()->for($membership->bar)->create();
        $complexIngredient = Ingredient::factory()->for($membership->bar)->create();

        ComplexIngredient::factory()->for($requiredIngredientA, 'ingredient')->for($complexIngredient, 'mainIngredient')->create();
        ComplexIngredient::factory()->for($requiredIngredientB, 'ingredient')->for($complexIngredient, 'mainIngredient')->create();

        $inventory = BarInventory::create(new BarId($membership->bar_id))
            ->putIngredient(new IngredientId($requiredIngredientA->id), status: \BarAssistant\Domain\Bar\IngredientInventoryStatus::InStock)
            ->putIngredient(new IngredientId($requiredIngredientB->id), status: \BarAssistant\Domain\Bar\IngredientInventoryStatus::InStock);

        $savedInventory = $repository->save($inventory);

        $this->assertDatabaseHas('bar_ingredients', [
            'bar_id' => $membership->bar_id,
            'ingredient_id' => $requiredIngredientA->id,
        ]);
        $this->assertDatabaseHas('bar_ingredients', [
            'bar_id' => $membership->bar_id,
            'ingredient_id' => $requiredIngredientB->id,
        ]);
        $this->assertDatabaseMissing('bar_ingredients', [
            'bar_id' => $membership->bar_id,
            'ingredient_id' => $complexIngredient->id,
        ]);

        $inStockIds = array_map(
            static fn ($item): int => $item->ingredientId->value,
            $savedInventory->getInStockIngredients(),
        );
        $makeableIds = array_map(
            static fn ($item): int => $item->ingredientId->value,
            array_filter(
                $savedInventory->getIngredients(),
                static fn ($item): bool => $item->isInStockAsMakeable(),
            ),
        );

        $this->assertEqualsCanonicalizing([$requiredIngredientA->id, $requiredIngredientB->id], $inStockIds);
        $this->assertSame([$complexIngredient->id], array_values($makeableIds));
    }

    public function test_it_replaces_persisted_inventory_with_current_in_stock_ingredients(): void
    {
        $membership = $this->setupBarMembership();
        $repository = new EloquentBarInventoryRepository();

        $removedIngredient = Ingredient::factory()->for($membership->bar)->create();
        $keptIngredient = Ingredient::factory()->for($membership->bar)->create();
        $addedIngredient = Ingredient::factory()->for($membership->bar)->create();

        BarIngredient::factory()->for($membership->bar)->for($removedIngredient)->create();
        BarIngredient::factory()->for($membership->bar)->for($keptIngredient)->create();

        $inventory = BarInventory::create(new BarId($membership->bar_id))
            ->putIngredient(new IngredientId($keptIngredient->id), status: \BarAssistant\Domain\Bar\IngredientInventoryStatus::InStock)
            ->putIngredient(new IngredientId($addedIngredient->id), status: \BarAssistant\Domain\Bar\IngredientInventoryStatus::InStock);

        $repository->save($inventory);

        $this->assertDatabaseMissing('bar_ingredients', [
            'bar_id' => $membership->bar_id,
            'ingredient_id' => $removedIngredient->id,
        ]);
        $this->assertDatabaseHas('bar_ingredients', [
            'bar_id' => $membership->bar_id,
            'ingredient_id' => $keptIngredient->id,
        ]);
        $this->assertDatabaseHas('bar_ingredients', [
            'bar_id' => $membership->bar_id,
            'ingredient_id' => $addedIngredient->id,
        ]);

        $reloadedInventory = $repository->findByBarId(new BarId($membership->bar_id));

        $this->assertNotNull($reloadedInventory);
        $this->assertEqualsCanonicalizing(
            [$keptIngredient->id, $addedIngredient->id],
            array_map(
                static fn ($item): int => $item->ingredientId->value,
                $reloadedInventory->getInStockIngredients(),
            ),
        );
    }
}
