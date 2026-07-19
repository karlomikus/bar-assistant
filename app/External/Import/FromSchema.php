<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use Throwable;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\External\Model\Schema;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Support\Facades\Validator;
use BarAssistant\Application\Image\ImageService;
use BarAssistant\Application\Matcher\GlassMatcher;
use BarAssistant\Application\Image\DTO\CreateImage;
use BarAssistant\Application\Matcher\MethodMatcher;
use Kami\Cocktail\Services\Image\ImageUploadService;
use BarAssistant\Application\Matcher\CocktailMatcher;
use BarAssistant\Application\Cocktail\CocktailService;
use BarAssistant\Application\Matcher\IngredientMatcher;
use Kami\Cocktail\Services\Image\ImageValidatorService;
use BarAssistant\Application\Cocktail\DTO\CreateCocktail;
use BarAssistant\Application\Cocktail\DTO\UpdateCocktail;
use BarAssistant\Application\Matcher\DTO\GlassMatchRequest;
use BarAssistant\Application\Matcher\DTO\MethodMatchRequest;
use BarAssistant\Application\Cocktail\DTO\CocktailIngredient;
use BarAssistant\Application\Matcher\DTO\CocktailMatchRequest;
use BarAssistant\Application\Matcher\DTO\IngredientMatchRequest;
use BarAssistant\Application\Cocktail\DTO\CocktailIngredientSubstitute;

final readonly class FromSchema
{
    public function __construct(
        private CocktailService $cocktailService,
        private CocktailMatcher $cocktailMatcher,
        private IngredientMatcher $ingredientMatcher,
        private GlassMatcher $glassMatcher,
        private MethodMatcher $methodMatcher,
        private ImageService $imageService,
        private ImageUploadService $imageUploadService,
        private ImageValidatorService $imageValidatorService,
    ) {
    }

    public function process(
        int $barId,
        int $userId,
        Schema $schema,
        DuplicateActionsEnum $duplicateAction = DuplicateActionsEnum::None,
    ): Cocktail {
        $existingCocktail = $this->cocktailMatcher->matchByName(new CocktailMatchRequest($barId, $schema->cocktail->name));
        if ($duplicateAction === DuplicateActionsEnum::Skip && $existingCocktail !== null) {
            return Cocktail::find($existingCocktail);
        }

        // Add images
        $cocktailImages = [];
        foreach ($schema->cocktail->images as $image) {
            try {
                if ($image->uri) {
                    Validator::make(['image_url' => $image->uri], [
                        'image_url' => 'url:http,https'
                    ])->validate();
                    $imageContents = $this->imageValidatorService->getValidImageSource($image->uri);
                    if ($imageContents === null) {
                        continue;
                    }
                    $uploadedImage = $this->imageUploadService->uploadImage($imageContents);
                    $storedImage = $this->imageService->createImage(new CreateImage($uploadedImage->path, $uploadedImage->extension, $userId, 1, $image->copyright, $uploadedImage->placeholderHash));
                    $cocktailImages[] = $storedImage->id;
                }
            } catch (Throwable $e) {
                Log::error('Importing image error: ' . $e->getMessage());
            }
        }

        $glassId = null;
        if ($schema->cocktail->glass) {
            $glassId = $this->glassMatcher->matchByName(new GlassMatchRequest($barId, $schema->cocktail->glass));
        }

        $methodId = null;
        if ($schema->cocktail->method) {
            $methodId = $this->methodMatcher->matchByName(new MethodMatchRequest($barId, $schema->cocktail->method));
        }

        $dilution = 0.0;
        if ($methodId) {
            $dilution = CocktailMethod::find($methodId)->dilution_percentage ?? 0.0;
        }

        $ingredients = [];
        $sort = 1;
        foreach ($schema->cocktail->ingredients as $scrapedIngredient) {
            $ingredientId = $this->ingredientMatcher->matchOrCreateByName(
                new IngredientMatchRequest(
                    barId: $barId,
                    userId: $userId,
                    ingredientName: $scrapedIngredient->ingredient->name,
                ),
            );

            $substitutes = [];
            foreach ($scrapedIngredient->substitutes as $substitute) {
                $substitutes[] = new CocktailIngredientSubstitute(
                    ingredientId: $this->ingredientMatcher->matchOrCreateByName(
                        new IngredientMatchRequest(
                            barId: $barId,
                            userId: $userId,
                            ingredientName: $substitute->ingredient->name,
                        )
                    ),
                    amount: $substitute->amount->amountMin,
                    units: $substitute->amount->units->value,
                    amountMax: $substitute->amount->amountMax,
                );
            }

            $ingredients[] = new CocktailIngredient(
                ingredientId: $ingredientId,
                strength: 0.0, // TODO: Implement strength mapping
                amount: $scrapedIngredient->amount->amountMin,
                units: $scrapedIngredient->amount->units->value,
                sort: $sort,
                isOptional: $scrapedIngredient->optional,
                isSpecified: false,
                substitutes: $substitutes,
                amountMax: $scrapedIngredient->amount->amountMax,
                note: $scrapedIngredient->note,
            );

            $sort++;
        }

        if ($duplicateAction === DuplicateActionsEnum::Overwrite && $existingCocktail !== null) {
            $this->cocktailService->updateCocktail(new UpdateCocktail(
                cocktailId: $existingCocktail,
                barId: $barId,
                name: $schema->cocktail->name,
                instructions: $schema->cocktail->instructions,
                userId: $userId,
                dilution: $dilution,
                description: $schema->cocktail->description,
                source: $schema->cocktail->source,
                garnish: $schema->cocktail->garnish,
                glassId: $glassId,
                methodId: $methodId,
                tags: $schema->cocktail->tags,
                ingredients: $ingredients,
                images: $cocktailImages,
                utensils: [],
                parentCocktailId: null,
                year: null,
            ));

            return Cocktail::findOrFail($existingCocktail);
        }

        return Cocktail::findOrFail($this->cocktailService->createCocktail(new CreateCocktail(
            barId: $barId,
            name: $schema->cocktail->name,
            instructions: $schema->cocktail->instructions,
            userId: $userId,
            dilution: $dilution,
            description: $schema->cocktail->description,
            source: $schema->cocktail->source,
            garnish: $schema->cocktail->garnish,
            glassId: $glassId,
            methodId: $methodId,
            tags: $schema->cocktail->tags,
            ingredients: $ingredients,
            images: $cocktailImages,
            utensils: [],
            parentCocktailId: null,
            year: null,
        ))->id);
    }
}
