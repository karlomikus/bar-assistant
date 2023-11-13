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
use Illuminate\Support\Facades\File;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class FromRecipesData
{
    public function process(FilesystemAdapter $dataDisk, Bar $bar, User $user, array $flags = []): bool
    {
        if ($dataDisk->fileExists('base_glasses.yml')) {
            $this->importBaseData('glasses', $dataDisk->path('base_glasses.yml'), $bar->id);
        }

        if ($dataDisk->fileExists('base_methods.yml')) {
            $this->importBaseData('cocktail_methods', $dataDisk->path('base_methods.yml'), $bar->id);
        }

        if ($dataDisk->fileExists('base_utensils.yml')) {
            $this->importBaseData('utensils', $dataDisk->path('base_utensils.yml'), $bar->id);
        }

        if ($dataDisk->fileExists('base_ingredient_categories.yml')) {
            $this->importBaseData('ingredient_categories', $dataDisk->path('base_ingredient_categories.yml'), $bar->id);
        }

        if (in_array('ingredients', $flags)) {
            $this->importIngredients($dataDisk, $bar, $user);
        }

        if (in_array('ingredients', $flags) && in_array('cocktails', $flags)) {
            $this->importBaseCocktails($dataDisk, $bar, $user);
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

    private function importIngredients(FilesystemAdapter $dataDisk, Bar $bar, User $user): void
    {
        /** @var \Illuminate\Support\Facades\Storage */
        $uploadsDisk = Storage::disk('uploads');

        $ingredients = Cache::remember('ba:data-import:ingredients', 60 * 60 * 24 * 7, function () use ($dataDisk) {
            $ingredients = [];
            foreach ($dataDisk->files('ingredients') as $ingredientFile) {
                $ingredients[] = Yaml::parseFile($dataDisk->path($ingredientFile));
            }

            return $ingredients;
        });

        $categories = DB::table('ingredient_categories')->select('id', 'name')->where('bar_id', $bar->id)->get();

        $ingredientsToInsert = [];
        $imagesToInsert = [];
        $barImagesDir = 'ingredients/' . $bar->id . '/';
        $uploadsDisk->makeDirectory($barImagesDir);

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
                $fileExtension = File::extension($dataDisk->path($baseSrcImagePath));
                $targetImagePath = $barImagesDir . $slug . '_' . Str::random(6) . '.' . $fileExtension;

                $this->copyResourceImage($dataDisk, $baseSrcImagePath, $targetImagePath);

                $imagesToInsert[$slug] = [
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

        DB::table('ingredients')->insert($ingredientsToInsert);

        $ingredients = DB::table('ingredients')->where('bar_id', $bar->id)->get();
        foreach ($ingredients as $ingredient) {
            if (array_key_exists($ingredient->slug, $imagesToInsert)) {
                $imagesToInsert[$ingredient->slug]['imageable_type'] = Ingredient::class;
                $imagesToInsert[$ingredient->slug]['imageable_id'] = $ingredient->id;
            }
        }

        DB::table('images')->insert(array_values($imagesToInsert));
    }

    private function importBaseCocktails(FilesystemAdapter $dataDisk, Bar $bar, User $user): void
    {
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
        $uploadsDisk->makeDirectory($barImagesDir);

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
                $fileExtension = File::extension($dataDisk->path($baseSrcImagePath));
                $targetImagePath = $barImagesDir . $slug . '_' . Str::random(6) . '.' . $fileExtension;

                $this->copyResourceImage($dataDisk, $baseSrcImagePath, $targetImagePath);

                $imagesToInsert[] = [
                    'imageable_type' => Cocktail::class,
                    'imageable_id' => $cocktailId,
                    'copyright' => $image['copyright'] ?? null,
                    'file_path' => $targetImagePath,
                    'file_extension' => $fileExtension,
                    'created_user_id' => $user->id,
                    'sort' => $image['sort'] ?? 1,
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

    private function copyResourceImage(FilesystemAdapter $dataDisk, string $baseSrcImagePath, string $targetImagePath): void
    {
        /** @var \Illuminate\Support\Facades\Storage */
        $uploadsDisk = Storage::disk('uploads');

        if (!$dataDisk->fileExists($baseSrcImagePath)) {
            return;
        }

        copy(
            $dataDisk->path($baseSrcImagePath),
            $uploadsDisk->path($targetImagePath)
        );
    }
}
