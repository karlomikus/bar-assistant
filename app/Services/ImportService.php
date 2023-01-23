<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use ZipArchive;
use Kami\Cocktail\Models\Glass;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Services\ImageService;
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

    public function importFromScraper(array $sourceData): Cocktail
    {
        $dbIngredients = DB::table('ingredients')->select('id', DB::raw('LOWER(name) AS name'))->get()->keyBy('name');
        $dbGlasses = DB::table('glasses')->select('id', DB::raw('LOWER(name) AS name'))->get()->keyBy('name');

        // Add images
        $cocktailImages = [];
        if ($sourceData['image']['url']) {
            $cocktailImages[] = $this->imageService->uploadImage(
                $sourceData['image']['url'],
                1,
                $sourceData['image']['copyright'] ?? null
            )->id;
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
        foreach ($sourceData['ingredients'] as &$scrapedIngredient) {
            if ($dbIngredients->has(strtolower($scrapedIngredient['name']))) {
                $scrapedIngredient['ingredient_id'] = $dbIngredients->get(strtolower($scrapedIngredient['name']))->id;
            } else {
                $newIngredient = $this->ingredientService->createIngredient(ucfirst($scrapedIngredient['name']), 1, 1, description: 'Created by scraper from ' . $sourceData['source']);
                $dbIngredients->put(strtolower($scrapedIngredient['name']), $newIngredient->id);
                $scrapedIngredient['ingredient_id'] = $newIngredient->id;
            }
        }

        // TODO: Substitutes

        // Add cocktail
        return $this->cocktailService->createCocktail(
            $sourceData['name'],
            $sourceData['instructions'],
            $sourceData['ingredients'],
            1,
            $sourceData['description'],
            $sourceData['garnish'],
            $sourceData['source'],
            $cocktailImages,
            $sourceData['tags'],
            $glassId
        );
    }

    public function importFromZipFile(string $zipFilePath): bool
    {
        $tempFolder = storage_path('uploads/temp/export_temp/');

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            return false;
        }
        $zip->extractTo($tempFolder);
        $zip->close();

        $importOrder = [
            'ingredient_categories',
            'glasses',
            'tags',
            'ingredients',
            'cocktails',
            'cocktail_ingredients',
            'cocktail_ingredient_substitutes',
            'cocktail_tag',
            'images',
        ];

        foreach ($importOrder as $tableName) {
            $data = json_decode(file_get_contents($tempFolder . $tableName . '.json'), true);

            foreach ($data as $row) {
                try {
                    DB::table($tableName)->insert($row);
                } catch (Throwable $e) {
                    dump(sprintf('Unable to import row with id "%s" to table "%s"', $row['id'], $tableName));
                }
            }
        }

        return true;
    }
}
