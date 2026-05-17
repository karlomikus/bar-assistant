<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BarInventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_ingredients_on_bar_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        BarIngredient::factory()
            ->recycle($membership->bar, $membership->user)
            ->count(5)
            ->create();

        $response = $this->getJson('/api/bars/'. $membership->bar_id .'/inventory/ingredients');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 5)
                ->etc()
        );
    }

    public function test_add_multiple_ingredients_to_bar_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $newIngredients = Ingredient::factory()
            ->recycle($membership->bar)
            ->count(5)
            ->create();

        $response = $this->postJson('/api/bars/'. $membership->bar_id .'/inventory/ingredients/batch-store', [
            'ingredients' => $newIngredients->pluck('id')->toArray()
        ]);

        $response->assertNoContent();
    }

    public function test_delete_multiple_ingredients_from_bar_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredients = BarIngredient::factory()
            ->recycle($membership->bar, $membership->user)
            ->count(5)
            ->create();

        $this->assertDatabaseCount('bar_ingredients', 5);
        $response = $this->postJson('/api/bars/'. $membership->bar_id .'/inventory/ingredients/batch-delete', [
            'ingredients' => $ingredients->splice(0, 2)->pluck('id')->toArray()
        ]);

        $response->assertNoContent();
    }

    public function test_ingredients_extra_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient1 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient2 = Ingredient::factory()->for($membership->bar)->create();
        BarIngredient::factory()->for($membership->bar)->for($ingredient1)->create();

        $cocktail = Cocktail::factory()
            ->for($membership->bar)
            ->has(CocktailIngredient::factory()->state(['optional' => false])->for(
                $ingredient1
            ), 'ingredients')
            ->has(CocktailIngredient::factory()->state(['optional' => false])->for(
                $ingredient2
            ), 'ingredients')
            ->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->getJson('/api/bars/'. $membership->bar_id . '/inventory/ingredients/' . $ingredient1->id . '/extra');
        $response->assertJsonCount(0, 'data');

        $response = $this->getJson('/api/bars/'. $membership->bar_id . '/inventory/ingredients/' . $ingredient2->id . '/extra');

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 1, function (AssertableJson $jsonIng) use ($cocktail) {
                    $jsonIng
                        ->where('id', $cocktail->id)
                        ->where('slug', $cocktail->slug)
                        ->where('name', $cocktail->name);
                })
        );
    }
}
