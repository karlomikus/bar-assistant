<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Psr\Log\LoggerInterface;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Cocktail;
use Prism\Prism\Facades\Prism;
use Illuminate\Support\Facades\Http;
use Kami\Cocktail\External\Model\Schema;
use Kami\Cocktail\GenAI\GenAIProviderConfig;
use Kami\Cocktail\GenAI\CocktailImageHandler;
use Illuminate\Http\Client\Response as HttpResponse;
use Kami\Cocktail\OpenAPI\Schemas\ImageRequest as ImageRequestDTO;
use Kami\Cocktail\GenAI\DTO\CocktailImageRequest as CocktailImagePromptRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

final readonly class CocktailImageGenerationService
{
    public function __construct(
        private ImageService $imageService,
        private LoggerInterface $log,
    ) {
    }

    public function generateUnassignedImage(Cocktail $cocktail, int $userId, ?string $style = null): Image
    {
        $providerConfiguration = GenAIProviderConfig::fromImageConfig();
        $promptConfiguration = new CocktailImageHandler(new CocktailImagePromptRequest(
            cocktailName: $cocktail->name,
            cocktailRecipe: Schema::fromCocktailModel($cocktail)->toMarkdown(),
            glassName: $cocktail->glass?->name,
            garnish: $cocktail->garnish,
            style: $style,
        ))();

        $this->log->info("[LLM] Generating cocktail image for cocktail ID: {$cocktail->id}");

        try {
            $response = Prism::image()
                ->using($providerConfiguration->provider, $providerConfiguration->model)
                ->withPrompt($promptConfiguration->prompt)
                ->withClientOptions($providerConfiguration->getClientOptions())
                ->withProviderOptions($providerConfiguration->getProviderOptions())
                ->generate();
        } catch (Throwable $e) {
            $this->log->error('[LLM] generateCocktailImage: Image generation error', [$e->getMessage()]);

            throw new HttpException(502, 'Failed to generate cocktail image. Please try again.');
        }

        $generatedImage = $response->firstImage();
        if ($generatedImage === null) {
            throw new HttpException(502, 'Image model did not return an image.');
        }

        $imageContents = $this->getGeneratedImageContents($generatedImage, $providerConfiguration->timeout);
        $savedImages = $this->imageService->uploadAndSaveImages([
            new ImageRequestDTO(
                image: $imageContents,
                sort: 1,
                copyright: 'AI-generated',
            ),
        ], $userId);

        if ($savedImages === []) {
            throw new HttpException(500, 'Generated image could not be saved.');
        }

        $image = $savedImages[0];
        $image->refresh();

        return $image;
    }

    private function getGeneratedImageContents(object $generatedImage, int $timeout): string
    {
        if ($generatedImage->hasBase64()) {
            $decoded = base64_decode((string) $generatedImage->base64, true);
            if ($decoded === false) {
                throw new HttpException(502, 'Image model returned invalid image data.');
            }

            return $decoded;
        }

        if ($generatedImage->hasUrl()) {
            /** @var HttpResponse $response */
            $response = Http::timeout($timeout)->get((string) $generatedImage->url);

            if (!$response->successful()) {
                throw new HttpException(502, 'Failed to download generated image from provider.');
            }

            $contentType = (string) $response->header('Content-Type', '');
            if (!str_starts_with(strtolower($contentType), 'image/')) {
                throw new HttpException(502, 'Provider returned non-image content for generated image.');
            }

            return $response->body();
        }

        throw new HttpException(502, 'Image model returned an unsupported image format.');
    }
}