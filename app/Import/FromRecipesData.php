<?php

declare(strict_types=1);

namespace Kami\Cocktail\Import;

use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Symfony\Component\Yaml\Yaml;
use Kami\Cocktail\Models\Utensil;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Models\BarStatusEnum;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Contracts\Filesystem\Filesystem;

class FromRecipesData
{
    private Filesystem $uploadsDisk;

    public function __construct()
    {
        $this->uploadsDisk = Storage::disk('uploads');
    }

    public function process(FilesystemAdapter $dataDisk, Bar $bar, User $user, array $flags = []): bool
    {
        Log::debug(sprintf('Starting recipe import for "%s"', $bar->name));

        $timerStart = microtime(true);
        $bar->setStatus(BarStatusEnum::Provisioning)->save();

        $baseDataFiles = [
            'glasses' => 'base_glasses.yml',
            'cocktail_methods' => 'base_methods.yml',
            'utensils' => 'base_utensils.yml',
            'ingredient_categories' => 'base_ingredient_categories.yml',
        ];

        foreach ($baseDataFiles as $table => $file) {
            if ($dataDisk->fileExists($file)) {
                $this->importBaseData($table, $dataDisk->path($file), $bar->id);
            }
        }

        if (in_array('ingredients', $flags)) {
            $this->importIngredients($dataDisk, $bar, $user);
        }

        if (in_array('ingredients', $flags) && in_array('cocktails', $flags)) {
            $this->importBaseCocktails($dataDisk, $bar, $user);
        }

        $bar->setStatus(BarStatusEnum::Active)->save();

        /** @phpstan-ignore-next-line */
        Ingredient::where('bar_id', $bar->id)->with('category', 'images')->searchable();
        /** @phpstan-ignore-next-line */
        Cocktail::where('bar_id', $bar->id)->with('ingredients.ingredient', 'tags', 'images')->searchable();

        $timerEnd = microtime(true);

        Log::debug(sprintf('Importing recipes done in "%s" seconds', ($timerEnd - $timerStart)));

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

        // Skip duplicates
        $existing = DB::table($tableName)->select('name')->where('bar_id', $barId)->get()->keyBy(fn ($row) => strtolower($row->name))->toArray();
        $importData = array_filter($importData, function ($item) use ($existing) {
            if (array_key_exists(strtolower($item['name']), $existing)) {
                return false;
            }

            return true;
        });

        DB::table($tableName)->insert($importData);
    }

    private function importIngredients(FilesystemAdapter $dataDisk, Bar $bar, User $user): void
    {
        $ingredients = Cache::remember('ba:data-import:ingredients', 60 * 60 * 24 * 7, function () use ($dataDisk) {
            $ingredients = [];
            foreach ($dataDisk->files('ingredients') as $ingredientFile) {
                $ingredients[] = Yaml::parseFile($dataDisk->path($ingredientFile));
            }

            return $ingredients;
        });

        $categories = DB::table('ingredient_categories')->select('id', 'name')->where('bar_id', $bar->id)->get();
        $existingIngredients = DB::table('ingredients')->select('id', 'name')->where('bar_id', $bar->id)->get()->keyBy(function ($ingredient) {
            return Str::slug($ingredient->name);
        });

        $ingredientsToInsert = [];
        $parentIngredientsToInsert = [];
        $imagesToInsert = [];
        $barImagesDir = 'ingredients/' . $bar->id . '/';
        $this->uploadsDisk->makeDirectory($barImagesDir);

        DB::beginTransaction();
        foreach ($ingredients as $ingredient) {
            if ($existingIngredients->has(Str::slug($ingredient['name']))) {
                continue;
            }

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

            if ($ingredient['_parent_id'] ?? null) {
                $parentIngredientsToInsert[$slug] = $ingredient['_parent_id'] . '-' . $bar->id;
            }

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

            if (array_key_exists($ingredient->slug, $parentIngredientsToInsert)) {
                $parentSlug = $parentIngredientsToInsert[$ingredient->slug];
                $parentIngredientId = DB::table('ingredients')->where('slug', $parentSlug)->where('bar_id', $bar->id)->first('id');
                if (isset($parentIngredientId->id)) {
                    DB::table('ingredients')->where('slug', $ingredient->slug)->where('bar_id', $bar->id)->update(['parent_ingredient_id' => $parentIngredientId->id]);
                }
            }
        }

        if (count($imagesToInsert) > 0) {
            DB::table('images')->insert(array_values($imagesToInsert));
        }

        DB::commit();
    }

