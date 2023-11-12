<?php

declare(strict_types=1);

namespace Kami\Cocktail\Import;

use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FromLocalData2
{
    public function process(Bar $bar, User $user, array $flags = []): bool
    {
        $this->importBaseData('glasses', resource_path('/data/base_glasses.yml'), $bar->id);
        $this->importBaseData('cocktail_methods', resource_path('/data/base_methods.yml'), $bar->id);
        $this->importBaseData('utensils', resource_path('/data/base_utensils.yml'), $bar->id);
        $this->importBaseData('ingredient_categories', resource_path('/data/base_ingredient_categories.yml'), $bar->id);

        if (in_array('ingredients', $flags)) {
            $this->importIngredients($bar, $user);
        }

        if (in_array('ingredients', $flags) && in_array('cocktails', $flags)) {
            $this->importBaseCocktails($bar, $user);
        }

        /** @phpstan-ignore-next-line */
        Ingredient::where('bar_id', $bar->id)->searchable();
        /** @phpstan-ignore-next-line */
        Cocktail::where('bar_id', $bar->id)->searchable();

        return true;
    }

    private function importBaseData(string $tableName, string $filepath, int $barId): void
    {
        $data = Cache::remember('ba:data-import:' . $filepath, 60 * 60 * 24 * 7, function () use ($filepath) {
            return Yaml::parseFile($filepath);
        });

        $importData = array_map(function (array $item) use ($barId) {
            $item['bar_id'] = $barId;
            $item['created_at'] = now();
            $item['updated_at'] = null;

            return $item;
        }, $data);

        DB::table($tableName)->insert($importData);
    }

    private function importIngredients(Bar $bar, User $user): void
    {
        /** @var \Illuminate\Support\Facades\Storage */
        $dataDisk = Storage::disk('data-files');
        /** @var \Illuminate\Support\Facades\Storage */
        $uploadsDisk = Storage::disk('uploads');

        $ingredients = [];
        foreach ($dataDisk->files('ingredients') as $ingredientFile) {
            $ingredients[] = Yaml::parseFile($dataDisk->path($ingredientFile));
        }

        $categories = DB::table('ingredient_categories')->select('id', 'name')->where('bar_id', $bar->id)->get();

        $ingredientsToInsert = [];
        $imagesToInsert = [];
        $barImagesDir = 'ingredients/' . $bar->id . '/';

        foreach ($ingredients as $ingredient) {
            $category = $categories->firstWhere('name', $ingredient['category']);
            $slug = Str::slug($ingredient['name']) . '-' . $bar->id;
            $ingredientsToInsert[] = [
                'bar_id' => $bar->id,
                'slug' => $slug,
                'name' => $ingredient['name'],
                'ingredient_category_id' => $category->id ?? null,
                'strength' => $ingredient['strength'] ?? null,
                'description' => $ingredient['description'] ?? null,
                'origin' => $ingredient['origin'] ?? null,
                'color' => $ingredient['color'] ?? null,
                'created_user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => null,
            ];

            // For performance, manually copy the files and create image references
            foreach ($ingredient['images'] ?? [] as $image) {
                $baseSrcImagePath = 'ingredients/images/' . $image['file_name'];
                if (!$dataDisk->fileExists($baseSrcImagePath)) {
                    continue;
                }

                $uploadsDisk->makeDirectory($barImagesDir);

                $targetImagePath = $barImagesDir . $slug . '_' . Str::random(6) . '.png';
                copy(
                    $dataDisk->path($baseSrcImagePath),
                    $uploadsDisk->path($targetImagePath)
                );

                $imagesToInsert[$slug] = [
                    'copyright' => $image['copyright'] ?? null,
                    'file_path' => $targetImagePath,
                    'file_extension' => 'png', // TODO
                    'created_user_id' => $user->id,
                    'sort' => 1,
                    'placeholder_hash' => $image['placeholder_hash'] ?? null,
                    'created_at' => now(),
                    'updated_at' => null,
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

    private function importBaseCocktails(Bar $bar, User $user): void
    {
        /** @var \Illuminate\Support\Facades\Storage */
        $dataDisk = Storage::disk('data-files');
        /** @var \Illuminate\Support\Facades\Storage */
        $uploadsDisk = Storage::disk('uploads');

        $cocktails = Cache::remember('ba:data-import:cocktails', 60 * 60 * 24 * 7, function () use ($dataDisk) {
            $cocktails = [];
            foreach ($dataDisk->files('cocktails') as $cocktailFile) {
                $cocktails[] = Yaml::parseFile($dataDisk->path($cocktailFile));
            }

            return $cocktails;
        });

        $dbIngredients = DB::table('ingredients')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $bar->id)->get()->keyBy('name')->map(fn ($row) => $row->id)->toArray();
        $dbGlasses = DB::table('glasses')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $bar->id)->get()->keyBy('name')->map(fn ($row) => $row->id)->toArray();
        $dbMethods = DB::table('cocktail_methods')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $bar->id)->get()->keyBy('name')->map(fn ($row) => $row->id)->toArray();

        $cocktailIngredientsToInsert = [];
        $imagesToInsert = [];
        $tagsToInsert = [];
        $barImagesDir = 'cocktails/' . $bar->id . '/';

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
                'glass_id' => $dbGlasses[strtolower($cocktail['glass'] ?? '')] ?? null,
                'cocktail_method_id' => $dbMethods[strtolower($cocktail['method'] ?? '')] ?? null,
                'bar_id' => $bar->id,
                'created_at' => now(),
                'updated_at' => null,
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
                    'optional' => $cocktailIngredient['optional'] ?? false,
                    'sort' => $sort,
                ];
                $sort++;
            }

            // For performance, manually copy the files and create image references
            foreach ($cocktail['images'] ?? [] as $image) {
                $baseSrcImagePath = 'cocktails/images/' . $image['file_name'];
                if (!$dataDisk->fileExists($baseSrcImagePath)) {
                    continue;
                }

                $fileExtension = \Illuminate\Support\Facades\File::extension($dataDisk->path($baseSrcImagePath));

                $uploadsDisk->makeDirectory($barImagesDir);

                $targetImagePath = $barImagesDir . $slug . '_' . Str::random(6) . '.' . $fileExtension;
                copy(
                    $dataDisk->path($baseSrcImagePath),
                    $uploadsDisk->path($targetImagePath)
                );

                $imagesToInsert[$slug] = [
                    'imageable_type' => \Kami\Cocktail\Models\Cocktail::class,
                    'imageable_id' => $cocktailId,
                    'copyright' => $image['copyright'] ?? null,
                    'file_path' => $targetImagePath,
                    'file_extension' => $fileExtension,
                    'created_user_id' => $user->id,
                    'sort' => 1,
                    'placeholder_hash' => $image['placeholder_hash'] ?? null,
                    'created_at' => now(),
                    'updated_at' => null,
                ];
            }
        }

        DB::table('cocktail_ingredients')->insert($cocktailIngredientsToInsert);
        DB::table('images')->insert($imagesToInsert);
        DB::table('cocktail_tag')->insert($tagsToInsert);
    }

    // public function copyResourceImage(string $baseSrcImagePath, string $barImagesDir, string $newFilename): ?string
    // {
    //     /** @var \Illuminate\Support\Facades\Storage */
    //     $dataDisk = Storage::disk('data-files');
    //     /** @var \Illuminate\Support\Facades\Storage */
    //     $uploadsDisk = Storage::disk('uploads');

    //     if (!$dataDisk->fileExists($baseSrcImagePath)) {
    //         return null;
    //     }

    //     $fileExtension = \Illuminate\Support\Facades\File::extension($dataDisk->path($baseSrcImagePath));

    //     $uploadsDisk->makeDirectory($barImagesDir);

    //     $targetImagePath = $barImagesDir . $slug . '_' . Str::random(6) . '.' . $fileExtension;
    //     copy(
    //         $dataDisk->path($baseSrcImagePath),
    //         $uploadsDisk->path($targetImagePath)
    //     );
    // }
}
