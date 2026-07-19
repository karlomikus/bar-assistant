<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Menu;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\MenuCategory;
use Kami\Cocktail\Models\MenuCocktail;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\MenuIngredient;
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

        $response = $this->getJson('/api/public/test-public-bar/menu');

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
}
