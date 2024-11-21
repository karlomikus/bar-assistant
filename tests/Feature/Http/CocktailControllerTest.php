<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Utensil;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Models\PriceCategory;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Models\IngredientPrice;
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

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_cocktails_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        Cocktail::factory()
            ->recycle($membership->bar, $membership->user)
            ->count(55)
            ->create();

        $response = $this->getJson('/api/cocktails', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertStatus(200);
        $response->assertJsonCount(25, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 3);
        $response->assertJsonPath('meta.per_page', 25);
        $response->assertJsonPath('meta.total', 55);

        $response = $this->getJson('/api/cocktails?page=2', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);
        $response->assertJsonPath('meta.current_page', 2);

        $response = $this->getJson('/api/cocktails?per_page=5', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);
        $response->assertJsonPath('meta.last_page', 11);
    }

    public function test_cocktails_response_with_filters(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $user = User::factory()->create();

        Cocktail::factory()
            ->recycle($membership->bar)
            ->createMany([
                ['name' => 'Old Fashioned', 'abv' => 10],
                ['name' => 'XXXX', 'abv' => 10],
                ['name' => 'Test', 'created_user_id' => $user->id, 'abv' => 10],
                ['name' => 'public', 'public_id' => 'UUID', 'abv' => 10],
                ['name' => 'Дикая вишня', 'abv' => 10, 'slug' => Str::slug('Дикая вишня')],
                ['name' => 'Army & Navy', 'abv' => 10],
            ]);
        Cocktail::factory()->recycle($membership->bar)->hasTags(1)->create(['name' => 'test 1', 'abv' => 10]);
        $cocktail1 = Cocktail::factory()->recycle($membership->bar)->has(
            CocktailIngredient::factory()->for(
                Ingredient::factory()->state(['name' => 'absinthe'])->create()
            ),
            'ingredients'
        )->create([
            'name' => 'a test',
            'abv' => 33.3,
        ]);
        $cocktailFavorited = Cocktail::factory()->recycle($membership->bar)->create(['abv' => 10]);
        CocktailFavorite::factory()->recycle($cocktailFavorited, $membership)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->getJson('/api/cocktails?filter[name]=old');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[name]=old,xx');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/cocktails?filter[tag_id]=1');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[created_user_id]=' . $user->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[on_shelf]=true');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[favorites]=true');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[is_public]=true');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[ingredient_name]=absinthe');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[id]=1,2');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/cocktails?filter[ingredient_id]=1');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[abv_min]=30');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[abv_min]=34');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[abv_max]=30');
        $response->assertJsonCount(8, 'data');
        $response = $this->getJson('/api/cocktails?filter[abv_max]=50');
        $response->assertJsonCount(9, 'data');
        $response = $this->getJson('/api/cocktails?filter[name]=Дикая');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[name]=army');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[user_rating_min]=1');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[user_rating_max]=5');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[average_rating_min]=1');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[average_rating_max]=5');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[main_ingredient_id]=' . $cocktail1->ingredients->first()->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[total_ingredients]=1');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[missing_ingredients]=4');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[missing_ingredients]=1');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[shelf_ingredients]=9999');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[bar_shelf]=true');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[collection_id]=3331');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?filter[specific_ingredients]=' . $cocktail1->ingredients->first()->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?filter[ignore_ingredients]=' . $cocktail1->ingredients->first()->id);
        $response->assertJsonCount(8, 'data');
    }

    public function test_cocktails_response_with_sorts(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        Cocktail::factory()->recycle($membership->bar)->createMany([
            ['name' => 'B Cocktail'],
            ['name' => 'A Cocktail'],
            ['name' => 'C Cocktail'],
        ]);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->getJson('/api/cocktails?sort=name');
        $response->assertJsonPath('data.0.name', 'A Cocktail');
        $response->assertJsonPath('data.1.name', 'B Cocktail');
        $response->assertJsonPath('data.2.name', 'C Cocktail');

        $response = $this->getJson('/api/cocktails?sort=-name');
        $response->assertJsonPath('data.0.name', 'C Cocktail');
        $response->assertJsonPath('data.1.name', 'B Cocktail');
        $response->assertJsonPath('data.2.name', 'A Cocktail');
    }

    public function test_cocktail_show_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $glass = Glass::factory()->recycle($membership->bar)->create();
        $method = CocktailMethod::factory()->recycle($membership->bar)->create();
        $cocktail = Cocktail::factory()
            ->recycle($membership->bar, $membership->user)
            ->has(CocktailIngredient::factory()->state([
                'amount' => 45,
                'amount_max' => 60,
                'units' => 'ml',
                'optional' => false,
            ])->count(1), 'ingredients')
            ->hasRatings(1, [
                'rating' => 4,
                'user_id' => $membership->user_id
            ])
            ->hasRatings(1, [
                'rating' => 1,
                'user_id' => User::factory()->create()->id,
            ])
            ->for($glass)
            ->for($method, 'method')
            ->has(Utensil::factory()->count(5))
            ->has(Image::factory()->count(2))
            ->hasTags(5)
            ->create([
                'name' => 'A cocktail name',
                'slug' => 'a-cocktail-name-1',
                'instructions' => "1. Step 1\n2. Step two",
                'garnish' => '# Lemon twist',
                'description' => 'A short description',
                'source' => 'http://test.com',
            ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('data.id', $cocktail->id)
                ->where('data.name', 'A cocktail name')
                ->where('data.slug', 'a-cocktail-name-1')
                ->where('data.instructions', "1. Step 1\n2. Step two")
                ->where('data.garnish', '# Lemon twist')
                ->where('data.description', 'A short description')
                ->where('data.source', 'http://test.com')
                ->where('data.public_id', null)
                ->where('data.public_at', null)
                ->has('data.images', 2)
                ->hasAll(['data.created_at', 'data.updated_at', 'data.calories', 'data.alcohol_units', 'data.volume_ml'])
                ->has('data.tags', 5)
                ->has('data.utensils', 5)
                ->where('data.created_user.name', $membership->user->name)
                ->has('data.updated_user')
                ->where('data.rating.user', 4)
                ->where('data.rating.average', 3)
                ->where('data.rating.total_votes', 2)
                ->where('data.glass.id', $glass->id)
                ->where('data.method.id', $method->id)
                ->where('data.in_shelf', false)
                ->has('data.abv')
                ->has('data.ingredients', 1, function (AssertableJson $jsonIng) {
                    $jsonIng
                        ->has('ingredient.id')
                        ->has('ingredient.name')
                        ->has('ingredient.slug')
                        ->where('substitutes', [])
                        ->where('amount', 45)
                        ->where('amount_max', 60)
                        ->where('units', 'ml')
                        ->where('optional', false)
                        ->etc();
                })
                ->etc()
        );
    }

    public function test_cocktail_show_using_slug_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $response = $this->getJson('/api/cocktails/' . $cocktail->slug);

        $response->assertStatus(200);
    }

    public function test_cocktail_create_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $gin = Ingredient::factory()
            ->for($membership->bar)
            ->create([
                'name' => 'Gin',
                'strength' => 40,
            ]);
        $ing2 = Ingredient::factory()->for($membership->bar)->create();
        $ing3 = Ingredient::factory()->for($membership->bar)->create();
        $method = CocktailMethod::factory()->for($membership->bar)->create();
        $glass = Glass::factory()->for($membership->bar)->create();
        $image = Image::factory()->create();
        Utensil::factory()->for($membership->bar)->count(5)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

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
            'utensils' => [2, 5, 3],
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
                    'substitutes' => [
                        ['ingredient_id' => $ing3->id]
                    ]
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
                ->where('data.slug', 'cocktail-name-1')
                ->where('data.name', 'Cocktail name')
                ->where('data.description', 'Cocktail description')
                ->where('data.garnish', 'Lemon peel')
                ->where('data.public_id', null)
                ->where('data.source', 'https://karlomikus.com')
                ->where('data.method.id', $method->id)
                ->where('data.glass.id', $glass->id)

                ->where('data.ingredients.0.ingredient.id', $gin->id)
                ->where('data.ingredients.0.amount', 30)
                ->where('data.ingredients.0.units', 'ml')
                ->where('data.ingredients.0.optional', false)
                ->where('data.ingredients.0.sort', 1)
                ->has('data.ingredients.0.substitutes', 0)

                ->where('data.ingredients.1.ingredient.id', $ing2->id)
                ->where('data.ingredients.1.amount', 45)
                ->where('data.ingredients.1.units', 'ml')
                ->where('data.ingredients.1.optional', false)
                ->where('data.ingredients.1.sort', 2)
                ->has('data.ingredients.1.substitutes', 1)

                ->has('data.images', 1)
                ->has('data.tags', 2)
                ->has('data.ingredients', 2)
                ->has('data.utensils', 3)
                ->etc()
        );
    }

    public function test_cocktail_update_response(): void
    {
        $this->setupBar();

        $cocktail = Cocktail::factory()->create(['bar_id' => 1, 'created_user_id' => 1]);
        Utensil::factory()->count(5)->create(['bar_id' => 1]);

        $gin = Ingredient::factory()
            ->state([
                'name' => 'Gin',
                'strength' => 40,
            ])
            ->create(['bar_id' => 1]);

        $response = $this->putJson('/api/cocktails/' . $cocktail->id, [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step",
            'description' => "Cocktail description",
            'garnish' => "Lemon peel",
            'source' => "https://karlomikus.com",
            'images' => [],
            'tags' => ['Test', 'Gin'],
            'utensils' => [2, 1],
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
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('data.id', $cocktail->id)
                ->where('data.slug', 'cocktail-name-1')
                ->where('data.name', 'Cocktail name')
                ->has('data.utensils', 2)
                ->etc()
        );
    }

    public function test_cocktail_delete_response(): void
    {
        $this->setupBar();

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth()->user()->id, 'bar_id' => 1]);

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id);

        $response->assertNoContent();
    }

    public function test_cocktail_delete_deletes_images_response(): void
    {
        $this->setupBar();

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth()->user()->id, 'bar_id' => 1]);
        $storage = Storage::fake('uploads');
        $imageFile = UploadedFile::fake()->createWithContent('image1.jpg', $this->getFakeImageContent('jpg'));
        $image = Image::factory()->for($cocktail, 'imageable')->create([
            'file_path' => $imageFile->storeAs('temp', 'image1.jpg', 'uploads'),
            'file_extension' => $imageFile->extension(),
            'copyright' => 'initial',
            'sort' => 7,
            'created_user_id' => auth()->user()->id
        ]);

        $this->assertTrue($storage->exists($image->file_path));

        $this->deleteJson('/api/cocktails/' . $cocktail->id);

        $this->assertFalse($storage->exists($image->file_path));
    }

    public function test_make_cocktail_public_link_response(): void
    {
        $this->setupBar();

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth()->user()->id, 'bar_id' => 1]);

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/public-link');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.public_id')
                ->has('data.public_at')
        );

        $cocktail = Cocktail::find($cocktail->id);
        $this->assertNotNull($cocktail->public_id);
    }

    public function test_delete_cocktail_public_link_response(): void
    {
        $this->setupBar();

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth()->user()->id, 'bar_id' => 1]);

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id . '/public-link');

        $response->assertNoContent();

        $cocktail = Cocktail::find($cocktail->id);
        $this->assertNull($cocktail->public_id);
    }

    public function test_cocktail_share_response(): void
    {
        $this->setupBar();

        $cocktail = Cocktail::factory()
            ->has(CocktailIngredient::factory()->count(3), 'ingredients')
            ->create([
                'name' => 'A cocktail name',
                'instructions' => "1. Step 1\n2. Step two",
                'garnish' => '# Lemon twist',
                'description' => 'A short description',
                'source' => 'http://test.com',
                'created_user_id' => auth()->user()->id,
                'bar_id' => 1,
            ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/share');
        $response->assertStatus(200);
        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/share?type=json-ld');
        $response->assertStatus(200);
        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/share?type=yml');
        $response->assertStatus(200);
        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/share?type=yaml');
        $response->assertStatus(200);
        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/share?type=xml');
        $response->assertStatus(200);
        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/share?type=markdown');
        $response->assertStatus(200);
        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/share?type=md');
        $response->assertStatus(200);
    }

    public function test_cocktail_share_forbidden_response(): void
    {
        $user = User::factory()->create();
        $bar = Bar::factory()->create(['created_user_id' => $user->id]);

        $cocktail = Cocktail::factory()
            ->create([
                'name' => 'A cocktail name',
                'instructions' => "1. Step 1\n2. Step two",
                'created_user_id' => $user->id,
                'bar_id' => $bar->id,
            ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/share');

        $response->assertForbidden();
    }

    public function test_token_read_abilities(): void
    {
        $user = User::factory()->create();
        $this->actingAs(
            $user,
            abilities: ['cocktails.write']
        );
        $this->setupBar();

        $response = $this->getJson('/api/cocktails?bar_id=1');
        $response->assertForbidden();

        $this->actingAs(
            $user,
            abilities: ['cocktails.read']
        );

        $response = $this->getJson('/api/cocktails?bar_id=1');
        $response->assertOk();
    }

    public function test_token_write_abilities(): void
    {
        $user = User::factory()->create();
        $this->actingAs(
            $user,
            abilities: ['cocktails.read']
        );
        $this->setupBar();

        $response = $this->postJson('/api/cocktails?bar_id=1', []);
        $response->assertForbidden();

        $this->actingAs(
            $user,
            abilities: ['cocktails.write']
        );

        $response = $this->postJson('/api/cocktails?bar_id=1', ['name' => 'Test', 'instructions' => 'Test']);
        $response->assertCreated();
    }

    public function test_cocktail_creation_fails_with_unowned_bar_ingredients(): void
    {
        $this->setupBar();
        $user2 = User::factory()->create();
        $bar2 = Bar::factory()->create(['created_user_id' => $user2->id]);

        $ingredientFromAnotherBar = Ingredient::factory()->create(['bar_id' => $bar2->id]);

        $response = $this->postJson('/api/cocktails?bar_id=1', [
            'name' => "Cocktail name",
            'instructions' => "Test",
            'description' => null,
            'garnish' => null,
            'source' => null,
            'cocktail_method_id' => null,
            'glass_id' => null,
            'images' => [],
            'tags' => [],
            'utensils' => [],
            'ingredients' => [
                [
                    'ingredient_id' => $ingredientFromAnotherBar->id,
                    'amount' => 30,
                    'units' => 'ml',
                    'optional' => false,
                    'sort' => 1,
                ],
            ]
        ]);

        $response->assertStatus(422);
    }

    public function test_cocktail_update_fails_with_unowned_bar_ingredients(): void
    {
        $this->setupBar();
        $user2 = User::factory()->create();
        $bar2 = Bar::factory()->create(['created_user_id' => $user2->id]);
        $cocktail = Cocktail::factory()->create(['bar_id' => 1, 'created_user_id' => 1]);
        $ingredientFromAnotherBar = Ingredient::factory()->create(['bar_id' => $bar2->id]);

        $response = $this->putJson('/api/cocktails/' . $cocktail->id, [
            'name' => "Cocktail name",
            'instructions' => "Test",
            'description' => null,
            'garnish' => null,
            'source' => null,
            'cocktail_method_id' => null,
            'glass_id' => null,
            'images' => [],
            'tags' => [],
            'utensils' => [],
            'ingredients' => [
                [
                    'ingredient_id' => $ingredientFromAnotherBar->id,
                    'amount' => 30,
                    'units' => 'ml',
                    'optional' => false,
                    'sort' => 1,
                ],
            ]
        ]);

        $response->assertStatus(422);
    }

    public function test_cocktail_copy(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Cocktail name']);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/copy');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->whereNot('data.id', $cocktail->id)
                ->whereNot('data.slug', $cocktail->slug)
                ->whereNot('data.created_at', $cocktail->created_at)
                ->where('data.name', 'Cocktail name Copy')
                ->etc()
        );
    }

    public function test_toggle_favorite(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/toggle-favorite');

        $response->assertSuccessful();
        $favorite = CocktailFavorite::where('cocktail_id', $cocktail->id)->first();
        $this->assertNotNull($favorite);

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/toggle-favorite');
        $response->assertSuccessful();
        $favorite = CocktailFavorite::where('cocktail_id', $cocktail->id)->first();
        $this->assertNull($favorite);
    }

    public function test_cocktail_has_multiple_ingredient_formats(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredientGin = Ingredient::factory()->for($membership->bar)->create([
            'name' => 'Gin'
        ]);

        $ingredientMint = Ingredient::factory()->for($membership->bar)->create([
            'name' => 'Mint'
        ]);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        CocktailIngredient::factory()->for($cocktail)->for($ingredientGin)->create([
            'amount' => 1.5,
            'amount_max' => 2,
            'units' => 'oz',
            'optional' => true,
        ]);
        CocktailIngredient::factory()->for($cocktail)->for($ingredientMint)->create([
            'amount' => 7,
            'amount_max' => null,
            'units' => 'leaves',
            'optional' => false,
        ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id);

        $response->assertStatus(200);

        // Convert convertable
        $response->assertJsonPath('data.ingredients.0.formatted.ml.amount', 45);
        $response->assertJsonPath('data.ingredients.0.formatted.ml.amount_max', 60);
        $response->assertJsonPath('data.ingredients.0.formatted.ml.units', 'ml');
        $response->assertJsonPath('data.ingredients.0.formatted.ml.full_text', '45 ml - 60 ml Gin (optional)');

        $response->assertJsonPath('data.ingredients.0.formatted.oz.amount', 1.5);
        $response->assertJsonPath('data.ingredients.0.formatted.oz.amount_max', 2);
        $response->assertJsonPath('data.ingredients.0.formatted.oz.units', 'oz');
        $response->assertJsonPath('data.ingredients.0.formatted.oz.full_text', '1.5 oz - 2 oz Gin (optional)');

        $response->assertJsonPath('data.ingredients.0.formatted.cl.amount', 4.5);
        $response->assertJsonPath('data.ingredients.0.formatted.cl.amount_max', 6);
        $response->assertJsonPath('data.ingredients.0.formatted.cl.units', 'cl');
        $response->assertJsonPath('data.ingredients.0.formatted.cl.full_text', '4.5 cl - 6 cl Gin (optional)');

        // Dont convert unconvertable
        $response->assertJsonPath('data.ingredients.1.formatted.ml.amount', 7);
        $response->assertJsonPath('data.ingredients.1.formatted.ml.amount_max', null);
        $response->assertJsonPath('data.ingredients.1.formatted.ml.units', 'leaves');
        $response->assertJsonPath('data.ingredients.1.formatted.ml.full_text', '7 leaves Mint');

        $response->assertJsonPath('data.ingredients.1.formatted.oz.amount', 7);
        $response->assertJsonPath('data.ingredients.1.formatted.oz.amount_max', null);
        $response->assertJsonPath('data.ingredients.1.formatted.oz.units', 'leaves');
        $response->assertJsonPath('data.ingredients.1.formatted.oz.full_text', '7 leaves Mint');

        $response->assertJsonPath('data.ingredients.1.formatted.cl.amount', 7);
        $response->assertJsonPath('data.ingredients.1.formatted.cl.amount_max', null);
        $response->assertJsonPath('data.ingredients.1.formatted.cl.units', 'leaves');
        $response->assertJsonPath('data.ingredients.1.formatted.cl.full_text', '7 leaves Mint');
    }

    public function test_cocktail_prices(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $priceCategory = PriceCategory::factory()->for($membership->bar)->create([
            'currency' => 'USD'
        ]);

        $ingredient1 = Ingredient::factory()->for($membership->bar)->create();
        IngredientPrice::factory()->for($ingredient1)->for($priceCategory)->create([
            'price' => 2000,
            'amount' => 750,
            'units' => 'ml',
        ]);

        $ingredient2 = Ingredient::factory()->for($membership->bar)->create();
        IngredientPrice::factory()->for($ingredient2)->for($priceCategory)->create([
            'price' => 2000,
            'amount' => 25,
            'units' => 'oz',
        ]);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        CocktailIngredient::factory()->for($cocktail)->for($ingredient1)->create([
            'amount' => 30,
            'amount_max' => null,
            'units' => 'ml',
            'optional' => false,
        ]);
        CocktailIngredient::factory()->for($cocktail)->for($ingredient2)->create([
            'amount' => 1,
            'amount_max' => null,
            'units' => 'oz',
            'optional' => false,
        ]);
        CocktailIngredient::factory()->for($cocktail)->for($ingredient1)->create([
            'amount' => 0.5,
            'amount_max' => null,
            'units' => 'oz',
            'optional' => false,
        ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/prices');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.prices_per_ingredient.0.price_per_unit.price', 0.03);
        $response->assertJsonPath('data.0.prices_per_ingredient.0.price_per_use.price', 0.9);
        $response->assertJsonPath('data.0.prices_per_ingredient.1.price_per_unit.price', 0.8);
        $response->assertJsonPath('data.0.prices_per_ingredient.1.price_per_use.price', 0.8);
        $response->assertJsonPath('data.0.prices_per_ingredient.2.price_per_unit.price', 0.8);
        $response->assertJsonPath('data.0.prices_per_ingredient.2.price_per_use.price', 0.4);
        $response->assertJsonPath('data.0.total_price.price', 2.1);
    }
}
