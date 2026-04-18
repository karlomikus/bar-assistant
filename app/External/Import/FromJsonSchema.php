<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use BarAssistant\Application\Cocktail\CocktailService;
use BarAssistant\Application\Cocktail\DTO\CocktailIngredient;
use BarAssistant\Application\Cocktail\DTO\CocktailIngredientSubstitute;
use BarAssistant\Application\Cocktail\DTO\CreateCocktail;
use BarAssistant\Application\Cocktail\DTO\UpdateCocktail;
use BarAssistant\Application\Image\DTO\CreateImage;
use BarAssistant\Application\Image\ImageService;
use BarAssistant\Application\Matcher\CocktailMatcher;
use BarAssistant\Application\Matcher\DTO\CocktailMatchRequest;
use BarAssistant\Application\Matcher\DTO\GlassMatchRequest;
use BarAssistant\Application\Matcher\DTO\IngredientMatchRequest;
use BarAssistant\Application\Matcher\DTO\MethodMatchRequest;
use BarAssistant\Application\Matcher\GlassMatcher;
use BarAssistant\Application\Matcher\IngredientMatcher;
use BarAssistant\Application\Matcher\MethodMatcher;
use Throwable;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\External\Model\Schema;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\Services\Image\ImageUploadService;

final readonly class FromJsonSchema
{
    public function __construct(
        private CocktailService $cocktailService,
        private CocktailMatcher $cocktailMatcher,
        private IngredientMatcher $ingredientMatcher,
        private GlassMatcher $glassMatcher,
        private MethodMatcher $methodMatcher,
        private ImageService $imageService,
        private ImageUploadService $imageUploadService,
    ) {
    }

    public function process(
        int $barId,
        int $userId,
        Schema $cocktailExternal,
        DuplicateActionsEnum $duplicateAction = DuplicateActionsEnum::None,
        string $imageDirectoryBasePath = '',
    ): Cocktail {
        $existingCocktail = $this->cocktailMatcher->matchByName(new CocktailMatchRequest($barId, $cocktailExternal->cocktail->name));
        if ($duplicateAction === DuplicateActionsEnum::Skip && $existingCocktail !== null) {
            return Cocktail::find($existingCocktail);
        }

        // Add images
        $cocktailImages = [];
        foreach ($cocktailExternal->cocktail->images as $image) {
            try {
                if ($image->uri && $imageContents = file_get_contents($imageDirectoryBasePath . $image->getLocalFilePath())) {
                    $uploadedImage = $this->imageUploadService->uploadImage($imageContents);
                    $storedImage = $this->imageService->createImage(new CreateImage($uploadedImage->path, $uploadedImage->extension, $userId, 1, $image->copyright, $uploadedImage->placeholderHash));
                    $cocktailImages[] = $storedImage->id;
                }
            } catch (Throwable $e) {
                Log::error('Importing image error: ' . $e->getMessage());
            }
        }

        // Match glass
        $glassId = null;
        if ($cocktailExternal->cocktail->glass) {
            $glassId = $this->glassMatcher->matchByName(new GlassMatchRequest($barId, $cocktailExternal->cocktail->glass));
        }

        // Match method
        $methodId = null;
        if ($cocktailExternal->cocktail->method) {
            $methodId = $this->methodMatcher->matchByName(new MethodMatchRequest($barId, $cocktailExternal->cocktail->method));
        }

        $dilution = 0.0;
        if ($methodId) {
            $dilution = CocktailMethod::find($methodId)?->dilution_percentage ?? 0.0;
        }

        // Match ingredients
        $ingredients = [];
        $sort = 1;
        foreach ($cocktailExternal->cocktail->ingredients as $scrapedIngredient) {
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
                strength: $ingredientStrengths[$ingredientId] ?? 0.0,
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
            return Cocktail::findOrFail($this->cocktailService->updateCocktail(new UpdateCocktail(
                cocktailId: $existingCocktail,
                barId: $barId,
                name: $cocktailExternal->cocktail->name,
                instructions: $cocktailExternal->cocktail->instructions,
                userId: $userId,
                dilution: $dilution,
                description: $cocktailExternal->cocktail->description,
                source: $cocktailExternal->cocktail->source,
                garnish: $cocktailExternal->cocktail->garnish,
                glassId: $glassId,
                methodId: $methodId,
                tags: $cocktailExternal->cocktail->tags,
                ingredients: $ingredients,
                images: $cocktailImages,
                utensils: [],
                parentCocktailId: null,
                year: null,
            )));
        }

        return Cocktail::findOrFail($this->cocktailService->createCocktail(new CreateCocktail(
            barId: $barId,
            name: $cocktailExternal->cocktail->name,
            instructions: $cocktailExternal->cocktail->instructions,
            userId: $userId,
            dilution: $dilution,
            description: $cocktailExternal->cocktail->description,
            source: $cocktailExternal->cocktail->source,
            garnish: $cocktailExternal->cocktail->garnish,
            glassId: $glassId,
            methodId: $methodId,
            tags: $cocktailExternal->cocktail->tags,
            ingredients: $ingredients,
            images: $cocktailImages,
            utensils: [],
            parentCocktailId: null,
            year: null,
        ))->id);
    }
}
