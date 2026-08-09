<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Menu;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\MenuCategory;
use Kami\Cocktail\Models\MenuCocktail;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\MenuIngredient;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;
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
        $menuCategoryCocktails = MenuCategory::factory()->for($menu)->create(['sort' => 1]);
        $menuCategoryIngredients = MenuCategory::factory()->for($menu)->create(['sort' => 2]);
        MenuCocktail::factory()->for($menuCategoryCocktails)->count(3)->create();
        MenuIngredient::factory()->for($menuCategoryIngredients)->count(7)->create();

        $response = $this->getJson('/api/menu', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->has('data.categories', 2)
                ->has('data.categories.0.items', 3)
                ->has('data.categories.1.items', 7)
                ->etc()
        );
    }

    public function test_update_menu(): void
    {
        $cocktail = Cocktail::factory()->for($this->barMembership->bar)->create();
        $ingredient = Ingredient::factory()->for($this->barMembership->bar)->create();
        Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);

        $response = $this->postJson('/api/menu', [
            'is_enabled' => true,
            'categories' => [
                [
                    'sort' => 1,
                    'name' => '1 category',
                    'items' => [
                        [
                            'id' => $cocktail->id,
                            'type' => MenuItemTypeEnum::Cocktail->value,
                            'sort' => 1,
                            'price' => 200,
                            'currency' => 'EUR',
                            'is_bar_inventory_aware' => true,
                        ],
                        [
                            'id' => $ingredient->id,
                            'type' => MenuItemTypeEnum::Ingredient->value,
                            'sort' => 2,
                            'price' => 500,
                            'currency' => 'EUR',
                            'is_bar_inventory_aware' => true,
                        ],
                    ]
                ],
            ],
        ], ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertNoContent();
    }

    public function test_export_menu(): void
    {
        $menu = Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);
        MenuCocktail::factory()->recycle($menu)->count(5)->create();

        $response = $this->getJson('/api/menu/export', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_update_menu_with_disabled_category(): void
    {
        $cocktail = Cocktail::factory()->for($this->barMembership->bar)->create();
        Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);

        $response = $this->postJson('/api/menu', [
            'is_enabled' => true,
            'categories' => [
                [
                    'sort' => 1,
                    'name' => 'Visible Category',
                    'is_enabled' => true,
                    'items' => [
                        [
                            'id' => $cocktail->id,
                            'type' => MenuItemTypeEnum::Cocktail->value,
                            'sort' => 1,
                            'price' => 200,
                            'currency' => 'EUR',
                            'is_bar_inventory_aware' => true,
                        ],
                    ],
                ],
                [
                    'sort' => 2,
                    'name' => 'Hidden Category',
                    'is_enabled' => false,
                    'items' => [],
                ],
            ],
        ], ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertNoContent();

        $this->assertDatabaseHas('menu_categories', [
            'name' => 'Visible Category',
            'is_enabled' => true,
        ]);
        $this->assertDatabaseHas('menu_categories', [
            'name' => 'Hidden Category',
            'is_enabled' => false,
        ]);
    }

    public function test_show_menu_includes_is_enabled_in_category_response(): void
    {
        $menu = Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);
        MenuCategory::factory()->for($menu)->create([
            'sort' => 1,
            'name' => 'Visible',
            'is_enabled' => true,
        ]);
        MenuCategory::factory()->for($menu)->create([
            'sort' => 2,
            'name' => 'Hidden',
            'is_enabled' => false,
        ]);

        $response = $this->getJson('/api/menu', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.categories', 2)
                ->has(
                    'data.categories.0',
                    fn (AssertableJson $json) =>
                    $json->where('name', 'Visible')
                        ->where('is_enabled', true)
                        ->etc()
                )
                ->has(
                    'data.categories.1',
                    fn (AssertableJson $json) =>
                    $json->where('name', 'Hidden')
                        ->where('is_enabled', false)
                        ->etc()
                )
                ->etc()
        );
    }

    public function test_public_menu_excludes_hidden_categories(): void
    {
        $bar = $this->barMembership->bar;
        $bar->slug = 'test-public-bar';
        $bar->save();

        $menu = Menu::factory()->for($bar)->create(['is_enabled' => true]);
        MenuCategory::factory()->for($menu)->create([
            'sort' => 1,
            'name' => 'Visible',
            'is_enabled' => true,
        ]);
        MenuCategory::factory()->for($menu)->create([
            'sort' => 2,
            'name' => 'Hidden',
            'is_enabled' => false,
        ]);

        $response = $this->getJson('/api/public/bars/test-public-bar/menu');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.categories', 1)
                ->has(
                    'data.categories.0',
                    fn (AssertableJson $json) =>
                    $json->where('name', 'Visible')
                        ->etc()
                )
                ->etc()
        );
    }

    public function test_public_menu_filters_unavailable_inventory_items(): void
    {
        $bar = $this->barMembership->bar;
        $bar->slug = 'test-public-bar-inventory';
        $bar->save();

        $menu = Menu::factory()->for($bar)->create(['is_enabled' => true]);
        $category = MenuCategory::factory()->for($menu)->create([
            'sort' => 1,
            'name' => 'Category',
            'is_enabled' => true,
        ]);

        $inShelfIngredient = Ingredient::factory()->recycle($bar)->create(['name' => 'Gin']);
        $missingIngredient = Ingredient::factory()->recycle($bar)->create(['name' => 'Vermouth']);
        BarIngredient::factory()->for($bar)->for($inShelfIngredient)->create();

        $availableCocktail = Cocktail::factory()->recycle($bar, $this->barMembership->user)->create(['name' => 'Gin Tonic']);
        CocktailIngredient::factory()->for($availableCocktail)->for($inShelfIngredient)->create(['optional' => false]);

        $unavailableCocktail = Cocktail::factory()->recycle($bar, $this->barMembership->user)->create(['name' => 'Martini']);
        CocktailIngredient::factory()->for($unavailableCocktail)->for($missingIngredient)->create(['optional' => false]);

        MenuCocktail::factory()->for($category)->for($availableCocktail)->create([
            'sort' => 1,
            'is_bar_inventory_aware' => true,
        ]);
        MenuCocktail::factory()->for($category)->for($unavailableCocktail)->create([
            'sort' => 2,
            'is_bar_inventory_aware' => true,
        ]);

        MenuIngredient::factory()->for($category)->for($inShelfIngredient)->create([
            'sort' => 3,
            'is_bar_inventory_aware' => true,
        ]);
        MenuIngredient::factory()->for($category)->for($missingIngredient)->create([
            'sort' => 4,
            'is_bar_inventory_aware' => true,
        ]);

        $response = $this->getJson('/api/public/bars/test-public-bar-inventory/menu');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.categories', 1)
                ->has(
                    'data.categories.0',
                    fn (AssertableJson $json) =>
                    $json->where('name', 'Category')
                        ->has('items', 2)
                        ->where('items.0.name', 'Gin Tonic')
                        ->where('items.1.name', 'Gin')
                        ->etc()
                )
                ->etc()
        );
    }

    public function test_public_menu_keeps_non_inventory_aware_items_regardless_of_shelf_status(): void
    {
        $bar = $this->barMembership->bar;
        $bar->slug = 'test-public-bar-non-aware';
        $bar->save();

        $menu = Menu::factory()->for($bar)->create(['is_enabled' => true]);
        $category = MenuCategory::factory()->for($menu)->create([
            'sort' => 1,
            'name' => 'Category',
            'is_enabled' => true,
        ]);

        $missingIngredient = Ingredient::factory()->recycle($bar)->create(['name' => 'Missing']);

        $unavailableCocktail = Cocktail::factory()->recycle($bar, $this->barMembership->user)->create(['name' => 'Missing Cocktail']);
        CocktailIngredient::factory()->for($unavailableCocktail)->for($missingIngredient)->create(['optional' => false]);

        MenuCocktail::factory()->for($category)->for($unavailableCocktail)->create([
            'sort' => 1,
            'is_bar_inventory_aware' => false,
        ]);
        MenuIngredient::factory()->for($category)->for($missingIngredient)->create([
            'sort' => 2,
            'is_bar_inventory_aware' => false,
        ]);

        $response = $this->getJson('/api/public/bars/test-public-bar-non-aware/menu');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.categories', 1)
                ->has(
                    'data.categories.0',
                    fn (AssertableJson $json) =>
                    $json->where('name', 'Category')
                        ->has('items', 2)
                        ->where('items.0.name', 'Missing Cocktail')
                        ->where('items.1.name', 'Missing')
                        ->etc()
                )
                ->etc()
        );
    }
}
