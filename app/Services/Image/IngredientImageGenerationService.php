<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Psr\Log\LoggerInterface;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Ingredient;
use Laravel\Ai\Image as LaravelAiImage;
use Laravel\Ai\Files\Image as AiImageFile;
use Kami\Cocktail\GenAI\IngredientImageHandler;
use BarAssistant\Application\Image\ImageService;
use BarAssistant\Application\Image\DTO\CreateImage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Kami\Cocktail\GenAI\DTO\IngredientImageRequest as IngredientImagePromptRequest;

final readonly class IngredientImageGenerationService
{
    public function __construct(
        private ImageService $imageService,
        private ImageUploadService $imageUploadService,
        private LoggerInterface $log,
    ) {
    }

    public function generateImage(Ingredient $ingredient, int $userId, ?string $style = null): Image
    {
        $promptConfiguration = new IngredientImageHandler(new IngredientImagePromptRequest(
            ingredientName: $ingredient->name,
            category: $ingredient->getMaterializedPathAsString(),
            description: $ingredient->description,
            parentIngredientName: $ingredient->parentIngredient?->name,
            style: $style,
        ))();

        $this->log->info("[LLM] Generating ingredient image for ingredient ID: {$ingredient->id}");

        $model = config('bar-assistant.ai.model_image');

        $pendingImage = LaravelAiImage::of($promptConfiguration->prompt)
            ->portrait()
            ->timeout(180);

        if (config('bar-assistant.ai.image_supports_reference')) {
            $pendingImage->attachments([
                AiImageFile::fromPath(resource_path('ai/no-ingredient.png')),
            ]);
        }

        // OpenAI-family image models reject unknown parameters, so only send
        // transparency options where they are supported
        if (in_array(config('bar-assistant.ai.provider_image'), ['openai', 'azure-openai'])) {
            $pendingImage->withProviderOptions([
                'background' => 'transparent',
                'output_format' => 'png',
            ]);
        }

        try {
            $response = $pendingImage->generate(model: is_string($model) && $model !== '' ? $model : null);
        } catch (Throwable $e) {
            $this->log->error('[LLM] generateIngredientImage: Image generation error', [$e->getMessage()]);

            throw new HttpException(502, 'Failed to generate ingredient image. Please try again.');
        }

        $imageContents = (string) $response;
        $uploadedImage = $this->imageUploadService->uploadImage($imageContents);
        if ($uploadedImage === null) {
            throw new HttpException(502, 'Failed to upload generated image.');
        }

        $storedImage = $this->imageService->createImage(new CreateImage($uploadedImage->path, $uploadedImage->extension, $userId, 1, 'AI (' . config('ai.default_for_images') . ')', $uploadedImage->placeholderHash));

        return Image::findOrFail($storedImage->id);
    }
}
