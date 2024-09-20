<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IngredientCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private BarMembership $barMembership;

    public function setUp(): void
    {
        parent::setUp();

        $this->barMembership = $this->setupBarMembership();
        $this->actingAs($this->barMembership->user);
    }

    public function test_list_categories_response(): void
    {
        IngredientCategory::factory()->recycle($this->barMembership->bar)->count(10)->create();

        $response = $this->getJson('/api/ingredient-categories', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_show_category_response(): void
    {
        $cat = IngredientCategory::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'Test cat',
            'description' => 'Test cat desc',
        ]);

        $response = $this->getJson('/api/ingredient-categories/' . $cat->id);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'Test cat')
                ->where('data.description', 'Test cat desc')
                ->etc()
        );
    }

    public function test_create_category_response(): void
    {
        $response = $this->postJson('/api/ingredient-categories', [
            'name' => 'Test cat',
            'description' => 'Test cat desc',
        ], ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertCreated();
        $this->assertNotEmpty($response->headers->get('Location'));
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'Test cat')
                ->where('data.description', 'Test cat desc')
                ->etc()
        );
    }

    public function test_update_category_response(): void
    {
        $cat = IngredientCategory::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'Start cat',
            'description' => 'Start cat desc',
        ]);

        $response = $this->putJson('/api/ingredient-categories/' . $cat->id, [
            'name' => 'Test cat',
            'description' => 'Test cat desc',
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $cat->id)
                ->where('data.name', 'Test cat')
                ->where('data.description', 'Test cat desc')
                ->etc()
        );
    }

    public function test_delete_category_response(): void
    {
        $cat = IngredientCategory::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'Start cat',
            'description' => 'Start cat desc',
        ]);

        $response = $this->delete('/api/ingredient-categories/' . $cat->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('ingredient_categories', ['id' => $cat->id]);
    }
}
