<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\ComplexIngredient;
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
}
