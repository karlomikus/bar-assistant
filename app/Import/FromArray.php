<?php

declare(strict_types=1);

namespace Kami\Cocktail\Import;

use Throwable;
use Kami\Cocktail\Models\Glass;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\DataObjects\Image;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;
use Intervention\Image\Facades\Image as ImageProcessor;
use Kami\Cocktail\DataObjects\Cocktail\Cocktail as CocktailDTO;
use Kami\Cocktail\DataObjects\Cocktail\Substitute as SubstituteDTO;
use Kami\Cocktail\DataObjects\Ingredient\Ingredient as IngredientDTO;
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
     *
     * @param array<mixed> $sourceData
     */
    public function process(
        array $sourceData,
        int $userId,
        int $barId,
        DuplicateActionsEnum $duplicateAction = DuplicateActionsEnum::None
    ): Cocktail {
        if ($duplicateAction === DuplicateActionsEnum::Skip) {
            $existingCocktail = Cocktail::whereRaw('LOWER(name) = ?', [strtolower($sourceData['name'])])->first();
            if ($existingCocktail !== null) {
                return $existingCocktail;
            }
        }

        $dbIngredients = DB::table('ingredients')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $barId)->get()->keyBy('name');
        $dbGlasses = DB::table('glasses')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $barId)->get()->keyBy('name');
        $dbMethods = DB::table('cocktail_methods')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $barId)->get()->keyBy('name');

        $defaultDescription = 'Created from "' . $sourceData['source'] . '"';

        // Add images
        $cocktailImages = [];
        foreach ($sourceData['images'] ?? [] as $image) {
            $imageSource = null;
            if (array_key_exists('url', $image)) {
                $imageSource = $image['url'];
            }

            if ($imageSource) {
                try {
                    $imageDTO = new Image(
                        ImageProcessor::make($imageSource),
                        $image['copyright'] ?? null
                    );

                    $cocktailImages[] = $this->imageService->uploadAndSaveImages([$imageDTO], 1)[0]->id;
                } catch (Throwable $e) {
                    Log::error($e->getMessage());
                }
            }
        }

        // Match glass
        $glassId = null;
        if ($sourceData['glass']) {
            $glassNameLower = strtolower($sourceData['glass']);
            if ($dbGlasses->has($glassNameLower)) {
                $glassId = $dbGlasses->get($glassNameLower)->id;
            } elseif ($sourceData['glass'] !== null) {
                $newGlass = new Glass();
                $newGlass->name = ucfirst($sourceData['glass']);
                $newGlass->description = $defaultDescription;
                $newGlass->bar_id = $barId;
                $newGlass->save();
                $dbGlasses->put($glassNameLower, $newGlass->id);
                $glassId = $newGlass->id;
            }
        }

        // Match method
        $methodId = null;
        if ($sourceData['method']) {
            $methodNameLower = strtolower($sourceData['method']);
            if ($dbMethods->has($methodNameLower)) {
                $methodId = $dbMethods->get($methodNameLower)->id;
            }
        }

        // Match ingredients
        $ingredients = [];
        $sort = 1;
        foreach ($sourceData['ingredients'] as $scrapedIngredient) {
            if ($dbIngredients->has(strtolower($scrapedIngredient['name']))) {
                $ingredientId = $dbIngredients->get(strtolower($scrapedIngredient['name']))->id;
            } else {
                $ingredientDTO = new IngredientDTO(
                    $barId,
                    ucfirst($scrapedIngredient['name']),
                    $userId,
                    null,
                    $scrapedIngredient['strength'] ?? 0.0,
                    $scrapedIngredient['description'] ?? $defaultDescription,
                    $scrapedIngredient['origin'] ?? null
                );
                $newIngredient = $this->ingredientService->createIngredient($ingredientDTO);
                $dbIngredients->put(strtolower($scrapedIngredient['name']), $newIngredient);
                $ingredientId = $newIngredient->id;
            }

            $substitutes = [];
            if (array_key_exists('substitutes', $scrapedIngredient) && !empty($scrapedIngredient['substitutes'])) {
                foreach ($scrapedIngredient['substitutes'] as $substituteName) {
                    if ($dbIngredients->has(strtolower($substituteName))) {
                        $substitutes[] = new SubstituteDTO($dbIngredients->get(strtolower($substituteName))->id);
                    } else {
                        $ingredientDTO = new IngredientDTO(
                            $barId,
                            ucfirst($substituteName),
                            $userId,
                        );
                        $newIngredient = $this->ingredientService->createIngredient($ingredientDTO);
                        $dbIngredients->put(strtolower($substituteName), $newIngredient);
                        $substitutes[] = new SubstituteDTO($newIngredient->id);
                    }
                }
            }

            $ingredient = new CocktailIngredientDTO(
                $ingredientId,
                $scrapedIngredient['name'],
                $scrapedIngredient['amount'],
                $scrapedIngredient['units'],
                $sort,
                $scrapedIngredient['optional'] ?? false,
                $substitutes,
                $scrapedIngredient['amount_max'] ?? null,
                $scrapedIngredient['note'] ?? null,
            );

            $ingredients[] = $ingredient;
            $sort++;
        }

        $cocktailDTO = new CocktailDTO(
            $sourceData['name'],
            $sourceData['instructions'],
            $userId,
            $barId,
            $sourceData['description'],
            $sourceData['source'],
            $sourceData['garnish'],
            $glassId,
            $methodId,
            $sourceData['tags'],
            $ingredients,
            $cocktailImages,
        );

        if ($duplicateAction === DuplicateActionsEnum::Overwrite) {
            $existingCocktail = DB::table('cocktails')->select('id')->whereRaw('LOWER(name) = ?', [strtolower($sourceData['name'])])->first();
            if ($existingCocktail !== null) {
                return $this->cocktailService->updateCocktail($existingCocktail->id, $cocktailDTO);
            }
        }

        return $this->cocktailService->createCocktail($cocktailDTO);
    }
}
