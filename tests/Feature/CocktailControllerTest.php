<?php

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Models\CocktailFavorite;
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

    public function test_cocktails_response()
    {
        Cocktail::factory()->count(45)->create();

        $response = $this->getJson('/api/cocktails');

        $response->assertStatus(200);
        $response->assertJsonCount(15, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 3);
        $response->assertJsonPath('meta.per_page', 15);
        $response->assertJsonPath('meta.total', 45);

        $response = $this->getJson('/api/cocktails?page=2');
        $response->assertJsonPath('meta.current_page', 2);

        $response = $this->getJson('/api/cocktails?per_page=5');
        $response->assertJsonPath('meta.last_page', 9);
    }

    public function test_cocktails_response_filters()
    {
        $user = User::factory()->create();
        Cocktail::factory()->createMany([
            ['name' => 'Old Fashioned'],
            ['name' => 'XXXX'],
            ['name' => 'Test', 'user_id' => $user->id],
            ['name' => 'public', 'public_id' => 'UUID'],
        ]);
        Cocktail::factory()->hasTags(1)->create(['name' => 'test 1']);
        $cocktailFavorited = Cocktail::factory()->create();

        $favorite = new CocktailFavorite();
        $favorite->cocktail_id = $cocktailFavorited->id;
        auth()->user()->favorites()->save($favorite);

        $response = $this->getJson('/api/cocktails?filter[name]=old');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[name]=old,xx');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/cocktails?filter[tag_id]=1');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[user_id]=' . $user->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[on_shelf]=true');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[favorites]=true');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[is_public]=true');
        $response->assertJsonCount(1, 'data');
    }

    public function test_cocktails_response_sorts()
    {
        Cocktail::factory()->createMany([
            ['name' => 'B Cocktail'],
            ['name' => 'A Cocktail'],
            ['name' => 'C Cocktail'],
        ]);

        $response = $this->getJson('/api/cocktails?sort=name');
        $response->assertJsonPath('data.0.name', 'A Cocktail');
        $response->assertJsonPath('data.1.name', 'B Cocktail');
        $response->assertJsonPath('data.2.name', 'C Cocktail');

        $response = $this->getJson('/api/cocktails?sort=-name');
        $response->assertJsonPath('data.0.name', 'C Cocktail');
        $response->assertJsonPath('data.1.name', 'B Cocktail');
        $response->assertJsonPath('data.2.name', 'A Cocktail');
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
                        ->has('ingredient_id')
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
        $method = CocktailMethod::factory()->create();
        $glass = Glass::factory()->create();
        $image = Image::factory()->create(['user_id' => auth()->user()->id]);

        $response = $this->postJson('/api/cocktails', [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step",
            'description' => "Cocktail description",
            'garnish' => "Lemon peel",
            'source' => "https://karlomikus.com",
            'cocktail_method_id' => $method->id,
            'glass_id' => $glass->id,
            'images' => [$image->id],
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
                    'amount' => 45,
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
                ->has('data.created_at')
                ->where('data.slug', 'cocktail-name')
                ->where('data.name', 'Cocktail name')
                ->where('data.description', 'Cocktail description')
                ->where('data.garnish', 'Lemon peel')
                ->where('data.has_public_link', false)
                ->where('data.public_id', null)
                ->where('data.main_image_id', $image->id)
                ->where('data.user_id', auth()->user()->id)
                ->where('data.user_rating', null)
                ->where('data.average_rating', 0)
                ->where('data.source', 'https://karlomikus.com')
                ->where('data.method.id', $method->id)
                ->where('data.glass.id', $glass->id)

                ->where('data.ingredients.0.ingredient_id', $gin->id)
                ->where('data.ingredients.0.amount', 30)
                ->where('data.ingredients.0.units', 'ml')
                ->where('data.ingredients.0.optional', false)
                ->where('data.ingredients.0.sort', 1)
                ->has('data.ingredients.0.substitutes', 0)

                ->where('data.ingredients.1.ingredient_id', $ing2->id)
                ->where('data.ingredients.1.amount', 45)
                ->where('data.ingredients.1.units', 'ml')
                ->where('data.ingredients.1.optional', false)
                ->where('data.ingredients.1.sort', 2)
                ->has('data.ingredients.1.substitutes', 1)

                ->has('data.images', 1)
                ->has('data.tags', 2)
                ->has('data.ingredients', 2)
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
