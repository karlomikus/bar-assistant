<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Kami\Cocktail\Models\Image;
use Symfony\Component\Yaml\Yaml;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\File;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;

class BarDataFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bar-data-files {barId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $barId = (int) $this->argument('barId');

        $this->dumpCocktails($barId);
        $this->dumpIngredients($barId);

        return Command::SUCCESS;
    }

    private function dumpCocktails(int $barId): void
    {
        $cocktails = Cocktail::with(['ingredients.ingredient', 'ingredients.substitutes', 'images' => function ($query) {
            $query->orderBy('sort');
        }, 'glass', 'method', 'tags'])->where('bar_id', $barId)->get();

        foreach ($cocktails as $cocktail) {
            $data = [];

            $cocktailId = Str::slug($cocktail->name);

            $data['_id'] = $cocktailId;
            $data['name'] = $cocktail->name;
            $data['instructions'] = $this->cleanSpaces($cocktail->instructions);
            $data['description'] = $cocktail->description ? $this->cleanSpaces($cocktail->description) : null;
            $data['garnish'] = $cocktail->garnish;
            $data['source'] = $cocktail->source;
            $data['tags'] = $cocktail->tags->pluck('name')->toArray();
            $data['abv'] = $cocktail->abv;

            if ($cocktail->glass_id) {
                $data['glass'] = $cocktail->glass->name;
            }

            if ($cocktail->cocktail_method_id) {
                $data['method'] = $cocktail->method->name;
            }

            $data['ingredients'] = $cocktail->ingredients->map(function (CocktailIngredient $cIngredient) {
                $ingredient = [];
                $ingredient['_id'] = Str::slug($cIngredient->ingredient->name);
                $ingredient['sort'] = $cIngredient->sort ?? 0;
                $ingredient['name'] = $cIngredient->ingredient->name;
                $ingredient['amount'] = $cIngredient->amount;
                if ($cIngredient->amount_max) {
                    $ingredient['amount_max'] = $cIngredient->amount_max;
                }
                $ingredient['units'] = $cIngredient->units;
                if ($cIngredient->note) {
                    $ingredient['note'] = $cIngredient->note;
                }
                if ((bool) $cIngredient->optional === true) {
                    $ingredient['optional'] = (bool) $cIngredient->optional;
                }

                if ($cIngredient->substitutes->isNotEmpty()) {
                    $ingredient['substitutes'] = $cIngredient->substitutes->map(function (CocktailIngredientSubstitute $substitute) {
                        return [
                            '_id' => Str::slug($substitute->ingredient->name),
                            'name' => $substitute->ingredient->name,
                            'amount' => $substitute->amount,
                            'amount_max' => $substitute->amount_max,
                            'units' => $substitute->units,
                        ];
                    })->toArray();
                }

                return $ingredient;
            })->toArray();

            $data['images'] = $cocktail->images->map(function (Image $image, int $key) use ($cocktailId) {
                return [
                    'sort' => $image->sort,
                    'file_name' => $cocktailId . '-' . ($key + 1) . '.' . $image->file_extension,
                    'placeholder_hash' => $image->placeholder_hash,
                    'copyright' => $image->copyright,
                ];
            })->toArray();

            $cocktailYaml = Yaml::dump($data, 8, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            file_put_contents(storage_path('bar-assistant/data-dump/cocktails/' . $cocktailId . '.yml'), $cocktailYaml);

            $i = 1;
            foreach ($cocktail->images as $img) {
                File::copy($img->getPath(), storage_path('bar-assistant/data-dump/cocktails/images/' . $cocktailId . '-' . $i . '.' . $img->file_extension));
                $i++;
            }
        }
    }

    private function dumpIngredients(int $barId): void
    {
        $ingredients = Ingredient::with(['images' => function ($query) {
            $query->orderBy('sort');
        }])->where('bar_id', $barId)->get();

        foreach ($ingredients as $ingredient) {
            $data = [];

            $ingredientId = Str::slug($ingredient->name);

            $data['_id'] = $ingredientId;
            if ($ingredient->parent_ingredient_id) {
                $data['_parent_id'] = Str::slug($ingredient->parentIngredient->name);
            }

            $data['name'] = $ingredient->name;
            $data['description'] = $ingredient->description;
            $data['strength'] = $ingredient->strength;
            $data['origin'] = $ingredient->origin;
            $data['color'] = $ingredient->color;
            $data['category'] = $ingredient->category?->name ?? null;

            if ($ingredient->images->isNotEmpty()) {
                $data['images'] = $ingredient->images->map(function (Image $image, int $key) use ($ingredientId) {
                    return [
                        'sort' => $image->sort,
                        'file_name' => $ingredientId . '-' . ($key + 1) . '.' . $image->file_extension,
                        'placeholder_hash' => $image->placeholder_hash,
                        'copyright' => $image->copyright,
                    ];
                })->toArray();
            }

            $ingredientYaml = Yaml::dump($data, 8, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            file_put_contents(storage_path('bar-assistant/data-dump/ingredients/' . $ingredientId . '.yml'), $ingredientYaml);

            $i = 1;
            foreach ($ingredient->images as $img) {
                File::copy($img->getPath(), storage_path('bar-assistant/data-dump/ingredients/images/' . $ingredientId . '-' . $i . '.' . $img->file_extension));
                $i++;
            }
        }
    }

    private function cleanSpaces(string $str): string
    {
        $str = mb_convert_encoding($str, 'UTF-8', mb_detect_encoding($str));
        $str = str_replace("&nbsp;", " ", $str);
        $str = str_replace(" ", " ", $str);

        return $str;
    }
}
