<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use Generator;
use Throwable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Utensil;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\External\BarOptionsEnum;
use Kami\Cocktail\Models\Enums\BarStatusEnum;
use Illuminate\Contracts\Filesystem\Filesystem;
use Kami\Cocktail\External\Model\Cocktail as CocktailExternal;
use Kami\Cocktail\External\Model\Calculator as CalculatorExternal;
use Kami\Cocktail\External\Model\Ingredient as IngredientExternal;

class FromDataPack
{
    private Filesystem $uploadsDisk;

    /** @var array<string> */
    private array $barShelf = [];

    /** @var array<string, int> */
    private array $ingredientCalculators = [];

    public function __construct()
    {
        $this->uploadsDisk = Storage::disk('uploads');
    }

    /**
     * @param array<BarOptionsEnum> $flags
     */
    public function process(Filesystem $dataDisk, Bar $bar, User $user, array $flags = []): bool
    {
        Log::debug(sprintf('Starting datapack import for "%s"', $bar->name));

        $timerStart = microtime(true);
        $bar->setStatus(BarStatusEnum::Provisioning)->save();

        $baseDataFiles = [
            'glasses' => 'base_glasses.json',
            'cocktail_methods' => 'base_methods.json',
            'utensils' => 'base_utensils.json',
            'price_categories' => 'base_price_categories.json',
        ];

        foreach ($baseDataFiles as $table => $file) {
            if ($dataDisk->exists($file)) {
                $this->importBaseData($table, $dataDisk->path($file), $bar->id);
            }
        }

        if ($dataDisk->exists('bar_shelf.json')) {
            $this->loadBarShelfFromImportData($dataDisk->path('bar_shelf.json'), $bar->id);
        }

        if ($dataDisk->exists('calculators.json')) {
            $this->importCalculators($dataDisk->path('calculators.json'), $bar->id);
        }

        if (in_array(BarOptionsEnum::Ingredients, $flags)) {
            $this->importIngredients($dataDisk, $bar, $user);
        }

        if (in_array(BarOptionsEnum::Ingredients, $flags) && in_array(BarOptionsEnum::Cocktails, $flags)) {
            $this->importBaseCocktails($dataDisk, $bar, $user);
        }

        $bar->setStatus(BarStatusEnum::Active)->save();

        /** @phpstan-ignore-next-line */
        Ingredient::where('bar_id', $bar->id)->searchable();
        /** @phpstan-ignore-next-line */
        Cocktail::where('bar_id', $bar->id)->searchable();

        $timerEnd = microtime(true);

        Log::debug(sprintf('Importing datapack done in "%s" seconds', ($timerEnd - $timerStart)));

        return true;
    }

