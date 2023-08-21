<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\DataObjects\Image;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image as ImageProcessor;

class BarService
{
    public function __construct(
        private readonly ImportService $importService,
        private readonly IngredientService $ingredientService,
        private readonly ImageService $imageService
    ) {
    }

    public function openBar(Bar $bar, User $user, array $flags = []): bool
    {
        // TODO: Move to queue
        $startBase = microtime(true);
        $this->importBaseData('glasses', resource_path('/data/base_glasses.yml'), $bar->id);
        $this->importBaseData('cocktail_methods', resource_path('/data/base_methods.yml'), $bar->id);
        $this->importBaseData('utensils', resource_path('/data/base_utensils.yml'), $bar->id);
        $this->importBaseData('ingredient_categories', resource_path('/data/base_ingredient_categories.yml'), $bar->id);
        $endBase = microtime(true);

        $startIngredients = microtime(true);
        $this->importIngredients(resource_path('/data/base_ingredients.yml'), $bar, $user);
        $endIngredients = microtime(true);

        $startCocktails = microtime(true);
        $this->importCocktails(resource_path('/data/iba_cocktails.yml'), $bar, $user);
        $endCocktails = microtime(true);

        Log::info(sprintf('[BAR_OPEN] Bar %s finished importing data. Base: %s | Ingredients: %s | Cocktails: %s', $bar->id, ($endBase - $startBase), ($endIngredients - $startIngredients), ($endCocktails - $startCocktails)));

        return true;
    }

    private function importBaseData(string $tableName, string $filepath, int $barId)
    {
        $data = Cache::remember('ba:data-import:' . $filepath, 60 * 60 * 24 * 7, function () use ($filepath) {
            return Yaml::parseFile($filepath);
        });

        $importData = array_map(function (array $item) use ($barId) {
            $item['bar_id'] = $barId;

            return $item;
        }, $data);

        DB::table($tableName)->insert($importData);
    }

    private function importIngredients(string $filepath, Bar $bar, User $user): void
    {
        $ingredients = Cache::remember('ba:data-import:' . $filepath, 60 * 60 * 24 * 7, function () use ($filepath) {
            return Yaml::parseFile($filepath);
        });

        $categories = DB::table('ingredient_categories')->select('id', 'name')->where('bar_id', $bar->id)->get();

        foreach ($ingredients as $ingredient) {
            $category = $categories->firstWhere('name', $ingredient['category']);

            $ingredientImages = [];
            $imageSource = null;
            if (isset($ingredient['images'][0]['resource_path'])) {
                $imageSource = resource_path($ingredient['images'][0]['resource_path']);
            }

            if ($imageSource) {
                try {
                    $imageDTO = new Image(
                        ImageProcessor::make($imageSource),
                        $ingredient['images'][0]['copyright'] ?? null
                    );
    
                    $ingredientImages[] = $this->imageService->uploadAndSaveImages([$imageDTO], $user->id)[0]->id;
                } catch (Throwable $e) {
                    Log::error($e->getMessage());
                }
            }

            $this->ingredientService->createIngredient(
                $bar->id,
                $ingredient['name'],
                $category->id,
                $user->id,
                $ingredient['strength'],
                $ingredient['description'],
                $ingredient['origin'],
                $ingredient['color'],
                null,
                $ingredientImages
            );
        }
    }

    private function importCocktails(string $filepath, Bar $bar, User $user): void
    {
        $cocktails = Cache::remember('ba:data-import:' . $filepath, 60 * 60 * 24 * 7, function () use ($filepath) {
            return Yaml::parseFile($filepath);
        });

        foreach ($cocktails as $cocktail) {
            $cocktail['images'][0]['resource_path'] = sprintf('data/cocktails/%s', Str::slug($cocktail['name']) . '.jpg');
            $this->importService->importCocktailFromArray($cocktail, $user->id, $bar->id);
        }
    }
}
