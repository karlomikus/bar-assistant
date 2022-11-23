<?php

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
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
        $cocktail = Cocktail::factory()->create([
            'name' => 'Test 1'
        ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id);

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Test 1')
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
        $response->assertJson(fn (AssertableJson $json) =>
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

        $response->assertStatus(201);
        $this->assertNotNull($response->headers->get('Location', null));

        $response->assertValidRequest();
        $response->assertValidResponse(201);
    }

    public function test_cocktail_delete_response()
    {
        $cocktail = Cocktail::factory()->create();

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id);

        $response->assertStatus(200);

        $response->assertValidResponse(200);
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
}
