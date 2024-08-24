<?php

declare(strict_types=1);

namespace Tests\Feature\Repository;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Repository\CocktailRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            ->has(CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient1)
                    ->recycle($membership->bar),
                'ingredients')
            ->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient1)
                    ->recycle($membership->bar),
                'ingredients')
            ->has(CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient2)
                    ->recycle($membership->bar),
                'ingredients')
            ->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient3)
                    ->recycle($membership->bar),
                'ingredients')
            ->create();

        Cocktail::factory()
            ->for($membership->bar)
            ->has(CocktailIngredient::factory()
                    ->state(['optional' => true])
                    ->for($ingredient2)
                    ->recycle($membership->bar),
                'ingredients')
            ->has(CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient3)
                    ->recycle($membership->bar),
                'ingredients')
            ->create();

        Ingredient::factory()->for($membership->bar)->count(10)->create();
        Cocktail::factory()->recycle($membership->bar)->count(10)->create();

        $repository = resolve(CocktailRepository::class);
        $cocktails = $repository->getCocktailsByIngredients([$ingredient1->id, $ingredient3->id]);

        $this->assertSame([1, 3, 4], $cocktails->toArray());
    }
}
