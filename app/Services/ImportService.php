<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use ZipArchive;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Glass;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\DataObjects\Image;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Exceptions\ImportException;
use Kami\Cocktail\Services\IngredientService;
use Intervention\Image\Facades\Image as ImageProcessor;
use Kami\Cocktail\Models\Collection as CocktailCollection;
use Kami\Cocktail\DataObjects\Cocktail\Cocktail as CocktailDTO;
use Kami\Cocktail\DataObjects\Cocktail\Ingredient as IngredientDTO;

class ImportService
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
    public function importCocktailFromArray(array $sourceData, int $userId, int $barId): Cocktail
    {
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
                $newGlass->created_user_id = $userId;
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
                $newIngredient = $this->ingredientService->createIngredient(
                    $barId,
                    ucfirst($scrapedIngredient['name']),
                    1,
                    $userId,
                    $scrapedIngredient['strength'] ?? 0.0,
                    $scrapedIngredient['description'] ?? $defaultDescription,
                    $scrapedIngredient['origin'] ?? null
                );
                $dbIngredients->put(strtolower($scrapedIngredient['name']), $newIngredient);
                $ingredientId = $newIngredient->id;
            }

            $substitutes = [];
            if (array_key_exists('substitutes', $scrapedIngredient) && !empty($scrapedIngredient['substitutes'])) {
                foreach ($scrapedIngredient['substitutes'] as $substituteName) {
                    if ($dbIngredients->has(strtolower($substituteName))) {
                        $substitutes[] = $dbIngredients->get(strtolower($substituteName))->id;
                    }
                }
            }

            $ingredient = new IngredientDTO(
                $ingredientId,
                $scrapedIngredient['name'],
                $scrapedIngredient['amount'],
                $scrapedIngredient['units'],
                $sort,
                $scrapedIngredient['optional'] ?? false,
                $substitutes,
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

        return $this->cocktailService->createCocktail($cocktailDTO);
    }

    /**
     * Import zipped data from another BA instance
     *
     * @param string $zipFilePath
     * @return void
     */
    public function importFromZipFile(string $zipFilePath): void
    {
        Log::info(sprintf('[IMPORT_SERVICE] Started importing data from "%s"', $zipFilePath));
        $importTimeStart = microtime(true);

        $unzipPath = storage_path('temp/export/import_' . Str::random(8));
        /** @var \Illuminate\Support\Facades\Storage */
        $disk = Storage::build([
            'driver' => 'local',
            'root' => $unzipPath,
        ]);

        // Extract the archive
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            $message = sprintf('[IMPORT_SERVICE] Error opening zip archive with filepath "%s"', $zipFilePath);
            Log::error($message);

            throw new ImportException($message);
        }
        $zip->extractTo($unzipPath);
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

        foreach (array_reverse($importOrder) as $tableName) {
            try {
                DB::table($tableName)->truncate();
            } catch (Throwable) {
                Log::error(sprintf('[IMPORT_SERVICE] Unable to truncate table "%s"', $tableName));
            }
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        foreach ($importOrder as $tableName) {
            $data = json_decode(file_get_contents($disk->path($tableName . '.json')), true);

            foreach ($data as $row) {
                try {
                    DB::table($tableName)->insert($row);
                } catch (Throwable) {
                    Log::error(sprintf('[IMPORT_SERVICE] Unable to import row with id "%s" to table "%s"', $row['id'], $tableName));
                }
            }
        }
        DB::statement('PRAGMA foreign_keys = ON');

        /** @var \Illuminate\Support\Facades\Storage */
        $baDisk = Storage::disk('bar-assistant');

        foreach (glob($disk->path('uploads/cocktails/*')) as $pathFrom) {
            if (!copy($pathFrom, $baDisk->path('cocktails/' . basename($pathFrom)))) {
                Log::error(sprintf('[IMPORT_SERVICE] Unable to copy cocktail image from path "%s"', $pathFrom));
            }
        }

        foreach (glob($disk->path('uploads/ingredients/*')) as $pathFrom) {
            if (!copy($pathFrom, $baDisk->path('ingredients/' . basename($pathFrom)))) {
                Log::error(sprintf('[IMPORT_SERVICE] Unable to copy ingredient image from path "%s"', $pathFrom));
            }
        }

        $importTimeEnd = microtime(true);
        Log::info(sprintf('[IMPORT_SERVICE] Finished importing data in %s seconds', $importTimeEnd - $importTimeStart));

        $disk->deleteDirectory('/');
    }

    /**
     * @param array{name: string, description: string|null, cocktails: array<mixed>} $sourceData
     */
    public function importCocktailCollection(array $sourceData, int $userId, int $barId): CocktailCollection
    {
        $collection = new CocktailCollection();
        $collection->name = $sourceData['name'];
        $collection->description = $sourceData['description'];
        // $collection->user_id = $userId; // TODO: Bar membership
        $collection->save();

        foreach ($sourceData['cocktails'] as $cocktail) {
            $cocktail = $this->importCocktailFromArray($cocktail, $userId, $barId);
            $cocktail->addToCollection($collection);
        }

        return $collection;
    }
}
