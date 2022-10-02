<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\IngredientCategory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        IngredientCategory::create(['id' => 1, 'name' => 'Spirits']);
        IngredientCategory::create(['id' => 2, 'name' => 'Liqueurs']);
        IngredientCategory::create(['id' => 3, 'name' => 'Juices']);
        IngredientCategory::create(['id' => 4, 'name' => 'Fruits']);
        IngredientCategory::create(['id' => 5, 'name' => 'Syrups']);
        IngredientCategory::create(['id' => 6, 'name' => 'Wines']);
        IngredientCategory::create(['id' => 7, 'name' => 'Misc.']);

        Ingredient::create(['name' => 'Vodka', 'ingredient_category_id' => 1, 'strength' => 40.0, 'description' => 'Vodka desc.']);
        Ingredient::create(['name' => 'Whiskey', 'ingredient_category_id' => 1, 'strength' => 40.0, 'description' => 'Whiskey desc.']);
        $bourbon = Ingredient::create(['name' => 'Bourbon', 'ingredient_category_id' => 1, 'strength' => 40.0, 'description' => 'Bourbon desc.']);
        $lemJuice = Ingredient::create(['name' => 'Lemon juice', 'ingredient_category_id' => 3, 'strength' => 0.0, 'description' => 'Freshly squeezed lemon juice.']);
        $simpleSyrup = Ingredient::create(['name' => 'Simple syrup', 'ingredient_category_id' => 5, 'strength' => 0.0, 'description' => 'Equal parts water and sugar.']);

        $whSour = Cocktail::create(['name' => 'Whiskey Sour', 'instructions' => '1. Do this 2. Do that 3. Share 4. Pour']);
        CocktailIngredient::create(['cocktail_id' => $whSour->id, 'ingredient_id' => $bourbon->id, 'amount' => 45, 'units' => 'ml']);
        CocktailIngredient::create(['cocktail_id' => $whSour->id, 'ingredient_id' => $lemJuice->id, 'amount' => 30, 'units' => 'ml']);
        CocktailIngredient::create(['cocktail_id' => $whSour->id, 'ingredient_id' => $simpleSyrup->id, 'amount' => 15, 'units' => 'ml']);
    }
}
