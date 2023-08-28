<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SetupBar
{
    public function openBar(Bar $bar, User $user, array $flags = []): bool
    {
        $startBase = microtime(true);
        $this->importBaseData('glasses', resource_path('/data/base_glasses.yml'), $bar->id, $user->id);
        $this->importBaseData('cocktail_methods', resource_path('/data/base_methods.yml'), $bar->id, $user->id);
        $this->importBaseData('utensils', resource_path('/data/base_utensils.yml'), $bar->id, $user->id);
        $this->importBaseData('ingredient_categories', resource_path('/data/base_ingredient_categories.yml'), $bar->id, $user->id);
        $endBase = microtime(true);

        $startIngredients = microtime(true);
        $this->importIngredients(resource_path('/data/base_ingredients.yml'), $bar, $user);
        $endIngredients = microtime(true);

        $startCocktails = microtime(true);
        $this->importBaseCocktails(resource_path('/data/base_cocktails.yml'), $bar, $user);
        $endCocktails = microtime(true);

        Log::info(sprintf('[BAR_OPEN] Bar %s finished importing data. Base: %s | Ingredients: %s | Cocktails: %s', $bar->id, ($endBase - $startBase), ($endIngredients - $startIngredients), ($endCocktails - $startCocktails)));

        return true;
    }

    private function importBaseData(string $tableName, string $filepath, int $barId, int $userId): void
    {
        $data = Cache::remember('ba:data-import:' . $filepath, 60 * 60 * 24 * 7, function () use ($filepath) {
            return Yaml::parseFile($filepath);
        });

        $importData = array_map(function (array $item) use ($barId, $userId) {
            $item['bar_id'] = $barId;
            $item['created_user_id'] = $userId;
            $item['created_at'] = now();
            $item['updated_at'] = now();

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

        $ingredientsToInsert = [];
        $imagesToInsert = [];
        $imagesBasePath = 'ingredients/' . $bar->id . '/';

        foreach ($ingredients as $ingredient) {
            $category = $categories->firstWhere('name', $ingredient['category']);
            $slug = Str::slug($ingredient['name']) . '-' . $bar->id;
            $ingredientsToInsert[] = [
                'bar_id' => $bar->id,
                'slug' => $slug,
                'name' => $ingredient['name'],
                'ingredient_category_id' => $category->id,
                'strength' => $ingredient['strength'],
                'description' => $ingredient['description'],
                'origin' => $ingredient['origin'],
                'color' => $ingredient['color'],
                'created_user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // For performance, manually copy the files and create image references
            if (isset($ingredient['images'][0]['resource_path'])) {
                $fullImagePath = resource_path('data/' . $ingredient['images'][0]['resource_path']);
                if (!file_exists($fullImagePath)) {
                    continue;
                }

                $disk = Storage::disk('bar-assistant');

                $disk->makeDirectory($imagesBasePath);

                $imageFilePath = $imagesBasePath . $slug . '_' . Str::random(6) . '.png';
                copy(
                    $fullImagePath,
                    $disk->path($imageFilePath)
                );

                $imagesToInsert[$slug] = [
                    'copyright' => $ingredient['images'][0]['copyright'] ?? null,
                    'file_path' => $imageFilePath,
                    'file_extension' => 'png',
                    'created_user_id' => $user->id,
                    'sort' => 1,
                    'placeholder_hash' => $ingredient['images'][0]['placeholder_hash'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('ingredients')->insert($ingredientsToInsert);

        $ingredients = DB::table('ingredients')->where('bar_id', $bar->id)->get();
        foreach ($ingredients as $ingredient) {
            if (array_key_exists($ingredient->slug, $imagesToInsert)) {
                $imagesToInsert[$ingredient->slug]['imageable_type'] = \Kami\Cocktail\Models\Ingredient::class;
                $imagesToInsert[$ingredient->slug]['imageable_id'] = $ingredient->id;
            }
        }

        DB::table('images')->insert(array_values($imagesToInsert));
    }

    private function importBaseCocktails(string $filepath, Bar $bar, User $user): void
    {
        $cocktails = Cache::remember('ba:data-import:' . $filepath, 60 * 60 * 24 * 7, function () use ($filepath) {
            return Yaml::parseFile($filepath);
        });

        $dbIngredients = DB::table('ingredients')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $bar->id)->get()->keyBy('name')->map(fn ($row) => $row->id)->toArray();
        $dbGlasses = DB::table('glasses')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $bar->id)->get()->keyBy('name')->map(fn ($row) => $row->id)->toArray();
        $dbMethods = DB::table('cocktail_methods')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $bar->id)->get()->keyBy('name')->map(fn ($row) => $row->id)->toArray();

        $cocktailIngredientsToInsert = [];
        $imagesToInsert = [];
        $tagsToInsert = [];
        $imagesBasePath = 'cocktails/' . $bar->id . '/';

        foreach ($cocktails as $cocktail) {
            $slug = Str::slug($cocktail['name']) . '-' . $bar->id;

            $cocktailId = DB::table('cocktails')->insertGetId([
                'slug' => $slug,
                'name' => $cocktail['name'],
                'instructions' => $cocktail['instructions'],
                'description' => $cocktail['description'] ?? null,
                'garnish' => $cocktail['garnish'] ?? null,
                'source' => $cocktail['source'] ?? null,
                'abv' => $cocktail['abv'] ?? null,
                'created_user_id' => $user->id,
                'glass_id' => $dbGlasses[strtolower($cocktail['glass'])] ?? null,
                'cocktail_method_id' => $dbMethods[strtolower($cocktail['method'])] ?? null,
                'bar_id' => $bar->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($cocktail['tags'] as $tag) {
                $tag = Tag::firstOrCreate([
                    'name' => trim($tag),
                    'bar_id' => $bar->id,
                ]);
                $tagsToInsert[] = [
                    'tag_id' => $tag->id,
                    'cocktail_id' => $cocktailId,
                ];
            }

            $sort = 1;
            foreach ($cocktail['ingredients'] as $cocktailIngredient) {
                $cocktailIngredientsToInsert[] = [
                    'cocktail_id' => $cocktailId,
                    'ingredient_id' => $dbIngredients[strtolower($cocktailIngredient['name'])] ?? null,
                    'amount' => $cocktailIngredient['amount'],
                    'units' => $cocktailIngredient['units'],
                    'optional' => $cocktailIngredient['optional'],
                    'sort' => $sort,
                ];
                $sort++;
            }

            // For performance, manually copy the files and create image references
            if (isset($cocktail['images'][0]['resource_path'])) {
                $fullImagePath = resource_path('data/' . $cocktail['images'][0]['resource_path']);
                if (!file_exists($fullImagePath)) {
                    continue;
                }
                $disk = Storage::disk('bar-assistant');

                $disk->makeDirectory($imagesBasePath);

                $imageFilePath = $imagesBasePath . $slug . '_' . Str::random(6) . '.jpg';
                copy(
                    $fullImagePath,
                    $disk->path($imageFilePath)
                );

                $imagesToInsert[$slug] = [
                    'imageable_type' => \Kami\Cocktail\Models\Cocktail::class,
                    'imageable_id' => $cocktailId,
                    'copyright' => $cocktail['images'][0]['copyright'] ?? null,
                    'file_path' => $imageFilePath,
                    'file_extension' => 'jpg',
                    'created_user_id' => $user->id,
                    'sort' => 1,
                    'placeholder_hash' => $cocktail['images'][0]['placeholder_hash'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('cocktail_ingredients')->insert($cocktailIngredientsToInsert);
        DB::table('images')->insert($imagesToInsert);
        DB::table('cocktail_tag')->insert($tagsToInsert);
    }
}
