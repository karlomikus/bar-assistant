<?php

declare(strict_types=1);

namespace Kami\Cocktail\Import;

use Throwable;
use Kami\Cocktail\ETL\Matcher;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\DataObjects\Image;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;
use Kami\Cocktail\ETL\Cocktail as CocktailETL;
use Intervention\Image\Facades\Image as ImageProcessor;
use Kami\Cocktail\DataObjects\Cocktail\Cocktail as CocktailDTO;
use Kami\Cocktail\DataObjects\Cocktail\Substitute as SubstituteDTO;
use Kami\Cocktail\DataObjects\Cocktail\Ingredient as CocktailIngredientDTO;

class FromArray
{
    public function __construct(
        private readonly CocktailService $cocktailService,
        private readonly IngredientService $ingredientService,
        private readonly ImageService $imageService
    ) {
    }

    /**
     * @param array<mixed> $sourceData
     */
    public function process(
        array $sourceData,
        int $userId,
        int $barId,
        DuplicateActionsEnum $duplicateAction = DuplicateActionsEnum::None
    ): Cocktail {
        $cocktailETL = CocktailETL::fromArray($sourceData);

        $existingCocktail = Cocktail::whereRaw('LOWER(name) = ?', [mb_strtolower($cocktailETL->name, 'UTF-8')])->where('bar_id', $barId)->first();
        if ($duplicateAction === DuplicateActionsEnum::Skip && $existingCocktail !== null) {
            return $existingCocktail;
        }

        $matcher = new Matcher($userId, $barId, $this->ingredientService);

        // Add images
        $cocktailImages = [];
        foreach ($cocktailETL->images as $image) {
            if ($image->source) {
                try {
                    $imageDTO = new Image(
                        ImageProcessor::make($image->source),
                        $image->copyright
                    );

                    $cocktailImages[] = $this->imageService->uploadAndSaveImages([$imageDTO], 1)[0]->id;
                } catch (Throwable $e) {
                    Log::error('Importing from array error: ' . $e->getMessage());
                }
            }
        }

        // Match glass
        $glassId = null;
        if ($cocktailETL->glass) {
            $glassId = $matcher->matchGlassByName($cocktailETL->glass);
        }

        // Match method
        $methodId = null;
        if ($cocktailETL->method) {
            $methodId = $matcher->matchMethodByName($cocktailETL->method);
        }

        // Match ingredients
        $ingredients = [];
        $sort = 1;
        foreach ($cocktailETL->ingredients as $scrapedIngredient) {
            $ingredientId = $matcher->matchOrCreateIngredientByName($scrapedIngredient->ingredient);

            $substitutes = [];
            foreach($scrapedIngredient->substitutes as $substitute) {
                $substitutes[] = new SubstituteDTO(
                    $matcher->matchOrCreateIngredientByName($substitute->ingredient),
                    $substitute->amount,
                    $substitute->amountMax,
                    $substitute->units,
                );
            }

            $ingredient = new CocktailIngredientDTO(
                $ingredientId,
                $scrapedIngredient->ingredient->name,
                $scrapedIngredient->amount,
                $scrapedIngredient->units,
                $sort,
                $scrapedIngredient->optional,
                $substitutes,
                $scrapedIngredient->amountMax,
                $scrapedIngredient->note,
            );

            $ingredients[] = $ingredient;
            $sort++;
        }

        $cocktailDTO = new CocktailDTO(
            $cocktailETL->name,
            $cocktailETL->instructions,
            $userId,
            $barId,
            $cocktailETL->description,
            $cocktailETL->source,
            $cocktailETL->garnish,
            $glassId,
            $methodId,
            $cocktailETL->tags,
            $ingredients,
            $cocktailImages,
        );

        if ($duplicateAction === DuplicateActionsEnum::Overwrite && $existingCocktail !== null) {
            return $this->cocktailService->updateCocktail($existingCocktail->id, $cocktailDTO);
        }

        return $this->cocktailService->createCocktail($cocktailDTO);
    }
}
