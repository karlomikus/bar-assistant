<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Exceptions\IngredientMoveException;
use Kami\Cocktail\Exceptions\IngredientPathTooDeepException;

class IngredientPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingredient_returns_descendants(): void
    {
        $bar = Bar::factory()->create();
        $spirits = Ingredient::factory()->for($bar)->create([
            'name' => 'Spirits',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $gin = Ingredient::factory()->for($bar)->create();
        $gin->appendAsChildOf($spirits);
        $oldTomGin = Ingredient::factory()->for($bar)->create();
        $oldTomGin->appendAsChildOf($gin);
        $oldTomGinSpecific = Ingredient::factory()->for($bar)->create();
        $oldTomGinSpecific->appendAsChildOf($oldTomGin);
        $londonDry = Ingredient::factory()->for($bar)->create();
        $londonDry->appendAsChildOf($gin);
        $whiskey = Ingredient::factory()->for($bar)->create([
            'name' => 'Whiskey',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $jd = Ingredient::factory()->for($bar)->create();
        $jd->appendAsChildOf($whiskey);

        $this->assertCount(4, $spirits->descendants);
        $this->assertCount(3, $gin->descendants);
        $this->assertCount(1, $oldTomGin->descendants);
        $this->assertCount(0, $oldTomGinSpecific->descendants);
        $this->assertCount(0, $londonDry->descendants);
        $this->assertCount(1, $whiskey->descendants);
        $this->assertCount(0, $jd->descendants);
    }

    public function test_ingredient_eager_loads_descendants(): void
    {
        $bar = Bar::factory()->create();
        $spirits = Ingredient::factory()->for($bar)->create();
        $gin = Ingredient::factory()->for($bar)->create();
        $gin->appendAsChildOf($spirits);
        $oldTomGin = Ingredient::factory()->for($bar)->create();
        $oldTomGin->appendAsChildOf($gin);
        $oldTomGinSpecific = Ingredient::factory()->for($bar)->create();
        $oldTomGinSpecific->appendAsChildOf($oldTomGin);

        $res = Ingredient::with('descendants')->where('id', 1)->get();
        $this->assertSame(3, $res->first()->descendants->count());

        $res = Ingredient::with('descendants')->where('id', 2)->get();
        $this->assertSame(2, $res->first()->descendants->count());
    }

    public function test_ingredient_returns_ancestors(): void
    {
        $bar = Bar::factory()->create();
        $spirits = Ingredient::factory()->for($bar)->create([
            'name' => 'Spirits',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $gin = Ingredient::factory()->for($bar)->create();
        $gin->appendAsChildOf($spirits);
        $oldTomGin = Ingredient::factory()->for($bar)->create();
        $oldTomGin->appendAsChildOf($gin);
        $oldTomGinSpecific = Ingredient::factory()->for($bar)->create();
        $oldTomGinSpecific->appendAsChildOf($oldTomGin);
        $londonDry = Ingredient::factory()->for($bar)->create();
        $londonDry->appendAsChildOf($gin);
        $whiskey = Ingredient::factory()->for($bar)->create([
            'name' => 'Whiskey',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $jd = Ingredient::factory()->for($bar)->create();
        $jd->appendAsChildOf($whiskey);

        $this->assertCount(0, $spirits->ancestors);
        $this->assertCount(1, $gin->ancestors);
        $this->assertCount(2, $oldTomGin->ancestors);
        $this->assertCount(3, $oldTomGinSpecific->ancestors);
        $this->assertCount(2, $londonDry->ancestors);
        $this->assertCount(1, $jd->ancestors);
        $this->assertCount(0, $whiskey->ancestors);
    }

    public function test_ingredient_creates_materialized_path_value_object(): void
    {
        $bar = Bar::factory()->create();
        $parentIngredient1 = Ingredient::factory()->for($bar)->create([
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);

        $empty = $parentIngredient1->getMaterializedPath();
        $this->assertSame([], $empty->toArray());

        $parentIngredient12 = Ingredient::factory()->for($bar)->create([
            'parent_ingredient_id' => $parentIngredient1->id,
            'materialized_path' => '1/',
        ]);

        $path12 = $parentIngredient12->getMaterializedPath();
        $this->assertSame([1], $path12->toArray());
    }

    public function test_ingredient_can_be_root(): void
    {
        $bar = Bar::factory()->create();
        $parentIngredient1 = Ingredient::factory()->for($bar)->create([
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);

        $this->assertTrue($parentIngredient1->isRoot());

        $parentIngredient12 = Ingredient::factory()->for($bar)->create([
            'parent_ingredient_id' => $parentIngredient1->id,
            'materialized_path' => '1/',
        ]);

        $this->assertFalse($parentIngredient12->isRoot());
    }

    public function test_ingredient_rebuilds_path_for_its_descendants_when_parent_changes(): void
    {
        $bar = Bar::factory()->create();
        $spirits = Ingredient::factory()->for($bar)->create([
            'name' => 'Spirits',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $gin = Ingredient::factory()->for($bar)->create();
        $gin->appendAsChildOf($spirits);
        $oldTomGin = Ingredient::factory()->for($bar)->create();
        $oldTomGin->appendAsChildOf($gin);
        $oldTomGinSpecific = Ingredient::factory()->for($bar)->create();
        $oldTomGinSpecific->appendAsChildOf($oldTomGin);
        $londonDry = Ingredient::factory()->for($bar)->create();
        $londonDry->appendAsChildOf($gin);
        $whiskey = Ingredient::factory()->for($bar)->create([
            'name' => 'Whiskey',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $jd = Ingredient::factory()->for($bar)->create();
        $jd->appendAsChildOf($whiskey);

        $gin->appendAsChildOf($whiskey);

        $this->assertDatabaseHas('ingredients', ['id' => $gin->id, 'parent_ingredient_id' => $whiskey->id]);
        $this->assertDatabaseHas('ingredients', ['id' => $londonDry->id, 'parent_ingredient_id' => $gin->id]);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGin->id, 'materialized_path' => $whiskey->id . '/2/']);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGinSpecific->id, 'materialized_path' => $whiskey->id . '/2/3/']);
        $this->assertDatabaseHas('ingredients', ['id' => $londonDry->id, 'materialized_path' => $whiskey->id . '/2/']);
    }

    public function test_ingredient_rebuilds_path_for_its_descendants_when_parent_changes_to_root(): void
    {
        $bar = Bar::factory()->create();
        $spirits = Ingredient::factory()->for($bar)->create([
            'name' => 'Spirits',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $gin = Ingredient::factory()->for($bar)->create([
            'name' => 'Gin',
        ]);
        $gin->appendAsChildOf($spirits);
        $oldTomGin = Ingredient::factory()->for($bar)->create([
            'name' => 'Old tom gin',
        ]);
        $oldTomGin->appendAsChildOf($gin);
        $oldTomGinSpecific = Ingredient::factory()->for($bar)->create([
            'name' => 'Specific old tom gin',
        ]);
        $oldTomGinSpecific->appendAsChildOf($oldTomGin);
        $ldg = Ingredient::factory()->for($bar)->create([
            'name' => 'London dry gin',
            'parent_ingredient_id' => $gin->id,
            'materialized_path' => implode('/', [$spirits->id, $gin->id]) . '/',
        ]);
        $ldg->appendAsChildOf($gin);
        $whiskey = Ingredient::factory()->for($bar)->create([
            'name' => 'Whiskey',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $jd = Ingredient::factory()->for($bar)->create([
            'name' => 'Jack Daniels',
            'parent_ingredient_id' => $whiskey->id,
            'materialized_path' => $whiskey->id . '/',
        ]);
        $jd->appendAsChildOf($whiskey);

        $gin->appendAsChildOf(null);

        $this->assertDatabaseHas('ingredients', ['id' => $gin->id, 'parent_ingredient_id' => null]);
        $this->assertDatabaseHas('ingredients', ['id' => $gin->id, 'materialized_path' => null]);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGin->id, 'materialized_path' => $gin->id . '/']);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGin->id, 'parent_ingredient_id' => $gin->id]);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGinSpecific->id, 'materialized_path' => $gin->id . '/' . $oldTomGin->id . '/']);

        $whiskey->appendAsChildOf(null);

        $this->assertDatabaseHas('ingredients', ['id' => $whiskey->id, 'parent_ingredient_id' => null]);
        $this->assertDatabaseHas('ingredients', ['id' => $whiskey->id, 'materialized_path' => null]);

        $oldTomGin->appendAsChildOf(null);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGin->id, 'parent_ingredient_id' => null]);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGin->id, 'materialized_path' => null]);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGinSpecific->id, 'materialized_path' => '3/']);
    }

    public function test_ingredient_moves_root_to_descendant(): void
    {
        $bar = Bar::factory()->create();
        $spirits = Ingredient::factory()->for($bar)->create([
            'name' => 'Spirits',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $gin = Ingredient::factory()->for($bar)->create([
            'name' => 'Gin',
            'parent_ingredient_id' => $spirits->id,
            'materialized_path' => $spirits->id . '/',
        ]);
        $oldTomGin = Ingredient::factory()->for($bar)->create([
            'name' => 'Old tom gin',
            'parent_ingredient_id' => $gin->id,
            'materialized_path' => implode('/', [$spirits->id, $gin->id]) . '/',
        ]);
        $oldTomGinSpecific = Ingredient::factory()->for($bar)->create([
            'name' => 'Specific old tom gin',
            'parent_ingredient_id' => $oldTomGin->id,
            'materialized_path' => implode('/', [$spirits->id, $gin->id, $oldTomGin->id]) . '/',
        ]);
        Ingredient::factory()->for($bar)->create([
            'name' => 'London dry gin',
            'parent_ingredient_id' => $gin->id,
            'materialized_path' => implode('/', [$spirits->id, $gin->id]) . '/',
        ]);
        $whiskey = Ingredient::factory()->for($bar)->create([
            'name' => 'Whiskey',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        Ingredient::factory()->for($bar)->create([
            'name' => 'Jack Daniels',
            'parent_ingredient_id' => $whiskey->id,
            'materialized_path' => $whiskey->id . '/',
        ]);

        $this->expectException(IngredientMoveException::class);
        $spirits->appendAsChildOf($oldTomGinSpecific);
    }

    public function test_ingredient_prints_path(): void
    {
        $bar = Bar::factory()->create();
        $spirits = Ingredient::factory()->for($bar)->create([
            'name' => 'Spirits',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $gin = Ingredient::factory()->for($bar)->create([
            'name' => 'Gin',
            'parent_ingredient_id' => $spirits->id,
            'materialized_path' => $spirits->id . '/',
        ]);
        $oldTomGin = Ingredient::factory()->for($bar)->create([
            'name' => 'Old tom gin',
            'parent_ingredient_id' => $gin->id,
            'materialized_path' => implode('/', [$spirits->id, $gin->id]) . '/',
        ]);
        $oldTomGinSpecific = Ingredient::factory()->for($bar)->create([
            'name' => 'Specific old tom gin',
            'parent_ingredient_id' => $oldTomGin->id,
            'materialized_path' => implode('/', [$spirits->id, $gin->id, $oldTomGin->id]) . '/',
        ]);

        $this->assertSame('Spirits > Gin > Old tom gin', $oldTomGinSpecific->getMaterializedPathAsString());
        $this->assertSame('Spirits > Gin', $oldTomGin->getMaterializedPathAsString());
        $this->assertSame('Spirits', $gin->getMaterializedPathAsString());
        $this->assertSame(null, $spirits->getMaterializedPathAsString());
    }

    public function test_ingredient_can_check_if_descendant_of(): void
    {
        $bar = Bar::factory()->create();
        $spirits = Ingredient::factory()->for($bar)->create([
            'name' => 'Spirits',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $gin = Ingredient::factory()->for($bar)->create([
            'name' => 'Gin',
            'parent_ingredient_id' => $spirits->id,
            'materialized_path' => $spirits->id . '/',
        ]);
        $oldTomGin = Ingredient::factory()->for($bar)->create([
            'name' => 'Old tom gin',
            'parent_ingredient_id' => $gin->id,
            'materialized_path' => implode('/', [$spirits->id, $gin->id]) . '/',
        ]);
        $oldTomGinSpecific = Ingredient::factory()->for($bar)->create([
            'name' => 'Specific old tom gin',
            'parent_ingredient_id' => $oldTomGin->id,
            'materialized_path' => implode('/', [$spirits->id, $gin->id, $oldTomGin->id]) . '/',
        ]);
        $whiskey = Ingredient::factory()->for($bar)->create([
            'name' => 'Whiskey',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);

        $this->assertTrue($oldTomGinSpecific->isDescendantOf($oldTomGin));
        $this->assertTrue($oldTomGinSpecific->isDescendantOf($gin));
        $this->assertTrue($oldTomGinSpecific->isDescendantOf($spirits));
        $this->assertFalse($oldTomGinSpecific->isDescendantOf($whiskey));
    }

    public function test_ingredient_checks_max_depth(): void
    {
        $bar = Bar::factory()->create();
        $parent = null;
        $path = '';
        $max = 11;

        for ($i = 1; $i <= $max; $i++) {
            $ingredient = Ingredient::factory()->for($bar)->create([
                'parent_ingredient_id' => $parent?->id, // Set parent if exists, else null
                'materialized_path' => $path, // Use accumulated path
            ]);

            // Update parent reference and path for the next iteration
            $parent = $ingredient;
            $path = ltrim($path . $ingredient->id . '/', '/'); // Append current ID to path
        }

        $lastIngredient = Ingredient::find($max);
        $newIngredient = Ingredient::factory()->for($bar)->create();

        $this->expectException(IngredientPathTooDeepException::class);
        $newIngredient->appendAsChildOf($lastIngredient);
    }

    public function test_ingredient_delete_updates_paths(): void
    {
        $bar = Bar::factory()->create();
        $spirits = Ingredient::factory()->for($bar)->create([
            'name' => 'Spirits',
            'parent_ingredient_id' => null,
            'materialized_path' => null,
        ]);
        $gin = Ingredient::factory()->for($bar)->create(['name' => 'Gin']);
        $gin->appendAsChildOf($spirits);
        $oldTomGin = Ingredient::factory()->for($bar)->create(['name' => 'Old Tom Gin']);
        $oldTomGin->appendAsChildOf($gin);
        $oldTomGinSpecific = Ingredient::factory()->for($bar)->create(['name' => 'Old Tom Gin Specific']);
        $oldTomGinSpecific->appendAsChildOf($oldTomGin);
        $londonDry = Ingredient::factory()->for($bar)->create(['name' => 'London Dry']);
        $londonDry->appendAsChildOf($gin);

        $gin->delete();

        $this->assertDatabaseMissing('ingredients', ['id' => 2]);
        $this->assertDatabaseMissing('ingredients', ['parent_ingredient_id' => 2]);
        $this->assertDatabaseHas('ingredients', ['id' => $spirits->id, 'parent_ingredient_id' => null]);
        $this->assertDatabaseHas('ingredients', ['id' => $spirits->id, 'materialized_path' => null]);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGin->id, 'parent_ingredient_id' => null]);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGin->id, 'materialized_path' => null]);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGinSpecific->id, 'parent_ingredient_id' => $oldTomGin->id]);
        $this->assertDatabaseHas('ingredients', ['id' => $oldTomGinSpecific->id, 'materialized_path' => $oldTomGin->id . '/']);
    }
}
