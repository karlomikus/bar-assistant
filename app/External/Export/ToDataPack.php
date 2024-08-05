<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Export;

use ZipArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\File;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\External\ExportTypeEnum;
use Kami\Cocktail\External\DataPack\Cocktail as CocktailExternal;
use Kami\Cocktail\Exceptions\ExportFileNotCreatedException;
use Kami\Cocktail\External\DataPack\IngredientFull as IngredientExternal;

class ToDataPack
{
    public function process(int $barId, ?string $exportPath = null, ExportTypeEnum $exportType = ExportTypeEnum::YAML): string
    {
        $version = config('bar-assistant.version');
        $meta = [
            'version' => $version,
            'date' => Carbon::now()->toJSON(),
            'called_from' => __CLASS__,
        ];

        File::ensureDirectoryExists(storage_path('bar-assistant/backups'));
        $filename = storage_path(sprintf('bar-assistant/backups/%s_%s.zip', Carbon::now()->format('Ymdhi'), 'recipes'));
        if ($exportPath) {
            $filename = $exportPath;
        }

        $zip = new ZipArchive();

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            $message = sprintf('Error creating zip archive with filepath "%s"', $filename);

            throw new ExportFileNotCreatedException($message);
        }

        $this->dumpCocktails($barId, $zip);
        $this->dumpIngredients($barId, $zip);
        $this->dumpBaseData($barId, $zip);

        if ($metaContent = json_encode($meta)) {
            $zip->addFromString('_meta.json', $metaContent);
        }

        $zip->close();

        return $filename;
    }

    private function dumpCocktails(int $barId, ZipArchive &$zip): void
    {
        $cocktails = Cocktail::with(['ingredients.ingredient', 'ingredients.substitutes', 'images' => function ($query) {
            $query->orderBy('sort');
        }, 'glass', 'method', 'tags'])->where('bar_id', $barId)->get();

        /** @var Cocktail $cocktail */
        foreach ($cocktails as $cocktail) {
            $data = CocktailExternal::fromModel($cocktail);

            /** @var \Kami\Cocktail\Models\Image $img */
            foreach ($cocktail->images as $img) {
                $zip->addFile($img->getPath(), 'cocktails/' . $img->getFileName());
            }

            $cocktailExportData = $this->prepareDataOutput($data);

            $zip->addFromString('cocktails/' . $cocktail->getExternalId() . '_recipe.json', $cocktailExportData);
        }
    }

    private function dumpIngredients(int $barId, ZipArchive &$zip): void
    {
        $ingredients = Ingredient::with(['images' => function ($query) {
            $query->orderBy('sort');
        }])->where('bar_id', $barId)->get();

        /** @var Ingredient $ingredient */
        foreach ($ingredients as $ingredient) {
            $data = IngredientExternal::fromModel($ingredient);

            /** @var \Kami\Cocktail\Models\Image $img */
            foreach ($ingredient->images as $img) {
                $zip->addFile($img->getPath(), 'ingredients/' . $img->getFileName());
            }

            $ingredientExportData = $this->prepareDataOutput($data);

            $zip->addFromString('ingredients/' . $ingredient->getExternalId() . '.json', $ingredientExportData);
        }
    }

    private function dumpBaseData(int $barId, ZipArchive &$zip): void
    {
        $baseDataFiles = [
            'base_glasses' => DB::table('glasses')->select('name', 'description')->where('bar_id', $barId)->get()->toArray(),
            'base_methods' => DB::table('cocktail_methods')->select('name', 'dilution_percentage')->where('bar_id', $barId)->get()->toArray(),
            'base_utensils' => DB::table('utensils')->select('name', 'description')->where('bar_id', $barId)->get()->toArray(),
            'base_ingredient_categories' => DB::table('ingredient_categories')->select('name', 'description')->where('bar_id', $barId)->get()->toArray(),
            'base_price_categories' => DB::table('price_categories')->select('name', 'currency', 'description')->where('bar_id', $barId)->get()->toArray(),
        ];

        foreach ($baseDataFiles as $file => $data) {
            $exportData = $this->prepareDataOutput($data);

            $zip->addFromString($file . '.json', $exportData);
        }
    }

    private function prepareDataOutput(mixed $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
