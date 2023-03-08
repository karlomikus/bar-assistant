<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Kami\Cocktail\Models\Glass;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\DataObjects\Image;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\DataObjects\Ingredient;
use Intervention\Image\ImageManagerStatic;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;

class ImportService
{
    public function __construct(
        private readonly CocktailService $cocktailService,
        private readonly IngredientService $ingredientService,
        private readonly ImageService $imageService
    ) {
    }

    /**
     * Create a cocktail from scraper data
     *
     * @param array<mixed> $sourceData Scraper data
     * @return Cocktail Database model of the cocktail
     */
    public function importFromScraper(array $sourceData): Cocktail
    {
        $dbIngredients = DB::table('ingredients')->select('id', DB::raw('LOWER(name) AS name'))->get()->keyBy('name');
        $dbGlasses = DB::table('glasses')->select('id', DB::raw('LOWER(name) AS name'))->get()->keyBy('name');

        // Add images
        $cocktailImages = [];
        if ($sourceData['image']['url']) {
            try {
                $imageDTO = new Image(
                    null,
                    ImageManagerStatic::make($sourceData['image']['url']),
                    $sourceData['image']['copyright'] ?? null
                );

                $cocktailImages[] = $this->imageService->uploadAndSaveImages([$imageDTO], 1)[0]->id;
            } catch (Throwable $e) {
                Log::error($e->getMessage());
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
                $newGlass->description = 'Created by scraper from ' . $sourceData['source'];
                $newGlass->save();
                $dbGlasses->put($glassNameLower, $newGlass->id);
                $glassId = $newGlass->id;
            }
        }

        // Match ingredients
        $ingredients = [];
        $sort = 1;
        foreach ($sourceData['ingredients'] as $scrapedIngredient) {
            if ($dbIngredients->has(strtolower($scrapedIngredient['name']))) {
                $ingredientId = $dbIngredients->get(strtolower($scrapedIngredient['name']))->id;
            } else {
                $newIngredient = $this->ingredientService->createIngredient(ucfirst($scrapedIngredient['name']), 1, 1, description: 'Created by scraper from ' . $sourceData['source']);
                $dbIngredients->put(strtolower($scrapedIngredient['name']), $newIngredient->id);
                $ingredientId = $newIngredient->id;
            }

            $ingredient = new Ingredient(
                $ingredientId,
                $scrapedIngredient['name'],
                $scrapedIngredient['amount'],
                $scrapedIngredient['units'],
                $sort,
                $scrapedIngredient['optional'] ?? false,
                $scrapedIngredient['substitutes'] ?? [],
            );

            $ingredients[] = $ingredient;
            $sort++;
        }

        // Add cocktail
        return $this->cocktailService->createCocktail(
            $sourceData['name'],
            $sourceData['instructions'],
            $ingredients,
            1,
            $sourceData['description'],
            $sourceData['garnish'],
            $sourceData['source'],
            $cocktailImages,
            $sourceData['tags'],
            $glassId
        );
    }
}
