<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Psr\Log\LoggerInterface;
use Prism\Prism\Facades\Prism;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Http;
use Kami\Cocktail\External\Model\Schema;
use Kami\Cocktail\GenAI\GenAIProviderConfig;
use Prism\Prism\ValueObjects\GeneratedImage;
use Kami\Cocktail\GenAI\CocktailImageHandler;
use BarAssistant\Application\Image\ImageService;
use BarAssistant\Application\Image\DTO\CreateImage;
use Illuminate\Http\Client\Response as HttpResponse;
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
                ->withProviderOptions([...$providerConfiguration->getProviderOptions(), 'size' => '1024x1024'])
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
        $uploadedImage = $this->imageUploadService->uploadImage($imageContents);
        $storedImage = $this->imageService->createImage(new CreateImage($uploadedImage->path, $uploadedImage->extension, $userId, 1, 'AI (' . $providerConfiguration->provider->name . ')', $uploadedImage->placeholderHash));

        return Image::findOrFail($storedImage->id);
    }

    private function getGeneratedImageContents(GeneratedImage $generatedImage, int $timeout): string
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

            $contentType = (string) $response->header('Content-Type');
            if (!str_starts_with(strtolower($contentType), 'image/')) {
                throw new HttpException(502, 'Provider returned non-image content for generated image.');
            }

            return $response->body();
        }

        throw new HttpException(502, 'Image model returned an unsupported image format.');
    }
}
