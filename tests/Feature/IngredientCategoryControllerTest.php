<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IngredientCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_list_categories_response()
    {
        $this->setupBar();
        IngredientCategory::factory()->count(10)->create(['bar_id' => 1]);

        $response = $this->getJson('/api/ingredient-categories?bar_id=1');

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_show_category_response()
    {
        $bar = $this->setupBar();
        $cat = IngredientCategory::factory()->create([
            'name' => 'Test cat',
            'description' => 'Test cat desc',
            'bar_id' => $bar->id,
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

    public function test_create_category_response()
    {
        $this->setupBar();
        $response = $this->postJson('/api/ingredient-categories?bar_id=1', [
            'name' => 'Test cat',
            'description' => 'Test cat desc',
        ]);

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

    public function test_update_category_response()
    {
        $bar = $this->setupBar();
        $cat = IngredientCategory::factory()->create([
            'name' => 'Start cat',
            'description' => 'Start cat desc',
            'created_user_id' => auth()->user()->id,
            'bar_id' => $bar->id,
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

    public function test_delete_category_response()
    {
        $bar = $this->setupBar();
        $cat = IngredientCategory::factory()->create([
            'name' => 'Start cat',
            'description' => 'Start cat desc',
            'created_user_id' => auth()->user()->id,
            'bar_id' => $bar->id,
        ]);

        $response = $this->delete('/api/ingredient-categories/' . $cat->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('ingredient_categories', ['id' => $cat->id]);
    }
}
