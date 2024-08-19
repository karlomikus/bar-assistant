<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use Throwable;
use Kami\Cocktail\DTO\Image\Image;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\External\Matcher;
use Intervention\Image\ImageManager;
use Kami\Cocktail\External\Draft2\Schema;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;
use Kami\Cocktail\Services\Image\ImageService;
use Kami\Cocktail\DTO\Cocktail\Cocktail as CocktailDTO;
use Kami\Cocktail\DTO\Cocktail\Substitute as SubstituteDTO;
use Kami\Cocktail\DTO\Ingredient\Ingredient as IngredientDTO;
use Kami\Cocktail\DTO\Cocktail\Ingredient as CocktailIngredientDTO;

class FromSchemaDraft2
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
        DuplicateActionsEnum $duplicateAction = DuplicateActionsEnum::None,
        string $dirRef = '',
    ): Cocktail {
        $cocktailExternal = Schema::fromArray($sourceData);

        $existingCocktail = Cocktail::whereRaw('LOWER(name) = ?', [mb_strtolower($cocktailExternal->cocktail->name, 'UTF-8')])->where('bar_id', $barId)->first();
        if ($duplicateAction === DuplicateActionsEnum::Skip && $existingCocktail !== null) {
            return $existingCocktail;
        }

        $matcher = new Matcher($barId, $this->ingredientService);

        // Add images
        $cocktailImages = [];
        foreach ($cocktailExternal->cocktail->images as $image) {
            if ($image->uri) {
                $manager = ImageManager::imagick();

                try {
                    $imageDTO = new Image(
                        $manager->read(file_get_contents($dirRef . $image->getLocalFilePath())),
                        $image->copyright
                    );

                    $cocktailImages[] = $this->imageService->uploadAndSaveImages([$imageDTO], 1)[0]->id;
                } catch (Throwable $e) {
                    Log::error('Importing image error: ' . $e->getMessage());
                }
            }
        }

        // Match glass
        $glassId = null;
        if ($cocktailExternal->cocktail->glass) {
            $glassId = $matcher->matchGlassByName($cocktailExternal->cocktail->glass);
        }

        // Match method
        $methodId = null;
        if ($cocktailExternal->cocktail->method) {
            $methodId = $matcher->matchMethodByName($cocktailExternal->cocktail->method);
        }

        // Match ingredients
        $externalIngredients = collect($cocktailExternal->ingredients);
        $ingredients = [];
        $sort = 1;
        foreach ($cocktailExternal->cocktail->ingredients as $scrapedIngredient) {
            $foundExternalIngredient = $externalIngredients->firstWhere('id', $scrapedIngredient->id);
            $ingredientId = $matcher->matchOrCreateIngredientByName(
                new IngredientDTO(
                    $barId,
                    $foundExternalIngredient->name,
                    $userId,
                    null,
                    $foundExternalIngredient->strength,
                    $foundExternalIngredient->description,
                    $foundExternalIngredient->origin
                ),
            );

            $substitutes = [];
            foreach ($scrapedIngredient->substitutes as $substitute) {
                $foundExternalSubIngredient = $externalIngredients->firstWhere('id', $substitute->id);
                $substitutes[] = new SubstituteDTO(
                    $matcher->matchOrCreateIngredientByName(
                        new IngredientDTO(
                            $barId,
                            $foundExternalSubIngredient->name,
                            $userId,
                            null,
                            $foundExternalSubIngredient->strength,
                            $foundExternalSubIngredient->description,
                            $foundExternalSubIngredient->origin
                        )
                    ),
                    $substitute->amount,
                    $substitute->amountMax,
                    $substitute->units,
                );
            }

            $ingredient = new CocktailIngredientDTO(
                $ingredientId,
                $externalIngredients->firstWhere('id', $scrapedIngredient->id)->name,
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
            $cocktailExternal->cocktail->name,
            $cocktailExternal->cocktail->instructions,
            $userId,
            $barId,
            $cocktailExternal->cocktail->description,
            $cocktailExternal->cocktail->source,
            $cocktailExternal->cocktail->garnish,
            $glassId,
            $methodId,
            $cocktailExternal->cocktail->tags,
            $ingredients,
            $cocktailImages,
        );

        if ($duplicateAction === DuplicateActionsEnum::Overwrite && $existingCocktail !== null) {
            return $this->cocktailService->updateCocktail($existingCocktail->id, $cocktailDTO);
        }

        return $this->cocktailService->createCocktail($cocktailDTO);
    }
}
