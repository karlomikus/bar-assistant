<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image as LaravelAiImage;
use Kami\Cocktail\Models\Image as ImageModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Kami\Cocktail\Services\Image\IngredientImageGenerationService;

class IngredientImageGenerationTest extends TestCase
{
    use RefreshDatabase;
    public function test_generates_stores_and_attributes_ingredient_image(): void
    {
        config(['bar-assistant.ai.provider_image' => 'openai']);

        $membership = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->create(['bar_id' => $membership->bar_id]);
        $referenceBytes = file_get_contents(resource_path('ai/no-ingredient.png'));

        $capturedPrompt = null;
        LaravelAiImage::fake([
            function ($prompt) use (&$capturedPrompt, $referenceBytes) {
                $capturedPrompt = $prompt;

                return base64_encode($referenceBytes);
            },
        ]);

        $image = app(IngredientImageGenerationService::class)->generateImage($ingredient, $membership->user_id);

        $this->assertInstanceOf(ImageModel::class, $image);
        $this->assertStringStartsWith('AI (', $image->copyright);
        Storage::disk('uploads')->assertExists($image->file_path);

        $this->assertNotNull($capturedPrompt);
        $this->assertStringContainsString($ingredient->name, $capturedPrompt->prompt);
        $this->assertStringContainsString('transparent background', $capturedPrompt->prompt);
        $this->assertCount(1, $capturedPrompt->attachments, 'Bottle reference image must be attached by default');
        $this->assertSame('transparent', $capturedPrompt->providerOptions['background'] ?? null);
        $this->assertSame('png', $capturedPrompt->providerOptions['output_format'] ?? null);
    }

    public function test_skips_transparency_provider_options_for_non_openai_providers(): void
    {
        config(['bar-assistant.ai.provider_image' => 'gemini']);

        $membership = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->create(['bar_id' => $membership->bar_id]);

        $capturedPrompt = null;
        LaravelAiImage::fake([
            function ($prompt) use (&$capturedPrompt) {
                $capturedPrompt = $prompt;

                return base64_encode(file_get_contents(resource_path('ai/no-ingredient.png')));
            },
        ]);

        app(IngredientImageGenerationService::class)->generateImage($ingredient, $membership->user_id);

        $this->assertNotNull($capturedPrompt);
        $this->assertArrayNotHasKey('background', $capturedPrompt->providerOptions);
        $this->assertArrayNotHasKey('output_format', $capturedPrompt->providerOptions);
    }

    public function test_skips_reference_attachment_when_disabled(): void
    {
        config(['bar-assistant.ai.image_supports_reference' => false]);
        $membership = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->create(['bar_id' => $membership->bar_id]);

        $capturedPrompt = null;
        LaravelAiImage::fake([
            function ($prompt) use (&$capturedPrompt) {
                $capturedPrompt = $prompt;

                return base64_encode(file_get_contents(resource_path('ai/no-ingredient.png')));
            },
        ]);

        app(IngredientImageGenerationService::class)->generateImage($ingredient, $membership->user_id);

        $this->assertNotNull($capturedPrompt);
        $this->assertCount(0, $capturedPrompt->attachments);
        $this->assertStringContainsString('reference bottle', $capturedPrompt->prompt, 'Bottle design must still be described in text');
    }

    public function test_provider_failure_throws_502_and_creates_no_image(): void
    {
        $membership = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->create(['bar_id' => $membership->bar_id]);

        LaravelAiImage::fake([
            fn () => throw new \RuntimeException('provider down'),
        ]);

        $imagesBefore = ImageModel::count();

        try {
            app(IngredientImageGenerationService::class)->generateImage($ingredient, $membership->user_id);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertSame(502, $e->getStatusCode());
        }

        $this->assertSame($imagesBefore, ImageModel::count());
    }
}
