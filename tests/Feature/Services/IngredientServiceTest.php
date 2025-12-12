<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\ComplexIngredient;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IngredientServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_correctly_matches_all_possible_member_ingredients(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Random ingredients
        Ingredient::factory()->for($membership->bar)->count(10)->create();
        // Normal ingredient in shelf
        $ingredient1 = Ingredient::factory()->for($membership->bar)->create();
        // Variant ingredient in shelf
        $ingredient2 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient3 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient3->appendAsChildOf($ingredient2);
        // Complex ingredient in shelf
        $ingredient4 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient5 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient6 = Ingredient::factory()->for($membership->bar)->create();
        ComplexIngredient::factory()->for($ingredient4, 'ingredient')->for($ingredient6, 'mainIngredient')->create();
        ComplexIngredient::factory()->for($ingredient5, 'ingredient')->for($ingredient6, 'mainIngredient')->create();

        UserIngredient::factory()->for($membership)->for($ingredient1)->create();
        UserIngredient::factory()->for($membership)->for($ingredient2)->create();
        UserIngredient::factory()->for($membership)->for($ingredient4)->create();
        UserIngredient::factory()->for($membership)->for($ingredient5)->create();

        $repository = resolve(IngredientService::class);
        $ingredients = $repository->getMemberIngredients($membership->user_id, $membership->bar_id);

        $this->assertCount(6, $ingredients);
    }

    public function test_get_ingredients_for_possible_cocktails(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Create ingredients
        $ingredient1 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Vodka']);
        $ingredient2 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Gin']);
        $ingredient3 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Rum']);
        $ingredient4 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Tequila']);
        $ingredient5 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Lime Juice']);
        $ingredient6 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Simple Syrup']);
        $ingredient7 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Triple Sec']);
        $ingredient8 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Tonic Water']);

        // Create cocktails with different ingredient combinations
        // Cocktail 1: Uses ingredient1 (in shelf) + ingredient5 (not in shelf)
        $cocktail1 = Cocktail::factory()->for($membership->bar)->create(['name' => 'Vodka Lime']);
        CocktailIngredient::factory()->for($cocktail1, 'cocktail')->for($ingredient1, 'ingredient')->create(['sort' => 1]);
        CocktailIngredient::factory()->for($cocktail1, 'cocktail')->for($ingredient5, 'ingredient')->create(['sort' => 2]);

        // Cocktail 2: Uses ingredient1 (in shelf) + ingredient5 (not in shelf)
        $cocktail2 = Cocktail::factory()->for($membership->bar)->create(['name' => 'Vodka Lime Special']);
        CocktailIngredient::factory()->for($cocktail2, 'cocktail')->for($ingredient1, 'ingredient')->create(['sort' => 1]);
        CocktailIngredient::factory()->for($cocktail2, 'cocktail')->for($ingredient5, 'ingredient')->create(['sort' => 2]);

        // Cocktail 3: Uses ingredient2 (in shelf) + ingredient5 (not in shelf)
        $cocktail3 = Cocktail::factory()->for($membership->bar)->create(['name' => 'Gin Lime']);
        CocktailIngredient::factory()->for($cocktail3, 'cocktail')->for($ingredient2, 'ingredient')->create(['sort' => 1]);
        CocktailIngredient::factory()->for($cocktail3, 'cocktail')->for($ingredient5, 'ingredient')->create(['sort' => 2]);

        // Cocktail 4: Uses ingredient1 (in shelf) + ingredient6 (not in shelf)
        $cocktail4 = Cocktail::factory()->for($membership->bar)->create(['name' => 'Vodka Simple']);
        CocktailIngredient::factory()->for($cocktail4, 'cocktail')->for($ingredient1, 'ingredient')->create(['sort' => 1]);
        CocktailIngredient::factory()->for($cocktail4, 'cocktail')->for($ingredient6, 'ingredient')->create(['sort' => 2]);

        // Cocktail 5: Uses ingredient7 (not in shelf) + ingredient8 (not in shelf)
        $cocktail5 = Cocktail::factory()->for($membership->bar)->create(['name' => 'Triple Sec Tonic']);
        CocktailIngredient::factory()->for($cocktail5, 'cocktail')->for($ingredient7, 'ingredient')->create(['sort' => 1]);
        CocktailIngredient::factory()->for($cocktail5, 'cocktail')->for($ingredient8, 'ingredient')->create(['sort' => 2]);

        // User has ingredient1 (Vodka) and ingredient2 (Gin) in their shelf
        UserIngredient::factory()->for($membership)->for($ingredient1)->create();
        UserIngredient::factory()->for($membership)->for($ingredient2)->create();

        $service = resolve(IngredientService::class);
        $results = $service->getIngredientsForPossibleCocktails($membership->bar_id, [$ingredient1->id, $ingredient2->id]);

        // ingredient5 (Lime Juice) should unlock 3 cocktails (cocktail1, cocktail2, cocktail3)
        // ingredient6 (Simple Syrup) should unlock 1 cocktail (cocktail4)
        // ingredient7 and ingredient8 should unlock 0 cocktails (cocktail5 needs both)

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        // Find Lime Juice in results
        $limeJuiceResult = collect($results)->firstWhere('id', $ingredient5->id);
        $this->assertNotNull($limeJuiceResult);
        $this->assertEquals('Lime Juice', $limeJuiceResult->name);
        $this->assertEquals(3, $limeJuiceResult->potential_cocktails);

        // Find Simple Syrup in results
        $simpleSyrupResult = collect($results)->firstWhere('id', $ingredient6->id);
        $this->assertNotNull($simpleSyrupResult);
        $this->assertEquals('Simple Syrup', $simpleSyrupResult->name);
        $this->assertEquals(1, $simpleSyrupResult->potential_cocktails);

        // Verify results are ordered by potential_cocktails DESC
        $firstResult = $results[0];
        $this->assertEquals($ingredient5->id, $firstResult->id);
        $this->assertEquals(3, $firstResult->potential_cocktails);
    }

    public function test_get_ingredients_for_possible_cocktails_with_complex_ingredients(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Create base ingredients
        $ingredient1 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Base Spirit']);
        $ingredient2 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Part A']);
        $ingredient3 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Part B']);
        $ingredient4 = Ingredient::factory()->for($membership->bar)->create(['name' => 'Mixer']);

        // Create complex ingredient made from Part A and Part B
        $complexIngredient = Ingredient::factory()->for($membership->bar)->create(['name' => 'Complex Mix']);
        ComplexIngredient::factory()->for($ingredient2, 'ingredient')->for($complexIngredient, 'mainIngredient')->create();
        ComplexIngredient::factory()->for($ingredient3, 'ingredient')->for($complexIngredient, 'mainIngredient')->create();

        // Create a cocktail that uses the complex ingredient
        $cocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Complex Cocktail']);
        CocktailIngredient::factory()->for($cocktail, 'cocktail')->for($ingredient1, 'ingredient')->create(['sort' => 1]);
        CocktailIngredient::factory()->for($cocktail, 'cocktail')->for($complexIngredient, 'ingredient')->create(['sort' => 2]);

        // User has base spirit, Part A, and Part B (which means they can make the complex ingredient)
        UserIngredient::factory()->for($membership)->for($ingredient1)->create();
        UserIngredient::factory()->for($membership)->for($ingredient2)->create();
        UserIngredient::factory()->for($membership)->for($ingredient3)->create();

        $service = resolve(IngredientService::class);
        $results = $service->getIngredientsForPossibleCocktails(
            $membership->bar_id,
            [$ingredient1->id, $ingredient2->id, $ingredient3->id]
        );

        // The complex ingredient should NOT appear in results because the user can already make it
        // from Part A and Part B that they have
        $complexResult = collect($results)->firstWhere('id', $complexIngredient->id);
        $this->assertNull($complexResult);
    }

    public function test_get_ingredients_for_possible_cocktails_only_returns_bar_ingredients(): void
    {
        $membership1 = $this->setupBarMembership();
        $membership2 = $this->setupBarMembership();

        // Create ingredients in bar 1
        $bar1Ingredient1 = Ingredient::factory()->for($membership1->bar)->create(['name' => 'Bar 1 Spirit']);
        $bar1Ingredient2 = Ingredient::factory()->for($membership1->bar)->create(['name' => 'Bar 1 Mixer']);

        // Create ingredients in bar 2
        $bar2Ingredient1 = Ingredient::factory()->for($membership2->bar)->create(['name' => 'Bar 2 Spirit']);
        $bar2Ingredient2 = Ingredient::factory()->for($membership2->bar)->create(['name' => 'Bar 2 Mixer']);

        // Create cocktails in both bars
        $bar1Cocktail = Cocktail::factory()->for($membership1->bar)->create();
        CocktailIngredient::factory()->for($bar1Cocktail, 'cocktail')->for($bar1Ingredient1, 'ingredient')->create();
        CocktailIngredient::factory()->for($bar1Cocktail, 'cocktail')->for($bar1Ingredient2, 'ingredient')->create();

        $bar2Cocktail = Cocktail::factory()->for($membership2->bar)->create();
        CocktailIngredient::factory()->for($bar2Cocktail, 'cocktail')->for($bar2Ingredient1, 'ingredient')->create();
        CocktailIngredient::factory()->for($bar2Cocktail, 'cocktail')->for($bar2Ingredient2, 'ingredient')->create();

        $service = resolve(IngredientService::class);
        $results = $service->getIngredientsForPossibleCocktails($membership1->bar_id, [$bar1Ingredient1->id]);

        // Should only return ingredients from bar 1
        $resultIds = collect($results)->pluck('id')->toArray();
        $this->assertContains($bar1Ingredient2->id, $resultIds);
        $this->assertNotContains($bar2Ingredient1->id, $resultIds);
        $this->assertNotContains($bar2Ingredient2->id, $resultIds);
    }
}
