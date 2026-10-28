<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use Laravel\Ai\Image as LaravelAiImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GenerateIngredientImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );

        config([
            'bar-assistant.ai.provider' => 'openai',
            'bar-assistant.ai.provider_image' => 'openai',
            'bar-assistant.ai.model_image' => 'fake-image-model',
        ]);
    }

    public function test_generate_ingredient_image_returns_image_and_attaches_it(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        LaravelAiImage::fake([
            base64_encode(file_get_contents(resource_path('ai/no-ingredient.png'))),
        ]);

        $response = $this->postJson('/api/generate/ingredient-image', [
            'ingredient_id' => (string) $ingredient->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.copyright', fn (string $copyright) => str_starts_with($copyright, 'AI ('));

        $ingredient->refresh();
        $this->assertCount(1, $ingredient->images);
        $this->assertSame($response->json('data.id'), $ingredient->images->first()->id);
        $this->assertSame(1, $ingredient->images->first()->sort);
    }

    public function test_generate_ingredient_image_by_slug(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        LaravelAiImage::fake([
            base64_encode(file_get_contents(resource_path('ai/no-ingredient.png'))),
        ]);

        $response = $this->postJson('/api/generate/ingredient-image', [
            'ingredient_id' => $ingredient->slug,
        ]);

        $response->assertOk();
        $ingredient->refresh();
        $this->assertCount(1, $ingredient->images);
    }

    public function test_generate_ingredient_image_provider_failure_returns_502(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        LaravelAiImage::fake([
            fn () => throw new \RuntimeException('provider down'),
        ]);

        $response = $this->postJson('/api/generate/ingredient-image', [
            'ingredient_id' => (string) $ingredient->id,
        ]);

        $response->assertStatus(502);
        $ingredient->refresh();
        $this->assertCount(0, $ingredient->images);
    }

    public function test_generate_ingredient_image_requires_image_provider_configuration(): void
    {
        config(['bar-assistant.ai.provider_image' => null, 'bar-assistant.ai.model_image' => null]);

        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->postJson('/api/generate/ingredient-image', [
            'ingredient_id' => (string) $ingredient->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_generate_ingredient_image_forbidden_for_non_admin_member(): void
    {
        $membership = $this->setupBarMembership(\Kami\Cocktail\Models\Enums\UserRoleEnum::Guest);
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->postJson('/api/generate/ingredient-image', [
            'ingredient_id' => (string) $ingredient->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_generate_ingredient_image_unknown_ingredient_returns_404(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->postJson('/api/generate/ingredient-image', [
            'ingredient_id' => '999999',
        ]);

        $response->assertStatus(404);
    }
}