    private function importBaseData(string $tableName, string $filepath, int $barId): void
    {
        if ($fileContents = file_get_contents($filepath)) {
            $data = json_decode($fileContents, true);
        } else {
            $data = [];
        }

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

    private function importCalculators(string $filepath, int $barId): void
    {
        if ($fileContents = file_get_contents($filepath)) {
            $data = json_decode($fileContents, true);
        } else {
            $data = [];
        }

        DB::beginTransaction();
        try {
            foreach ($data as $calculator) {
                $externalCalculator = CalculatorExternal::fromDataPackArray($calculator);
                $calculatorId = DB::table('calculators')->insertGetId([
                    'name' => $externalCalculator->name,
                    'description' => $externalCalculator->description,
                    'bar_id' => $barId,
                    'created_at' => now(),
                    'updated_at' => null,
                ]);
                $this->ingredientCalculators[$externalCalculator->id] = $calculatorId;

                $blocksToInsert = [];
                foreach ($calculator['blocks'] as $cBlock) {
                    $blocksToInsert[] = [
                        'calculator_id' => $calculatorId,
                        'type' => $cBlock['type'],
                        'label' => $cBlock['label'],
                        'variable_name' => $cBlock['variable_name'],
                        'value' => $cBlock['value'],
                        'sort' => $cBlock['sort'],
                        'description' => $cBlock['description'],
                        'settings' => json_encode($cBlock['settings']),
                    ];
                }

                DB::table('calculator_blocks')->insert($blocksToInsert);
            }
        } catch (Throwable) {
            DB::rollBack();
        }
        DB::commit();
    }

    private function importIngredients(Filesystem $dataDisk, Bar $bar, User $user): void
    {
        $existingIngredients = DB::table('ingredients')->select('id', 'name')->where('bar_id', $bar->id)->get()->keyBy(function ($ingredient) {
            return Str::slug($ingredient->name);
        });

        $ingredientsToInsert = [];
        $parentIngredientsToInsert = [];
        $ingredientPartsToInsert = [];
        $imagesToInsert = [];
        $barImagesDir = $bar->getIngredientsDirectory();
        $this->uploadsDisk->makeDirectory($barImagesDir);

        foreach ($this->getDataFromDir('ingredients', $dataDisk) as $fromYield) {
            [$externalIngredient, $filePath] = $fromYield;
            $externalIngredient = IngredientExternal::fromDataPackArray($externalIngredient);
            if ($existingIngredients->has($externalIngredient->id)) {
                continue;
            }

            $slug = $externalIngredient->id . '-' . $bar->id;
            $ingredientsToInsert[] = [
                'bar_id' => $bar->id,
                'slug' => $slug,
                'name' => $externalIngredient->name,
                'strength' => $externalIngredient->strength,
                'description' => $externalIngredient->description,
                'origin' => $externalIngredient->origin,
                'color' => $externalIngredient->color,
                'created_user_id' => $user->id,
                'created_at' => $externalIngredient->createdAt ?? now(),
                'updated_at' => $externalIngredient->updatedAt,
                'calculator_id' => $externalIngredient->calculatorId ? $this->ingredientCalculators[$externalIngredient->calculatorId] : null,
            ];

            if ($externalIngredient->parentId) {
                $parentIngredientsToInsert[$slug] = $externalIngredient->parentId . '-' . $bar->id;
            }

            foreach ($externalIngredient->ingredientParts as $ingredientPart) {
                $ingredientPartsToInsert[$slug][] = $ingredientPart->id . '-' . $bar->id;
            }

            // For performance, manually copy the files and create image references
            foreach ($externalIngredient->images as $image) {
                $baseSrcImagePath = $filePath . $image->getLocalFilePath();
                $fileExtension = File::extension($dataDisk->path($baseSrcImagePath));
                $targetImagePath = $barImagesDir . $slug . '_' . Str::random(6) . '.' . $fileExtension;

                $this->copyResourceImage($dataDisk, $baseSrcImagePath, $targetImagePath);

                $imagesToInsert[$slug] = [
                    'copyright' => $image->copyright,
                    'file_path' => $targetImagePath,
                    'file_extension' => $fileExtension,
                    'created_user_id' => $user->id,
                    'sort' => $image->sort,
                    'placeholder_hash' => $image->placeholderHash,
                    'created_at' => now(),
                    'updated_at' => null,
                ];
            }
        }

        // Start inserting
        DB::beginTransaction();

        DB::table('ingredients')->insert($ingredientsToInsert);

        $ingredients = DB::table('ingredients')->where('bar_id', $bar->id)->get();
        $barShelfIngredientsToInsert = [];
        $complexIngredientsToInsert = [];

        foreach ($ingredients as $ingredient) {
            if (array_key_exists($ingredient->slug, $imagesToInsert)) {
                $imagesToInsert[$ingredient->slug]['imageable_type'] = Ingredient::class;
                $imagesToInsert[$ingredient->slug]['imageable_id'] = $ingredient->id;
            }

            if (array_key_exists($ingredient->slug, $parentIngredientsToInsert)) {
                $parentSlug = $parentIngredientsToInsert[$ingredient->slug];
                $parentIngredientId = $ingredients->firstWhere('slug', $parentSlug);
                if (isset($parentIngredientId->id)) {
                    DB::table('ingredients')->where('slug', $ingredient->slug)->where('bar_id', $bar->id)->update(['parent_ingredient_id' => $parentIngredientId->id]);
                }
            }

            if (array_key_exists($ingredient->slug, $ingredientPartsToInsert)) {
                foreach ($ingredientPartsToInsert[$ingredient->slug] as $partSlug) {
                    $ingredientPartId = $ingredients->firstWhere('slug', $partSlug);
                    if (isset($ingredientPartId->id)) {
                        $complexIngredientsToInsert[] = ['main_ingredient_id' => $ingredient->id, 'ingredient_id' => $ingredientPartId->id];
                    }
                }
            }

            if (in_array($ingredient->slug, $this->barShelf)) {
                $barShelfIngredientsToInsert[] = ['ingredient_id' => $ingredient->id, 'bar_id' => $bar->id];
            }
        }

        if (count($complexIngredientsToInsert) > 0) {
            DB::table('complex_ingredients')->insert($complexIngredientsToInsert);
        }

        if (count($barShelfIngredientsToInsert) > 0) {
            DB::table('bar_ingredients')->insert($barShelfIngredientsToInsert);
        }

        if (count($imagesToInsert) > 0) {
            DB::table('images')->insert(array_values($imagesToInsert));
        }

        DB::commit();
    }

    private function importBaseCocktails(Filesystem $dataDisk, Bar $bar, User $user): void
    {
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
        foreach ($this->getDataFromDir('cocktails', $dataDisk) as $fromYield) {
            [$cocktail, $filePath] = $fromYield;

            $externalCocktail = CocktailExternal::fromDataPackArray($cocktail);

            if ($existingCocktails->has($externalCocktail->id)) {
                continue;
            }

            $slug = $externalCocktail->id . '-' . $bar->id;

            $cocktailId = DB::table('cocktails')->insertGetId([
                'slug' => $slug,
                'name' => $externalCocktail->name,
                'instructions' => $externalCocktail->instructions,
                'description' => $externalCocktail->description,
                'garnish' => $externalCocktail->garnish,
                'source' => $externalCocktail->source,
                'abv' => $externalCocktail->abv,
                'created_user_id' => $user->id,
                'glass_id' => $dbGlasses[mb_strtolower($externalCocktail->glass ?? '', 'UTF-8')] ?? null,
                'cocktail_method_id' => $dbMethods[mb_strtolower($externalCocktail->method ?? '', 'UTF-8')] ?? null,
                'bar_id' => $bar->id,
                'created_at' => $externalCocktail->createdAt ?? now(),
                'updated_at' => $externalCocktail->updatedAt,
            ]);

            foreach ($externalCocktail->tags as $tag) {
                $tag = Tag::firstOrCreate([
                    'name' => trim($tag),
                    'bar_id' => $bar->id,
                ]);
                $tagsToInsert[] = [
                    'tag_id' => $tag->id,
                    'cocktail_id' => $cocktailId,
                ];
            }

            foreach ($externalCocktail->utensils as $utensil) {
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
            foreach ($externalCocktail->ingredients as $cocktailIngredient) {
                $matchedIngredientId = $dbIngredients[mb_strtolower($cocktailIngredient->ingredient->name, 'UTF-8')] ?? null;
                if (!$matchedIngredientId) {
                    Log::warning(sprintf('Unable to match ingredient "%s" to cocktail "%s"', $cocktailIngredient->ingredient->name, $externalCocktail->name));
                    continue;
                }

                $ciId = DB::table('cocktail_ingredients')->insertGetId([
                    'cocktail_id' => $cocktailId,
                    'ingredient_id' => $matchedIngredientId,
                    'amount' => $cocktailIngredient->amount->amountMin,
                    'amount_max' => $cocktailIngredient->amount->amountMax,
                    'units' => $cocktailIngredient->amount->units->value,
                    'optional' => $cocktailIngredient->optional,
                    'is_specified' => $cocktailIngredient->isSpecified,
                    'note' => $cocktailIngredient->note,
                    'sort' => $sort,
                ]);

                $sort++;

                foreach ($cocktailIngredient->substitutes as $substitute) {
                    $matchedSubIngredientId = $dbIngredients[mb_strtolower($substitute->ingredient->name, 'UTF-8')] ?? null;
                    if (!$matchedSubIngredientId) {
                        Log::warning(sprintf('Unable to match substitute ingredient "%s" to cocktail "%s"', $substitute->ingredient->name, $externalCocktail->name));
                        continue;
                    }

                    DB::table('cocktail_ingredient_substitutes')->insert([
                        'cocktail_ingredient_id' => $ciId,
                        'ingredient_id' => $matchedSubIngredientId,
                        'amount' => $substitute->amount->amountMin,
                        'amount_max' => $substitute->amount->amountMax,
                        'units' => $substitute->amount->units->value,
                        'created_at' => now(),
                        'updated_at' => null,
                    ]);
                }
            }

            // For performance, manually copy the files and create image references
            foreach ($externalCocktail->images as $image) {
                $baseSrcImagePath = $filePath . $image->getLocalFilePath();
                $fileExtension = File::extension($dataDisk->path($baseSrcImagePath));
                $targetImagePath = $barImagesDir . $slug . '_' . Str::random(6) . '.' . $fileExtension;

                $this->copyResourceImage($dataDisk, $baseSrcImagePath, $targetImagePath);

                $imagesToInsert[] = [
                    'imageable_type' => Cocktail::class,
                    'imageable_id' => $cocktailId,
                    'copyright' => $image->copyright,
                    'file_path' => $targetImagePath,
                    'file_extension' => $fileExtension,
                    'created_user_id' => $user->id,
                    'sort' => $image->sort,
                    'placeholder_hash' => $image->placeholderHash,
                    'created_at' => now(),
                    'updated_at' => null,
                ];
            }
        }
        DB::commit();

        DB::table('images')->insert($imagesToInsert);
        DB::table('cocktail_tag')->insert($tagsToInsert);
    }

    private function copyResourceImage(Filesystem $dataDisk, string $baseSrcImagePath, string $targetImagePath): void
    {
        if (!$dataDisk->exists($baseSrcImagePath)) {
            return;
        }

        copy(
            $dataDisk->path($baseSrcImagePath),
            $this->uploadsDisk->path($targetImagePath)
        );
    }

    private function getDataFromDir(string $dir, Filesystem $dataDisk): Generator
    {
        foreach ($dataDisk->directories($dir) as $diskDirPath) {
            if ($fileContents = file_get_contents($dataDisk->path($diskDirPath . '/data.json'))) {
                yield [
                    json_decode($fileContents, true),
                    $diskDirPath,
                ];
            }
        }
    }

    private function loadBarShelfFromImportData(string $filename, int $barId): void
    {
        if ($data = file_get_contents($filename)) {
            $ingredients = json_decode($data, true);

            $this->barShelf = array_map(fn (string $slug) => $slug . '-' . $barId, $ingredients);
        }
    }
}
