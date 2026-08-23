<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Menu;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Laravel\Paddle\Subscription;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Utensil;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Config;
use Kami\Cocktail\Models\MenuCategory;
use Kami\Cocktail\Models\MenuCocktail;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\PriceCategory;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Models\IngredientPrice;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CocktailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('bar-assistant.enable_billing', false);

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
        $cocktailFavorited = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'nonan', 'abv' => 10]);
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
                'bar_membership_id' => $membership->id
            ])
            ->hasRatings(1, [
                'rating' => 1,
                'bar_membership_id' => BarMembership::factory()->create()->id,
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
                ->where('data.rating.average', 2.5)
                ->where('data.rating.total_votes', 2)
                ->where('data.glass.id', $glass->id)
                ->where('data.method.id', $method->id)
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
    }

    public function test_cocktail_create_requires_ingredient_amount_and_units(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->postJson('/api/cocktails', [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step",
            'ingredients' => [
                ['ingredient_id' => $ingredient->id, 'sort' => 1],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'ingredients.0.amount',
            'ingredients.0.units',
        ]);
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

        $response->assertNoContent();
    }

    public function test_cocktail_delete_response(): void
    {
        $this->setupBar();

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth('sanctum')->user()->id, 'bar_id' => 1]);

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id);

        $response->assertNoContent();
    }

    public function test_cocktail_delete_deletes_all_references_response(): void
    {
        $barMembership = $this->setupBarMembership();
        $this->actingAs($barMembership->user);

        $cocktail = Cocktail::factory()->create(['created_user_id' => $barMembership->user_id, 'bar_id' => $barMembership->bar_id]);
        $cocktail->rate(2, $barMembership->id);
        $cocktail->addNote('Test note', $barMembership->user_id);
        $storage = Storage::fake('uploads');
        $imageFile = UploadedFile::fake()->createWithContent('image1.jpg', $this->getFakeImageContent('jpg'));
        $image = Image::factory()->for($cocktail, 'imageable')->create([
            'file_path' => $imageFile->storeAs('temp', 'image1.jpg', 'uploads'),
            'file_extension' => $imageFile->extension(),
            'copyright' => 'initial',
            'sort' => 7,
            'created_user_id' => $barMembership->user_id
        ]);
        $menu = Menu::factory()->for($barMembership->bar)->create(['is_enabled' => true]);
        $menuCategory = MenuCategory::factory()->for($menu)->create();
        MenuCocktail::factory()->for($menuCategory)->for($cocktail)->create();

        $this->assertTrue($storage->exists($image->file_path));
        $this->assertDatabaseHas('images', ['id' => $image->id]);
        $this->assertDatabaseHas('ratings', ['rateable_id' => $cocktail->id]);
        $this->assertDatabaseHas('notes', ['noteable_id' => $cocktail->id]);
        $this->assertDatabaseHas('menu_cocktails', ['cocktail_id' => $cocktail->id]);

        $this->deleteJson('/api/cocktails/' . $cocktail->id);

        $this->assertFalse($storage->exists($image->file_path));
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        $this->assertDatabaseEmpty('ratings');
        $this->assertDatabaseEmpty('notes');
        $this->assertDatabaseEmpty('menu_cocktails');
    }

    public function test_make_cocktail_public_link_response(): void
    {
        $this->setupBar();

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth('sanctum')->user()->id, 'bar_id' => 1]);

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/public-link');

        $response->assertSuccessful();
        $response->assertHeader('Location');

        $cocktail = Cocktail::find($cocktail->id);
        $this->assertNotNull($cocktail->public_id);
    }

    public function test_delete_cocktail_public_link_response(): void
    {
        $this->setupBar();

        $cocktail = Cocktail::factory()->create([
            'created_user_id' => auth('sanctum')->user()->id,
            'bar_id' => 1,
            'public_id' => 'TEST-ID',
            'public_at' => now(),
        ]);

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
                'created_user_id' => auth('sanctum')->user()->id,
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
        $bar = $this->setupBar();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $bar->id);

        $response = $this->getJson('/api/cocktails');
        $response->assertForbidden();

        $this->actingAs(
            $user,
            abilities: ['cocktails.read']
        );

        $response = $this->getJson('/api/cocktails');
        $response->assertOk();
    }

    public function test_token_write_abilities(): void
    {
        $user = User::factory()->create();
        $this->actingAs(
            $user,
            abilities: ['cocktails.read']
        );
        $bar = $this->setupBar();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $bar->id);

        $response = $this->postJson('/api/cocktails', []);
        $response->assertForbidden();

        $this->actingAs(
            $user,
            abilities: ['cocktails.write']
        );

        $response = $this->postJson('/api/cocktails', ['name' => 'Test', 'instructions' => 'Test']);
        $response->assertCreated();
    }

    public function test_cocktail_creation_fails_with_unowned_bar_ingredients(): void
    {
        $bar = $this->setupBar();
        $user2 = User::factory()->create();
        $bar2 = Bar::factory()->create(['created_user_id' => $user2->id]);

        $ingredientFromAnotherBar = Ingredient::factory()->create(['bar_id' => $bar2->id]);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $bar->id);

        $response = $this->postJson('/api/cocktails', [
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

        $response->assertCreated();
        $response->assertHeader('Location');
    }

    public function test_cocktail_copy_creates_owned_image_from_catalog_media(): void
    {
        Storage::fake('catalog');
        Storage::fake('uploads');
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $cocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Catalog cocktail']);
        $catalogPath = 'catalog/2026.08.21/cocktails/catalog-cocktail/image.jpg';
        Storage::disk('catalog')->put($catalogPath, $this->getFakeImageContent('jpg'));
        Image::factory()->for($cocktail, 'imageable')->create([
            'file_path' => $catalogPath,
            'file_extension' => 'jpg',
            'disk' => 'catalog',
            'storage_origin' => 'catalog',
        ]);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $this->postJson('/api/cocktails/' . $cocktail->id . '/copy')->assertCreated();

        $copiedImage = Image::query()->where('imageable_type', Cocktail::class)->where('imageable_id', '!=', $cocktail->id)->firstOrFail();
        $this->assertSame('uploads', $copiedImage->disk);
        $this->assertSame('owned', $copiedImage->storage_origin);
        Storage::disk('uploads')->assertExists($copiedImage->file_path);
        Storage::disk('catalog')->assertExists($catalogPath);
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
        $response->assertJsonPath('data.0.prices_per_ingredient.0.price_per_unit.price', 0.02);
        $response->assertJsonPath('data.0.prices_per_ingredient.0.price_per_use.price', 0.8);
        $response->assertJsonPath('data.0.prices_per_ingredient.1.price_per_unit.price', 0.8);
        $response->assertJsonPath('data.0.prices_per_ingredient.1.price_per_use.price', 0.8);
        $response->assertJsonPath('data.0.prices_per_ingredient.2.price_per_unit.price', 0.8);
        $response->assertJsonPath('data.0.prices_per_ingredient.2.price_per_use.price', 0.4);
        $response->assertJsonPath('data.0.total_price.price', 2);
    }

    public function test_max_images_validation(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $images = Image::factory()->count(11)->create();

        $response = $this->postJson('/api/cocktails', [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step",
            'description' => "Cocktail description",
            'images' => $images->pluck('id')->toArray(),
        ]);
        $response->assertUnprocessable();

        $response = $this->postJson('/api/cocktails', [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step",
            'description' => "Cocktail description",
            'images' => $images->take(10)->pluck('id')->toArray(),
        ]);
        $response->assertCreated();
    }

    public function test_limits_cocktails_images_limit_for_unsubscribed_users(): void
    {
        Config::set('bar-assistant.enable_billing', true);

        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $image1 = Image::factory()->create();
        $image2 = Image::factory()->create();
        $image3 = Image::factory()->create();

        $response = $this->postJson('/api/cocktails', [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step",
            'description' => "Cocktail description",
            'images' => [$image1->id, $image2->id, $image3->id],
        ]);

        $response->assertUnprocessable();

        $response = $this->postJson('/api/cocktails', [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step",
            'description' => "Cocktail description",
            'images' => [$image1->id],
        ]);

        $response->assertCreated();

        $membership->user->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_12345',
            'status' => Subscription::STATUS_ACTIVE,
        ]);
        $membership->user->refresh();

        $response = $this->postJson('/api/cocktails', [
            "name" => "Cocktail name",
            "instructions" => "1. Step\n2. Step",
            "description" => "Cocktail description",
            "images" => [$image1->id, $image2->id, $image3->id],
        ]);
        $response->assertCreated();
    }

    public function test_cocktails_filter_favorited_by_user(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Two additional members of the same bar
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $barMembership2 = BarMembership::factory()->recycle($user2, $membership->bar)->create();
        $barMembership3 = BarMembership::factory()->recycle($user3, $membership->bar)->create();

        // Cocktails in the bar
        $cocktailA = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Cocktail A']);
        $cocktailB = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Cocktail B']);
        $cocktailC = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Cocktail C']);

        // user2 favorites A and B; user3 favorites only A
        CocktailFavorite::factory()->recycle($cocktailA, $barMembership2)->create();
        CocktailFavorite::factory()->recycle($cocktailB, $barMembership2)->create();
        CocktailFavorite::factory()->recycle($cocktailA, $barMembership3)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        // Single user returns their favorites (user2 -> A, B)
        $response = $this->getJson('/api/cocktails?filter[favorited_by_user]=' . $user2->id);
        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        // Two users returns the union
        $response = $this->getJson('/api/cocktails?filter[favorited_by_user]=' . $user2->id . ',' . $user3->id);
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.name', 'Cocktail A');

        // A user with no favorites yields empty (using membership->user who has not favorited anything)
        $response = $this->getJson('/api/cocktails?filter[favorited_by_user]=' . $membership->user->id);
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // A non-member user id yields empty
        $nonMemberUser = User::factory()->create();
        $response = $this->getJson('/api/cocktails?filter[favorited_by_user]=' . $nonMemberUser->id);
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // Omitted/empty value is a no-op (returns all 3 cocktails)
        $response = $this->getJson('/api/cocktails?filter[favorited_by_user]=');
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_cocktails_filter_favorited_by_user_accessible_to_non_admin_member(): void
    {
        $membership = $this->setupBarMembership(UserRoleEnum::General);
        $this->actingAs($membership->user);

        $otherUser = User::factory()->create();
        $otherMembership = BarMembership::factory()->recycle($otherUser, $membership->bar)->create();

        $cocktail = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Fave']);
        CocktailFavorite::factory()->recycle($cocktail, $otherMembership)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        // Non-admin member can use the filter (not 403)
        $response = $this->getJson('/api/cocktails?filter[favorited_by_user]=' . $otherUser->id);
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_cocktails_filter_by_author(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Cocktails in the bar
        $cocktail1 = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Cocktail 1', 'author' => 'Jerry Thomas']);
        $cocktail2 = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Cocktail 2', 'author' => 'Audrey Saunders']);
        $cocktail3 = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Cocktail 3', 'author' => 'Ada Coleman']);
        Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Cocktail Null', 'author' => null]);

        // Cocktail in another bar with same author
        $otherBar = Bar::factory()->create();
        Cocktail::factory()->recycle($otherBar)->create(['name' => 'Cocktail Other', 'author' => 'Jerry Thomas']);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        // Single author match
        $response = $this->getJson('/api/cocktails?filter[author]=Jerry Thomas');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Cocktail 1');

        // Multiple authors (OR match)
        $response = $this->getJson('/api/cocktails?filter[author]=Jerry Thomas,Audrey Saunders');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        // Exact match required (partial does not match)
        $response = $this->getJson('/api/cocktails?filter[author]=Jerry');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // Nonexistent author
        $response = $this->getJson('/api/cocktails?filter[author]=Nonexistent');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // Cocktails with null author are never returned when author filter is active
        $response = $this->getJson('/api/cocktails?filter[author]=null');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // Omitted/empty value is a no-op (returns all 4 cocktails in this bar)
        $response = $this->getJson('/api/cocktails?filter[author]=');
        $response->assertOk();
        $response->assertJsonCount(4, 'data');
    }

    public function test_cocktails_meta_filters_authors(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Cocktails in the bar (with duplicate author and null author and empty author)
        Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Drink 1', 'author' => 'Jerry Thomas']);
        Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Drink 2', 'author' => 'Audrey Saunders']);
        Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Drink 3', 'author' => 'Jerry Thomas']);
        Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Drink 4', 'author' => null]);
        Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Drink 5', 'author' => '']);

        // Cocktail in another bar
        $otherBar = Bar::factory()->create();
        Cocktail::factory()->recycle($otherBar)->create(['name' => 'Other Drink', 'author' => 'Dale DeGroff']);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        // Unfiltered request lists distinct authors for this bar only, sorted alphabetically
        $response = $this->getJson('/api/cocktails');
        $response->assertOk();
        $response->assertJsonPath('meta.filters.authors', [
            ['name' => 'Audrey Saunders'],
            ['name' => 'Jerry Thomas'],
        ]);

        // Filtered request still includes all distinct authors for the bar
        $response = $this->getJson('/api/cocktails?filter[author]=Jerry Thomas');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.filters.authors', [
            ['name' => 'Audrey Saunders'],
            ['name' => 'Jerry Thomas'],
        ]);
    }

    public function test_cocktails_filter_author_accessible_to_non_admin_member(): void
    {
        $membership = $this->setupBarMembership(UserRoleEnum::General);
        $this->actingAs($membership->user);

        Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Drink A', 'author' => 'Jerry Thomas']);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->getJson('/api/cocktails?filter[author]=Jerry Thomas');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.filters.authors.0.name', 'Jerry Thomas');
    }

    public function test_cocktail_show_returns_half_value_user_and_average_ratings(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $otherMembership = BarMembership::factory()->recycle($membership->bar)->create();
        $anotherMembership = BarMembership::factory()->recycle($membership->bar)->create();

        $cocktail = Cocktail::factory()->recycle($membership->bar)->create();
        $cocktail->rate(3.5, $membership->id);
        $cocktail->rate(3, $otherMembership->id);
        $cocktail->rate(4, $anotherMembership->id);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.rating.user', 3.5);
        $response->assertJsonPath('data.rating.average', 3.5);
        $response->assertJsonPath('data.rating.total_votes', 3);
    }

    public function test_cocktail_show_average_rounds_half_up(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $otherMembership = BarMembership::factory()->recycle($membership->bar)->create();
        $anotherMembership = BarMembership::factory()->recycle($membership->bar)->create();

        $cocktail = Cocktail::factory()->recycle($membership->bar)->create();
        $cocktail->rate(4, $membership->id);
        $cocktail->rate(4, $otherMembership->id);
        $cocktail->rate(5, $anotherMembership->id);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.rating.average', 4.5);
    }

    public function test_cocktails_filter_by_half_user_rating_min(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $otherMembership = BarMembership::factory()->recycle($membership->bar)->create();

        $cocktailLow = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'Low rated']);
        $cocktailLow->rate(3, $membership->id);

        $cocktailHigh = Cocktail::factory()->recycle($membership->bar)->create(['name' => 'High rated']);
        $cocktailHigh->rate(3.5, $membership->id);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->getJson('/api/cocktails?filter[user_rating_min]=3.5');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'High rated');
    }
}
