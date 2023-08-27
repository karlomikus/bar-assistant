<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Utensil;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
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

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_cocktails_response(): void
    {
        $this->useBar(
            Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id])
        );
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => 1, 'user_role_id' => 1]);

        Cocktail::factory()->count(55)->create(['bar_id' => 1]);

        $response = $this->getJson('/api/cocktails?bar_id=1');

        $response->assertStatus(200);
        $response->assertJsonCount(25, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 3);
        $response->assertJsonPath('meta.per_page', 25);
        $response->assertJsonPath('meta.total', 55);

        $response = $this->getJson('/api/cocktails?bar_id=1&page=2');
        $response->assertJsonPath('meta.current_page', 2);

        $response = $this->getJson('/api/cocktails?bar_id=1&per_page=5');
        $response->assertJsonPath('meta.last_page', 11);
    }

    public function test_cocktails_response_with_filters(): void
    {
        $this->useBar(
            Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id])
        );
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => 1, 'user_role_id' => 1]);

        $user = User::factory()->create();
        Cocktail::factory()->createMany([
            ['bar_id' => 1, 'name' => 'Old Fashioned'],
            ['bar_id' => 1, 'name' => 'XXXX'],
            ['bar_id' => 1, 'name' => 'Test', 'created_user_id' => $user->id],
            ['bar_id' => 1, 'name' => 'public', 'public_id' => 'UUID'],
        ]);
        Cocktail::factory()->hasTags(1)->create(['name' => 'test 1', 'bar_id' => 1]);
        Cocktail::factory()->has(
            CocktailIngredient::factory()->for(
                Ingredient::factory()->state(['name' => 'absinthe'])->create()
            ),
            'ingredients'
        )->create([
            'abv' => 33.3,
            'bar_id' => 1
        ]);
        $cocktailFavorited = Cocktail::factory()->create(['bar_id' => 1]);

        $favorite = new CocktailFavorite();
        $favorite->cocktail_id = $cocktailFavorited->id;
        $favorite->bar_membership_id = 1;
        $favorite->save();

        $response = $this->getJson('/api/cocktails?bar_id=1&filter[name]=old');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[name]=old,xx');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[tag_id]=1');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[created_user_id]=' . $user->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[on_shelf]=true');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[favorites]=true');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[is_public]=true');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[ingredient_name]=absinthe');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[id]=1,2');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[ingredient_id]=1');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[abv_min]=30');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[abv_min]=34');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[abv_max]=30');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/cocktails?bar_id=1&filter[abv_max]=50');
        $response->assertJsonCount(1, 'data');
    }

    public function test_cocktails_response_with_sorts(): void
    {
        $this->useBar(
            Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id])
        );
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => 1, 'user_role_id' => 1]);

        Cocktail::factory()->createMany([
            ['bar_id' => 1, 'name' => 'B Cocktail'],
            ['bar_id' => 1, 'name' => 'A Cocktail'],
            ['bar_id' => 1, 'name' => 'C Cocktail'],
        ]);

        $response = $this->getJson('/api/cocktails?bar_id=1&sort=name');
        $response->assertJsonPath('data.0.name', 'A Cocktail');
        $response->assertJsonPath('data.1.name', 'B Cocktail');
        $response->assertJsonPath('data.2.name', 'C Cocktail');

        $response = $this->getJson('/api/cocktails?bar_id=1&sort=-name');
        $response->assertJsonPath('data.0.name', 'C Cocktail');
        $response->assertJsonPath('data.1.name', 'B Cocktail');
        $response->assertJsonPath('data.2.name', 'A Cocktail');
    }

    public function test_cocktail_show_response(): void
    {
        $bar = Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => 1, 'user_role_id' => 1]);

        $glass = Glass::factory()->create(['bar_id' => $bar->id]);
        $method = CocktailMethod::factory()->create(['bar_id' => $bar->id]);
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
                'created_user_id' => auth()->user()->id,
                'bar_id' => $bar->id
            ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'A cocktail name')
                ->where('data.slug', 'a-cocktail-name-1')
                ->where('data.instructions', "1. Step 1\n2. Step two")
                ->where('data.garnish', '# Lemon twist')
                ->where('data.description', 'A short description')
                ->where('data.source', 'http://test.com')
                ->where('data.public_id', null)
                ->where('data.main_image_id', null)
                ->where('data.images', [])
                ->has('data.tags', 5)
                ->where('data.user_rating', 4)
                ->where('data.average_rating', 3)
                ->where('data.glass.id', $glass->id)
                ->where('data.method.id', $method->id)
                ->has('data.abv')
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
    }

    public function test_cocktail_show_using_slug_response(): void
    {
        Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => 1, 'user_role_id' => 1]);

        $cocktail = Cocktail::factory()->create(['bar_id' => 1]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->slug);

        $response->assertStatus(200);

        $cocktail = Cocktail::factory()->create([
            'slug' => '200',
            'bar_id' => 1,
        ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->slug);

        $response->assertStatus(200);
    }

    public function test_cocktail_create_response(): void
    {
        $this->useBar(
            Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id])
        );
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => 1, 'user_role_id' => 1]);

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
        $image = Image::factory()->create(['created_user_id' => auth()->user()->id]);
        Utensil::factory()->count(5)->create();

        $response = $this->postJson('/api/cocktails?bar_id=1', [
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
                ->where('data.slug', 'cocktail-name-1')
                ->where('data.name', 'Cocktail name')
                ->where('data.description', 'Cocktail description')
                ->where('data.garnish', 'Lemon peel')
                ->where('data.public_id', null)
                ->where('data.main_image_id', $image->id)
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
                ->has('data.utensils', 3)
                ->etc()
        );
    }

    public function test_cocktail_update_response(): void
    {
        Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => auth()->user()->id, 'user_role_id' => 1]);

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
        Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => auth()->user()->id, 'user_role_id' => 1]);

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth()->user()->id, 'bar_id' => 1]);

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id);

        $response->assertNoContent();
    }

    public function test_cocktail_delete_deletes_images_response(): void
    {
        Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => auth()->user()->id, 'user_role_id' => 1]);

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth()->user()->id, 'bar_id' => 1]);
        $storage = Storage::fake('bar-assistant');
        $imageFile = UploadedFile::fake()->image('image1.jpg');
        $image = Image::factory()->for($cocktail, 'imageable')->create([
            'file_path' => $imageFile->storeAs('temp', 'image1.jpg', 'bar-assistant'),
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
        Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => auth()->user()->id, 'user_role_id' => 1]);

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth()->user()->id, 'bar_id' => 1]);

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

    public function test_delete_cocktail_public_link_response(): void
    {
        Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => auth()->user()->id, 'user_role_id' => 1]);

        $cocktail = Cocktail::factory()->create(['created_user_id' => auth()->user()->id, 'bar_id' => 1]);

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id . '/public-link');

        $response->assertNoContent();

        $cocktail = Cocktail::find($cocktail->id);
        $this->assertNull($cocktail->public_id);
    }

    public function test_cocktail_share_response(): void
    {
        Bar::factory()->create(['id' => 1, 'created_user_id' => auth()->user()->id]);
        DB::table('bar_memberships')->insert(['id' => 1, 'bar_id' => 1, 'user_id' => auth()->user()->id, 'user_role_id' => 1]);

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
}
