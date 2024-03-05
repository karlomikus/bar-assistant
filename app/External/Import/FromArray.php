<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use Throwable;
use Kami\Cocktail\External\Matcher;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Kami\Cocktail\DataObjects\Image;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;
use Kami\Cocktail\External\Cocktail as CocktailExternal;
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
        $cocktailExternal = CocktailExternal::fromArray($sourceData);

        $existingCocktail = Cocktail::whereRaw('LOWER(name) = ?', [mb_strtolower($cocktailExternal->name, 'UTF-8')])->where('bar_id', $barId)->first();
        if ($duplicateAction === DuplicateActionsEnum::Skip && $existingCocktail !== null) {
            return $existingCocktail;
        }

        $matcher = new Matcher($userId, $barId, $this->ingredientService);

        // Add images
        $cocktailImages = [];
        foreach ($cocktailExternal->images as $image) {
            if ($image->source) {
                $manager = ImageManager::imagick();

                try {
                    $imageDTO = new Image(
                        $manager->read($image->source),
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
        if ($cocktailExternal->glass) {
            $glassId = $matcher->matchGlassByName($cocktailExternal->glass);
        }

        // Match method
        $methodId = null;
        if ($cocktailExternal->method) {
            $methodId = $matcher->matchMethodByName($cocktailExternal->method);
        }

        // Match ingredients
        $ingredients = [];
        $sort = 1;
        foreach ($cocktailExternal->ingredients as $scrapedIngredient) {
            $ingredientId = $matcher->matchOrCreateIngredientByName($scrapedIngredient->ingredient);

            $substitutes = [];
            foreach ($scrapedIngredient->substitutes as $substitute) {
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
            $cocktailExternal->name,
            $cocktailExternal->instructions,
            $userId,
            $barId,
            $cocktailExternal->description,
            $cocktailExternal->source,
            $cocktailExternal->garnish,
            $glassId,
            $methodId,
            $cocktailExternal->tags,
            $ingredients,
            $cocktailImages,
        );

        if ($duplicateAction === DuplicateActionsEnum::Overwrite && $existingCocktail !== null) {
            return $this->cocktailService->updateCocktail($existingCocktail->id, $cocktailDTO);
        }

        return $this->cocktailService->createCocktail($cocktailDTO);
    }
}
