<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Menu;
use Kami\Cocktail\Models\MenuCocktail;
use Kami\Cocktail\Models\BarMembership;
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
        MenuCocktail::factory()->recycle($menu)->count(2)->create();

        $response = $this->getJson('/api/menu', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->has('data.categories', 2)
                ->where('data.is_enabled', true)
                ->etc()
        );
    }

    public function test_update_menu(): void
    {
        Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);

        $response = $this->postJson('/api/menu', [
            'is_enabled' => true,
            'cocktails' => [
                [
                    'cocktail_id' => 1,
                    'price' => 20,
                    'category_name' => 'Test 1',
                    'sort' => '1',
                    'currency' => 'EUR',
                ],
                [
                    'cocktail_id' => 2,
                    'price' => 10,
                    'category_name' => 'Test 1',
                    'sort' => '2',
                    'currency' => 'USD',
                ],
            ],
        ], ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
    }

    public function test_export_menu(): void
    {
        $menu = Menu::factory()->for($this->barMembership->bar)->create(['is_enabled' => true]);
        MenuCocktail::factory()->recycle($menu)->count(2)->create();

        $response = $this->getJson('/api/menu/export', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertSuccessful();
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
}
