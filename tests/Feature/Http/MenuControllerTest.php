<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Menu;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\MenuCocktail;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\MenuIngredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MenuControllerTest extends TestCase
{
    use RefreshDatabase;

    private BarMembership $barMembership;

    public function setUp(): void
    {
        parent::setUp();

        $this->barMembership = $this->setupBarMembership();
        $this->actingAs($this->barMembership->user);
    }

    public function test_menu_gets_created_on_first_visit(): void
    {
        $bar = $this->barMembership->bar;
        $bar->slug = null;
        $bar->save();

        $response = $this->getJson('/api/menu', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
        $bar->refresh();
        $this->assertNotNull($bar->slug);
    }

    public function test_show_menu(): void
    {
        $menu = Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);
        MenuCocktail::factory()->recycle($menu)->count(7)->create(['category_name' => 'cocktails']);
        MenuIngredient::factory()->recycle($menu)->count(3)->create(['category_name' => 'ingredients']);

        $response = $this->getJson('/api/menu', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->has('data.categories', 2)
                ->has('data.categories.0.items', 7)
                ->has('data.categories.1.items', 3)
                ->where('data.is_enabled', true)
                ->etc()
        );
    }

    public function test_update_menu(): void
    {
        Cocktail::factory()->for($this->barMembership->bar)->create();
        Cocktail::factory()->for($this->barMembership->bar)->create();
        Ingredient::factory()->for($this->barMembership->bar)->create();
        Ingredient::factory()->for($this->barMembership->bar)->create();
        Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);

        $response = $this->postJson('/api/menu', [
            'is_enabled' => true,
            'items' => [
                [
                    'id' => 1,
                    'type' => 'cocktail',
                    'price' => 20.00,
                    'category_name' => 'Test 1',
                    'sort' => '1',
                    'currency' => 'EUR',
                ],
                [
                    'id' => 2,
                    'type' => 'cocktail',
                    'price' => 10.50,
                    'category_name' => 'Test 1',
                    'sort' => '2',
                    'currency' => 'USD',
                ],
                [
                    'id' => 1,
                    'type' => 'ingredient',
                    'price' => 12.99,
                    'category_name' => 'Test 1',
                    'sort' => '3',
                    'currency' => 'EUR',
                ],
                [
                    'id' => 2,
                    'type' => 'ingredient',
                    'price' => 1.32,
                    'category_name' => 'Test 2',
                    'sort' => '1',
                    'currency' => 'EUR',
                ],
            ],
        ], ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('menu_cocktails', [
            'menu_id' => $response->json('data.id'),
            'cocktail_id' => 1,
            'price' => 2000,
            'category_name' => 'Test 1',
            'sort' => '1',
            'currency' => 'EUR',
        ]);
        $this->assertDatabaseHas('menu_cocktails', [
            'menu_id' => $response->json('data.id'),
            'cocktail_id' => 2,
            'price' => 1050,
            'category_name' => 'Test 1',
            'sort' => '2',
            'currency' => 'USD',
        ]);
        $this->assertDatabaseHas('menu_ingredients', [
            'menu_id' => $response->json('data.id'),
            'ingredient_id' => 1,
            'price' => 1299,
            'category_name' => 'Test 1',
            'sort' => 3,
            'currency' => 'EUR',
        ]);
        $this->assertDatabaseHas('menu_ingredients', [
            'menu_id' => $response->json('data.id'),
            'ingredient_id' => 2,
            'price' => 132,
            'category_name' => 'Test 2',
            'sort' => 1,
            'currency' => 'EUR',
        ]);
    }

    public function test_export_menu(): void
    {
        $menu = Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);
        MenuCocktail::factory()->recycle($menu)->count(2)->create();

        $response = $this->getJson('/api/menu/export', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_menu_has_null_currency(): void
    {
        $menu = Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);
        MenuCocktail::factory()->recycle($menu)->create([
            'currency' => null,
        ]);

        $response = $this->getJson('/api/menu', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
    }

    public function test_generate_menu_from_shelf(): void
    {
        $ingredient1 = Ingredient::factory()->for($this->barMembership->bar)->create();
        $ingredient2 = Ingredient::factory()->for($this->barMembership->bar)->create();

        $cocktail = Cocktail::factory()->for($this->barMembership->bar)->create();


        $this->barMembership->bar->shelfIngredients()->createMany([
            ['ingredient_id' => $ingredient1->id],
            ['ingredient_id' => $ingredient2->id],
        ]);

        $response = $this->postJson('/api/menu/generate-from-shelf', [], [
            'Bar-Assistant-Bar-Id' => $this->barMembership->bar_id,
        ]);

        $response->assertSuccessful();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data.id')
                ->has('data.categories')
                ->where('data.is_enabled', false)
                ->etc()
        );

        $this->assertDatabaseHas('menu_cocktails', [
            'cocktail_id' => $cocktail->id,
            'menu_id' => $response->json('data.id'),
            'price' => 100,
            'currency' => 'EUR',
        ]);
    }
}
