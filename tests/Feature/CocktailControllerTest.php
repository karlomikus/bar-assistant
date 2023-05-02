<?php

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CocktailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_cocktail_show_response()
    {
        $glass = Glass::factory()->create();
        $method = CocktailMethod::factory()->create();
        $cocktail = Cocktail::factory()
            ->has(CocktailIngredient::factory()->count(3), 'ingredients')
            ->hasRatings(1, [
                'rating' => 4,
                'user_id' => auth()->user()->id
            ])
            ->hasRatings(1, [
                'rating' => 1,
            ])
            ->for($glass)
            ->for($method, 'method')
            ->hasTags(5)
            ->create([
                'name' => 'A cocktail name',
                'instructions' => "1. Step 1\n2. Step two",
                'garnish' => '# Lemon twist',
                'description' => 'A short description',
                'source' => 'http://test.com',
                'user_id' => auth()->user()->id,
            ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'A cocktail name')
                ->where('data.slug', 'a-cocktail-name')
                ->where('data.instructions', "1. Step 1\n2. Step two")
                ->where('data.garnish', '# Lemon twist')
                ->where('data.description', 'A short description')
                ->where('data.source', 'http://test.com')
                ->where('data.has_public_link', false)
                ->where('data.public_id', null)
                ->where('data.main_image_id', null)
                ->where('data.images', [])
                ->has('data.tags', 5)
                ->where('data.user_id', auth()->user()->id)
                ->where('data.user_rating', 4)
                ->where('data.average_rating', 3)
                ->where('data.glass.id', $glass->id)
                ->where('data.method.id', $method->id)
                ->has('data.abv')
                ->has('data.main_ingredient_name')
                ->has('data.short_ingredients', 3)
                ->has('data.ingredients', 3, function (AssertableJson $jsonIng) {
                    $jsonIng
                        ->has('id')
                        ->where('amount', 60)
                        ->where('units', 'ml')
                        ->where('optional', false)
                        ->etc();
                })
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_cocktail_show_using_slug_response()
    {
        $cocktail = Cocktail::factory()->create();

        $response = $this->getJson('/api/cocktails/' . $cocktail->slug);

        $response->assertStatus(200);

        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_cocktail_create_response()
    {
        $gin = Ingredient::factory()
            ->state([
                'name' => 'Gin',
                'strength' => 40,
            ])
            ->create();
        $ing2 = Ingredient::factory()->create();
        $ing3 = Ingredient::factory()->create();

        $response = $this->postJson('/api/cocktails', [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step",
            'description' => "Cocktail description",
            'garnish' => "Lemon peel",
            'source' => "https://karlomikus.com",
            'images' => [],
            'tags' => ['Test', 'Gin'],
            'ingredients' => [
                [
                    'ingredient_id' => $gin->id,
                    'amount' => 30,
                    'units' => 'ml',
                    'optional' => false,
                    'sort' => 1,
                ],
                [
                    'ingredient_id' => $ing2->id,
                    'amount' => 30,
                    'units' => 'ml',
                    'optional' => false,
                    'sort' => 2,
                    'substitutes' => [$ing3->id]
                ]
            ]
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->headers->get('Location', null));
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Cocktail name')
                ->where('data.description', 'Cocktail description')
                ->where('data.garnish', 'Lemon peel')
                ->has('data.ingredients', 2, function (AssertableJson $jsonIng) {
                    $jsonIng
                        ->has('id')
                        ->etc();
                })
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse(201);
    }

    public function test_cocktail_update_response()
    {
        $cocktail = Cocktail::factory()->create();

        $gin = Ingredient::factory()
            ->state([
                'name' => 'Gin',
                'strength' => 40,
            ])
            ->create();

        $response = $this->putJson('/api/cocktails/' . $cocktail->id, [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step",
            'description' => "Cocktail description",
            'garnish' => "Lemon peel",
            'source' => "https://karlomikus.com",
            'images' => [],
            'tags' => ['Test', 'Gin'],
            'ingredients' => [
                [
                    'ingredient_id' => $gin->id,
                    'amount' => 30,
                    'units' => 'ml',
                    'optional' => false,
                    'sort' => 1,
                ]
            ]
        ]);

        $response->assertSuccessful();

        $response->assertValidRequest();
        $response->assertValidResponse(200);
    }

    public function test_cocktail_delete_response()
    {
        $cocktail = Cocktail::factory()->create();

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id);

        $response->assertNoContent();

        $response->assertValidResponse(204);
    }

    public function test_user_shelf_cocktails_response()
    {
        $response = $this->getJson('/api/cocktails/user-shelf');

        $response->assertStatus(200);

        $response->assertValidResponse(200);
    }

    public function test_user_favorites_cocktails_response()
    {
        $response = $this->getJson('/api/cocktails/user-favorites');

        $response->assertStatus(200);

        $response->assertValidResponse(200);
    }

    public function test_make_cocktail_public_link_response()
    {
        $cocktail = Cocktail::factory()->create();

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/public-link');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.public_id')
                ->has('data.public_at')
                ->has('data.public_expires_at')
        );

        $cocktail = Cocktail::find($cocktail->id);
        $this->assertNotNull($cocktail->public_id);
    }

    public function test_delete_cocktail_public_link_response()
    {
        $cocktail = Cocktail::factory()->create();

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id . '/public-link');

        $response->assertNoContent();

        $cocktail = Cocktail::find($cocktail->id);
        $this->assertNull($cocktail->public_id);
    }
}
