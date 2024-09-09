<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use Carbon\Carbon;
use Tests\TestCase;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\External\Model\Schema as SchemaExternal;

class ExportTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_recipe_as_markdown(): void
    {
        $cocktail = $this->setupCocktail();
        $external = SchemaExternal::fromCocktailModel($cocktail);

        $result = file_get_contents(base_path('tests/fixtures/external/markdown.md'));

        $this->assertSame($result, $external->toMarkdown());
    }

    public function test_export_recipe_as_jsonld(): void
    {
        $cocktail = $this->setupCocktail();
        $external = SchemaExternal::fromCocktailModel($cocktail);

        $result = file_get_contents(base_path('tests/fixtures/external/json-ld.json'));

        $this->assertSame($result, $external->cocktail->toJSONLD());
    }

    public function test_export_recipe_as_yaml(): void
    {
        $cocktail = $this->setupCocktail();
        $external = SchemaExternal::fromCocktailModel($cocktail);

        $result = file_get_contents(base_path('tests/fixtures/external/recipe.yml'));

        $this->assertSame($result, $external->toYAML());
    }

    public function test_export_recipe_as_draft2_schema(): void
    {
        $cocktail = $this->setupCocktail();
        $external = SchemaExternal::fromCocktailModel($cocktail);

        $result = file_get_contents(base_path('tests/fixtures/external/recipe.json'));

        $this->assertSame($result, json_encode($external->toDraft2Array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function test_export_recipe_as_xml(): void
    {
        $cocktail = $this->setupCocktail();
        $external = SchemaExternal::fromCocktailModel($cocktail);

        $result = file_get_contents(base_path('tests/fixtures/external/recipe.xml'));

        $this->assertSame($result, $external->toXML());
    }

    private function setupCocktail(): Cocktail
    {
        $membership = $this->setupBarMembership();

        return Cocktail::factory()
            ->for($membership->bar)
            ->for(Glass::factory()->for($membership->bar)->create(['name' => 'Highball']))
            ->for(CocktailMethod::factory()->for($membership->bar)->create(['name' => 'Shake', 'dilution_percentage' => 15]), 'method')
            ->has(Image::factory()->state([
                'copyright' => 'Exported copyright',
                'file_path' => 'tests/non_existing_image.jpg',
            ])->count(1))
            ->has(CocktailIngredient::factory()->for(Ingredient::factory()->for(IngredientCategory::factory()->create(['name' => 'spirits']), 'category')->for($membership->bar)->create([
                'slug' => 'gin-1',
                'name' => 'Gin',
                'strength' => 40.0,
                'description' => 'Test description',
                'origin' => 'London',
            ]))->state([
                'amount' => 45,
                'amount_max' => 60,
                'units' => 'ml',
                'note' => 'Ingredient note',
                'optional' => false,
            ]), 'ingredients')
            ->create([
                'source' => 'https://barassistant.app',
                'created_at' => Carbon::parse('2020-01-01 12:00:00'),
                'updated_at' => Carbon::parse('2021-01-01 12:00:00'),
                'name' => 'Gin and Tonic',
                'description' => 'Cocktail description goes here',
                'instructions' => 'Cocktail instructions go here',
                'garnish' => 'Straw and lemon wheel',
                'slug' => 'test-cocktail-1',
                'abv' => 32.3,
            ]);
    }
}