    private function importBaseCocktails(FilesystemAdapter $dataDisk, Bar $bar, User $user): void
    {
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
        $existingCocktails = DB::table('cocktails')->select('id', 'name')->where('bar_id', $bar->id)->get()->keyBy(function ($cocktail) {
            return Str::slug($cocktail->name);
        });

        $imagesToInsert = [];
        $tagsToInsert = [];
        $cocktailUtensilsToInsert = [];
        $barImagesDir = 'cocktails/' . $bar->id . '/';
        $this->uploadsDisk->makeDirectory($barImagesDir);

        DB::beginTransaction();
        foreach ($cocktails as $cocktail) {
            if ($existingCocktails->has(Str::slug($cocktail['name']))) {
                continue;
            }

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

            foreach ($cocktail['tags'] ?? [] as $tag) {
                $tag = Tag::firstOrCreate([
                    'name' => trim($tag),
                    'bar_id' => $bar->id,
                ]);
                $tagsToInsert[] = [
                    'tag_id' => $tag->id,
                    'cocktail_id' => $cocktailId,
                ];
            }

            foreach ($cocktail['utensils'] ?? [] as $utensil) {
                $utensil = Utensil::firstOrCreate([
                    'name' => trim($utensil),
                    'bar_id' => $bar->id,
                ]);
                $cocktailUtensilsToInsert[] = [
                    'utensil_id' => $utensil->id,
                    'cocktail_id' => $cocktailId,
                ];
            }

            $sort = 1;
            foreach ($cocktail['ingredients'] as $cocktailIngredient) {
                $ciId = DB::table('cocktail_ingredients')->insertGetId([
                    'cocktail_id' => $cocktailId,
                    'ingredient_id' => $dbIngredients[strtolower($cocktailIngredient['name'])] ?? null,
                    'amount' => $cocktailIngredient['amount'],
                    'units' => $cocktailIngredient['units'],
                    'optional' => $cocktailIngredient['optional'] ?? false,
                    'note' => $cocktailIngredient['note'] ?? null,
                    'sort' => $sort,
                ]);

                $sort++;

                if (count($cocktailIngredient['substitutes'] ?? []) > 0) {
                    foreach ($cocktailIngredient['substitutes'] as $substitute) {
                        DB::table('cocktail_ingredient_substitutes')->insert([
                            'cocktail_ingredient_id' => $ciId,
                            'ingredient_id' => $dbIngredients[strtolower($substitute['name'])] ?? null,
                            'amount' => $substitute['amount'] ?? null,
                            'amount_max' => $substitute['amount_max'] ?? null,
                            'units' => $substitute['units'] ?? null,
                            'created_at' => now(),
                            'updated_at' => null,
                        ]);
                    }
                }
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
        DB::commit();

        DB::table('images')->insert($imagesToInsert);
        DB::table('cocktail_tag')->insert($tagsToInsert);
    }

    private function copyResourceImage(FilesystemAdapter $dataDisk, string $baseSrcImagePath, string $targetImagePath): void
    {
        if (!$dataDisk->fileExists($baseSrcImagePath)) {
            return;
        }

        copy(
            $dataDisk->path($baseSrcImagePath),
            $this->uploadsDisk->path($targetImagePath)
        );
    }
}
