<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Ingredient::factory()->count(5)->create();
        Cocktail::factory()->count(6)->create();
        $user = User::factory()->create();

        $this->actingAs($user);
    }

    public function test_stats_response()
    {
        $response = $this->getJson('/api/stats');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('data.total_ingredients', 5)
                ->where('data.total_cocktails', 6)
                ->where('data.total_shelf_cocktails', 0)
                ->has('data.most_popular_ingredients', 0)
                ->has('data.top_rated_cocktails', 0)
                ->etc()
        );
    }
}
