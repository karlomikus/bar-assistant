<?php

declare(strict_types=1);

namespace Tests\Feature\External;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\External\BarOptionsEnum;
use Kami\Cocktail\External\Import\FromDataPack;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FromDataPackTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_all_data(): void
    {
        $membership = $this->setupBarMembership();

        $datapackFolder = Storage::build([
            'driver' => 'local',
            'root' => base_path('tests/fixtures/datapack'),
        ]);

        $this->assertDatabaseEmpty('glasses');
        $this->assertDatabaseEmpty('ingredient_categories');
        $this->assertDatabaseEmpty('cocktail_methods');
        $this->assertDatabaseEmpty('price_categories');
        $this->assertDatabaseEmpty('utensils');

        $importer = new FromDataPack();
        $importer->process($datapackFolder, $membership->bar, $membership->user, [BarOptionsEnum::Ingredients, BarOptionsEnum::Cocktails]);

        $this->assertDatabaseHas('glasses', ['name' => 'glass 1', 'description' => 'glass 1 description']);
        $this->assertDatabaseHas('glasses', ['name' => 'glass 2', 'description' => null]);

        $this->assertDatabaseHas('ingredient_categories', ['name' => 'category 1', 'description' => 'category description']);
        $this->assertDatabaseHas('ingredient_categories', ['name' => 'category 2', 'description' => null]);

        $this->assertDatabaseHas('cocktail_methods', ['name' => 'method 1', 'dilution_percentage' => 15]);
        $this->assertDatabaseHas('cocktail_methods', ['name' => 'method 2', 'dilution_percentage' => 0]);

        $this->assertDatabaseHas('price_categories', ['name' => 'price 1', 'currency' => 'NAM', 'description' => 'Price description']);
        $this->assertDatabaseHas('price_categories', ['name' => 'price 2', 'currency' => 'TKL', 'description' => null]);

        $this->assertDatabaseHas('utensils', ['name' => 'utensil 1', 'description' => 'utensil description']);

        $this->assertDatabaseHas('images', [
            'imageable_type' => Ingredient::class,
            'imageable_id' => 1,
            'copyright' => 'Image copyright',
            'sort' => 1,
            'placeholder_hash' => null,
        ]);
        $this->assertDatabaseHas('ingredients', [
            'bar_id' => $membership->bar->id,
            'created_user_id' => $membership->user->id,
            'name' => 'Test ingredient',
            'strength' => 37.75,
            'description' => 'Description of ingredient',
            'origin' => 'French Guiana',
            'color' => '#b474de',
            'ingredient_category_id' => 1,
            'created_at' => '1976-01-23T22:25:11+00:00',
            'updated_at' => '1998-01-08T13:41:44+00:00'
        ]);

        $this->assertDatabaseHas('images', [
            'imageable_type' => Cocktail::class,
            'imageable_id' => 1,
            'copyright' => 'Random image',
            'sort' => 1,
            'placeholder_hash' => null,
        ]);
        $this->assertDatabaseHas('cocktails', [
            'bar_id' => $membership->bar->id,
            'created_user_id' => $membership->user->id,
            'name' => 'Test cocktail',
            'instructions' => 'Cocktail instructions',
            'description' => 'Cocktail description',
            'source' => 'http://www.bins.org/fugiat-reprehenderit-necessitatibus-sapiente-quia',
            'garnish' => 'Lemon wheel',
            'abv' => 37.77,
            'created_at' => '1979-12-23T09:07:48+00:00',
            'updated_at' => '1983-01-24T11:37:19+00:00',
            'glass_id' => 2,
            'cocktail_method_id' => 1,
        ]);
    }
}
