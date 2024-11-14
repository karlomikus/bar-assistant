<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use Throwable;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\External\Matcher;
use Kami\Cocktail\External\Model\Schema;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;
use Kami\Cocktail\Services\Image\ImageService;
use Kami\Cocktail\OpenAPI\Schemas\ImageRequest;
use Kami\Cocktail\OpenAPI\Schemas\CocktailRequest as CocktailDTO;
use Kami\Cocktail\OpenAPI\Schemas\IngredientRequest as IngredientDTO;
use Kami\Cocktail\OpenAPI\Schemas\CocktailIngredientRequest as CocktailIngredientDTO;
use Kami\Cocktail\OpenAPI\Schemas\CocktailIngredientSubstituteRequest as SubstituteDTO;

class FromJsonSchema
{
    private Matcher $matcher;

    public function __construct(
        private readonly CocktailService $cocktailService,
        private readonly IngredientService $ingredientService,
        private readonly ImageService $imageService,
        private readonly int $barId,
        private readonly int $userId,
    ) {
        $this->matcher = new Matcher($this->barId, $this->ingredientService);
    }

    /**
     * @param array<mixed> $sourceData
     */
    public function process(
        array $sourceData,
        DuplicateActionsEnum $duplicateAction = DuplicateActionsEnum::None,
        string $imageDirectoryBasePath = '',
    ): Cocktail {
        $cocktailExternal = Schema::fromDraft2Array($sourceData);

        $existingCocktail = $this->matcher->matchCocktailByName(mb_strtolower($cocktailExternal->cocktail->name, 'UTF-8'));
        if ($duplicateAction === DuplicateActionsEnum::Skip && $existingCocktail !== null) {
            return Cocktail::find($existingCocktail);
        }

        // Add images
        $cocktailImages = [];
        foreach ($cocktailExternal->cocktail->images as $image) {
            try {
                if ($image->uri && $imageContents = file_get_contents($imageDirectoryBasePath . $image->getLocalFilePath())) {
                    $imageDTO = new ImageRequest(
                        image: $imageContents,
                        copyright: $image->copyright
                    );

                    $cocktailImages[] = $this->imageService->uploadAndSaveImages([$imageDTO], 1)[0]->id;
                }
            } catch (Throwable $e) {
                Log::error('Importing image error: ' . $e->getMessage());
            }
        }

        // Match glass
        $glassId = null;
        if ($cocktailExternal->cocktail->glass) {
            $glassId = $this->matcher->matchGlassByName($cocktailExternal->cocktail->glass);
        }

        // Match method
        $methodId = null;
        if ($cocktailExternal->cocktail->method) {
            $methodId = $this->matcher->matchMethodByName($cocktailExternal->cocktail->method);
        }

        // Match ingredients
        $externalIngredients = collect($cocktailExternal->ingredients);
        $ingredients = [];
        $sort = 1;
        foreach ($cocktailExternal->cocktail->ingredients as $scrapedIngredient) {
            $foundExternalIngredient = $externalIngredients->firstWhere('id', $scrapedIngredient->ingredient->id);
            $ingredientId = $this->matcher->matchOrCreateIngredientByName(
                new IngredientDTO(
                    $this->barId,
                    $foundExternalIngredient->name,
                    $this->userId,
                    null,
                    $foundExternalIngredient->strength,
                    $foundExternalIngredient->description,
                    $foundExternalIngredient->origin
                ),
            );

            $substitutes = [];
            foreach ($scrapedIngredient->substitutes as $substitute) {
                $foundExternalSubIngredient = $externalIngredients->firstWhere('id', $substitute->ingredient->id);
                $substitutes[] = new SubstituteDTO(
                    $this->matcher->matchOrCreateIngredientByName(
                        new IngredientDTO(
                            $this->barId,
                            $foundExternalSubIngredient->name,
                            $this->userId,
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
                $externalIngredients->firstWhere('id', $scrapedIngredient->ingredient->id)->name,
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
            $this->userId,
            $this->barId,
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
            return $this->cocktailService->updateCocktail($existingCocktail, $cocktailDTO);
        }

        return $this->cocktailService->createCocktail($cocktailDTO);
    }
}
