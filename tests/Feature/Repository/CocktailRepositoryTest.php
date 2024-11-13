<?php

declare(strict_types=1);

namespace Tests\Feature\Repository;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\ComplexIngredient;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Repository\CocktailRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;

class CocktailRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gets_cocktails_that_can_be_made_with_ingredients(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient1 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient2 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient3 = Ingredient::factory()->for($membership->bar)->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient1)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient1)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient2)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient3)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => true])
                    ->for($ingredient2)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient3)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->create();

        Ingredient::factory()->for($membership->bar)->count(10)->create();
        Cocktail::factory()->recycle($membership->bar)->count(10)->create();

        $repository = resolve(CocktailRepository::class);
        $cocktails = $repository->getCocktailsByIngredients([$ingredient1->id, $ingredient3->id]);

        $this->assertSame([1, 3, 4], $cocktails->toArray());
    }

    public function test_gets_cocktails_that_can_be_made_with_substitute_ingredients(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient1 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient2 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient3 = Ingredient::factory()->for($membership->bar)->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient1)
                    ->has(CocktailIngredientSubstitute::factory()->for($ingredient2), 'substitutes')
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient3)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->create();

        Ingredient::factory()->for($membership->bar)->count(10)->create();
        Cocktail::factory()->recycle($membership->bar)->count(10)->create();

        $repository = resolve(CocktailRepository::class);
        $cocktails = $repository->getCocktailsByIngredients([$ingredient2->id, $ingredient3->id]);

        $this->assertSame([1], $cocktails->toArray());
    }

    public function test_gets_cocktails_that_can_be_made_with_complex_ingredients(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $whiskey = Ingredient::factory()->for($membership->bar)->create();
        $gin = Ingredient::factory()->for($membership->bar)->create();
        $lemon = Ingredient::factory()->for($membership->bar)->create();
        $lemonJuice = Ingredient::factory()
            ->for($membership->bar)
            ->has(ComplexIngredient::factory()->for($lemon), 'ingredientParts')
            ->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($gin)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($lemonJuice)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($whiskey)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($lemonJuice)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->create();

        Ingredient::factory()->for($membership->bar)->count(10)->create();
        Cocktail::factory()->recycle($membership->bar)->count(10)->create();

        $repository = resolve(CocktailRepository::class);
        $cocktails = $repository->getCocktailsByIngredients([$gin->id, $lemon->id]);

        $this->assertSame([1], $cocktails->toArray());
    }

    public function test_gets_cocktails_that_can_be_made_with_ingredients_include_parent_ingredients(): void
    {
        $membership = $this->setupBarMembership();

        $parentIngredient = Ingredient::factory()->for($membership->bar)->create();
        $subIngredient = Ingredient::factory()->for($membership->bar)->create(['parent_ingredient_id' => $parentIngredient->id]);
        $cocktail = Cocktail::factory()->for($membership->bar)->has(
            CocktailIngredient::factory()
                ->state(['optional' => false])
                ->for($parentIngredient)
                ->recycle($membership->bar),
            'ingredients'
        )->create();

        $repository = resolve(CocktailRepository::class);
        $cocktails = $repository->getCocktailsByIngredients([$subIngredient->id], null, true);

        $this->assertSame([$cocktail->id], $cocktails->toArray());
    }

    public function test_fetch_similar_cocktails(): void
    {
        $membership = $this->setupBarMembership();

        $gin = Ingredient::factory()->for($membership->bar)->create();
        $bourbon = Ingredient::factory()->for($membership->bar)->create();
        $campari = Ingredient::factory()->for($membership->bar)->create();
        $vermouth = Ingredient::factory()->for($membership->bar)->create();

        $negroni = Cocktail::factory()->for($membership->bar)
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($vermouth)
                    ->recycle($membership->bar),
                'ingredients')
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($campari)
                    ->recycle($membership->bar),
                'ingredients')
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($gin)
                    ->recycle($membership->bar),
                'ingredients')
            ->create();

        $boulvardier = Cocktail::factory()->for($membership->bar)
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($vermouth)
                    ->recycle($membership->bar),
                'ingredients')
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($campari)
                    ->recycle($membership->bar),
                'ingredients')
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($bourbon)
                    ->recycle($membership->bar),
                'ingredients')
            ->create();

        $repository = resolve(CocktailRepository::class);
        $cocktails = $repository->getSimilarCocktails($boulvardier);

        $this->assertSame($negroni->id, $cocktails->first()->id);
    }
}
