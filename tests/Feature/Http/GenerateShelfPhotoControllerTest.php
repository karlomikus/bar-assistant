<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\GenAI\ShelfPhotoAgent;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GenerateShelfPhotoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'bar-assistant.ai.provider' => 'openai',
        ]);
    }

    private function sendShelfPhotoRequest(BarMembership $membership, int $imageCount = 1): \Illuminate\Testing\TestResponse
    {
        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $images = [];
        for ($i = 0; $i < $imageCount; $i++) {
            $images[] = UploadedFile::fake()->createWithContent('shelf-' . $i . '.jpg', $this->getFakeImageContent('jpg'));
        }

        return $this->post('/api/generate/shelf-from-photo', ['images' => $images]);
    }

    public function test_identifies_bottles_and_marks_matched_ingredients_in_stock(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cointreau = Ingredient::factory()->for($membership->bar)->create(['name' => 'Cointreau']);
        $cointreauImage = Image::factory()->for($cointreau, 'imageable')->create();
        $gin = Ingredient::factory()->for($membership->bar)->create(['name' => 'Gin']);
        $scotch = Ingredient::factory()->for($membership->bar)->create(['name' => 'Scotch whisky']);

        ShelfPhotoAgent::fake([
            ['bottles' => [
                ['name' => 'Cointreau'],
                ['name' => 'Cointreau'],
                ['name' => 'GIN'],
                ['name' => 'Blended scotch whisky'],
                ['name' => 'Captin Morgon'],
            ]],
        ]);

        $response = $this->sendShelfPhotoRequest($membership);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.stocked', 3)
                ->has('data.stocked.0', function (AssertableJson $jsonStocked) use ($cointreau, $cointreauImage) {
                    $jsonStocked
                        ->where('id', $cointreau->id)
                        ->where('slug', $cointreau->slug)
                        ->where('name', 'Cointreau')
                        ->has('image', function (AssertableJson $jsonImage) use ($cointreauImage) {
                            $jsonImage
                                ->where('id', $cointreauImage->id)
                                ->where('url', $cointreauImage->getImageUrl())
                                ->etc();
                        })
                        ->etc();
                })
                ->where('data.stocked.1.id', $gin->id)
                ->where('data.stocked.1.image', null)
                ->where('data.stocked.2.id', $scotch->id)
                ->has('data.unmatched', 1)
                ->where('data.unmatched.0', 'Captin Morgon')
                ->etc()
        );

        // Duplicates deduped: one shelf entry per matched ingredient
        $this->assertSame(3, BarIngredient::query()->where('bar_id', $membership->bar_id)->count());
        $this->assertTrue(BarIngredient::query()->where('bar_id', $membership->bar_id)->where('ingredient_id', $cointreau->id)->exists());
        $this->assertTrue(BarIngredient::query()->where('bar_id', $membership->bar_id)->where('ingredient_id', $gin->id)->exists());
    }

    public function test_unmatched_bottles_are_reported_and_ingredients_are_not_created(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredientCountBefore = Ingredient::query()->where('bar_id', $membership->bar_id)->count();

        ShelfPhotoAgent::fake([
            ['bottles' => [['name' => 'Captin Morgon']]],
        ]);

        $response = $this->sendShelfPhotoRequest($membership);

        $response->assertOk();
        $response->assertJsonPath('data.stocked', []);
        $response->assertJsonPath('data.unmatched', ['Captin Morgon']);

        $this->assertSame($ingredientCountBefore, Ingredient::query()->where('bar_id', $membership->bar_id)->count());
        $this->assertSame(0, BarIngredient::query()->where('bar_id', $membership->bar_id)->count());
    }

    public function test_photo_without_identifiable_bottles_returns_success(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        ShelfPhotoAgent::fake([
            ['bottles' => []],
        ]);

        $response = $this->sendShelfPhotoRequest($membership);

        $response->assertOk();
        $response->assertJsonPath('data.stocked', []);
        $response->assertJsonPath('data.unmatched', []);
        $this->assertSame(0, BarIngredient::query()->where('bar_id', $membership->bar_id)->count());
    }

    public function test_user_without_shelf_permission_is_rejected(): void
    {
        $owner = User::factory()->create();
        $bar = Bar::factory()->create(['created_user_id' => $owner->id]);
        $member = User::factory()->create();
        $membership = BarMembership::factory()
            ->recycle($member, $bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);
        $this->actingAs($member);

        ShelfPhotoAgent::fake([
            ['bottles' => [['name' => 'Cointreau']]],
        ]);

        $response = $this->sendShelfPhotoRequest($membership);

        $response->assertForbidden();
        $this->assertSame(0, BarIngredient::query()->where('bar_id', $bar->id)->count());
    }

    public function test_accepts_multiple_photos_and_passes_them_to_the_agent(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create(['name' => 'Cointreau']);

        ShelfPhotoAgent::fake([
            ['bottles' => [['name' => 'Cointreau']]],
        ]);

        $response = $this->sendShelfPhotoRequest($membership, imageCount: 2);

        $response->assertOk();
        $response->assertJsonPath('data.stocked.0.id', $ingredient->id);
        $response->assertJsonPath('data.unmatched', []);

        ShelfPhotoAgent::assertPrompted(fn ($prompt) => $prompt->attachments->count() === 2);
    }

    public function test_rejects_more_than_ten_photos(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $response = $this->sendShelfPhotoRequest($membership, imageCount: 11);

        $response->assertInvalid(['images']);
    }

    public function test_unconfigured_ai_provider_is_rejected(): void
    {
        config(['bar-assistant.ai.provider' => null]);

        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $response = $this->sendShelfPhotoRequest($membership);

        $response->assertStatus(400);
    }
}
