<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Psr\Log\LoggerInterface;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Cocktail;
use Laravel\Ai\Image as LaravelAiImage;
use Kami\Cocktail\External\Model\Schema;
use Kami\Cocktail\GenAI\CocktailImageHandler;
use BarAssistant\Application\Image\ImageService;
use BarAssistant\Application\Image\DTO\CreateImage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Kami\Cocktail\GenAI\DTO\CocktailImageRequest as CocktailImagePromptRequest;

final readonly class CocktailImageGenerationService
{
    public function __construct(
        private ImageService $imageService,
        private ImageUploadService $imageUploadService,
        private LoggerInterface $log,
    ) {
    }

    public function generateImage(Cocktail $cocktail, int $userId, ?string $style = null): Image
    {
        $promptConfiguration = new CocktailImageHandler(new CocktailImagePromptRequest(
            cocktailName: $cocktail->name,
            cocktailRecipe: Schema::fromCocktailModel($cocktail)->toMarkdown(),
            glassName: $cocktail->glass?->name,
            garnish: $cocktail->garnish,
            style: $style,
        ))();

        $this->log->info("[LLM] Generating cocktail image for cocktail ID: {$cocktail->id}");

        try {
            $response = LaravelAiImage::of($promptConfiguration->prompt)
                ->portrait()
                ->timeout(180)
                ->generate();
        } catch (Throwable $e) {
            $this->log->error('[LLM] generateCocktailImage: Image generation error', [$e->getMessage()]);

            throw new HttpException(502, 'Failed to generate cocktail image. Please try again.');
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
