<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Export;

use ZipArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Kami\Cocktail\Models\Calculator;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\External\ForceUnitConvertEnum;
use Kami\Cocktail\Exceptions\ImageFileNotFoundException;
use Kami\Cocktail\Exceptions\ExportFileNotCreatedException;
use Kami\Cocktail\External\Model\Cocktail as CocktailExternal;
use Illuminate\Contracts\Filesystem\Factory as FileSystemFactory;
use Kami\Cocktail\External\Model\Calculator as CalculatorExternal;
use Kami\Cocktail\External\Model\Ingredient as IngredientExternal;

/**
 * Datapack is a zip archive containing all data required to move to another Bar Assistant instance.
 *
 * @package Kami\Cocktail\External\Export
 */
class ToDataPack
{
    public function __construct(private readonly FileSystemFactory $file)
    {
    }

    public function process(int $barId, ?string $filename = null, ForceUnitConvertEnum $units = ForceUnitConvertEnum::Original): string
    {
        if (!$filename) {
            throw new \Exception('Export filename is required');
        }

        $toUnits = null;
        if ($units !== ForceUnitConvertEnum::Original) {
            $toUnits = Units::tryFrom($units->value);
        }

        $version = config('bar-assistant.version');
        $meta = [
            'version' => $version,
            'date' => Carbon::now()->toAtomString(),
            'called_from' => __CLASS__,
        ];

        File::ensureDirectoryExists($this->file->disk('exports')->path((string) $barId));
        $filename = $this->file->disk('exports')->path($barId . '/' . $filename);

        Log::debug(sprintf('Exporting datapack to "%s"', $filename));

        $zip = new ZipArchive();

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            $message = sprintf('Error creating zip archive with filepath "%s"', $filename);

            throw new ExportFileNotCreatedException($message);
        }

        $this->dumpCocktails($barId, $zip, $toUnits);
        $this->dumpIngredients($barId, $zip);
        $this->dumpBaseData($barId, $zip);
        $this->dumpCalculators($barId, $zip);

        if ($metaContent = json_encode($meta)) {
            $zip->addFromString('_meta.json', $metaContent);
        }

        $zip->close();

        return $filename;
    }

    private function dumpCocktails(int $barId, ZipArchive &$zip, ?Units $toUnits = null): void
    {
        $cocktails = Cocktail::with(
            'ingredients.ingredient',
            'ingredients.ingredient.parentIngredient',
            'ingredients.substitutes.ingredient.parentIngredient',
            'images.imageable',
            'glass',
            'method',
            'tags',
            'utensils',
            'parentCocktail',
        )->where('bar_id', $barId)->get();

        /** @var Cocktail $cocktail */
        foreach ($cocktails as $cocktail) {
            $data = CocktailExternal::fromModel($cocktail, true, $toUnits);

            /** @var \Kami\Cocktail\Models\Image $img */
            foreach ($cocktail->images as $img) {
                try {
                    $zip->addFile($img->getPath(), 'cocktails/' . $cocktail->getExternalId() . '/' . $img->getFileName());
                } catch (ImageFileNotFoundException $e) {
                    Log::warning($e->getMessage());
                }
            }

            $cocktailExportData = $this->prepareDataOutput($data->toDataPackArray());

            $zip->addFromString('cocktails/' . $cocktail->getExternalId() . '/data.json', $cocktailExportData);
        }
    }

    private function dumpIngredients(int $barId, ZipArchive &$zip): void
    {
        $ingredients = Ingredient::with('images.imageable', 'calculator', 'prices.priceCategory', 'ingredientParts.ingredient.parentIngredient', 'ancestors', 'parentIngredient')->where('bar_id', $barId)->get();

        /** @var Ingredient $ingredient */
        foreach ($ingredients as $ingredient) {
            $data = IngredientExternal::fromModel($ingredient, true);

            /** @var \Kami\Cocktail\Models\Image $img */
            foreach ($ingredient->images as $img) {
                $zip->addFile($img->getPath(), 'ingredients/' . $ingredient->getExternalId() . '/' . $img->getFileName());
            }

            $ingredientExportData = $this->prepareDataOutput($data->toDataPackArray());

            $zip->addFromString('ingredients/' . $ingredient->getExternalId() . '/data.json', $ingredientExportData);
        }
    }

    private function dumpCalculators(int $barId, ZipArchive &$zip): void
    {
        $calculators = Calculator::with('blocks')->where('bar_id', $barId)->get();
        $calculatorExportData = [];

        /** @var Calculator $calculator */
        foreach ($calculators as $calculator) {
            $data = CalculatorExternal::fromModel($calculator);

            $calculatorExportData[] = $data->toDataPackArray();
        }

        $zip->addFromString('calculators.json', $this->prepareDataOutput($calculatorExportData));
    }

    private function dumpBaseData(int $barId, ZipArchive &$zip): void
    {
        $baseDataFiles = [
            'base_glasses' => DB::table('glasses')->select('name', 'description', 'volume', 'volume_units')->where('bar_id', $barId)->get()->toArray(),
            'base_methods' => DB::table('cocktail_methods')->select('name', 'dilution_percentage')->where('bar_id', $barId)->get()->toArray(),
            'base_utensils' => DB::table('utensils')->select('name', 'description')->where('bar_id', $barId)->get()->toArray(),
            'base_price_categories' => DB::table('price_categories')->select('name', 'currency', 'description')->where('bar_id', $barId)->get()->toArray(),
        ];

        $baseDataFiles['bar_shelf'] = BarIngredient::where('bar_id', $barId)->with('ingredient')->get()->map(fn ($bi) => $bi->ingredient->getExternalId())->toArray();

        foreach ($baseDataFiles as $file => $data) {
            $exportData = $this->prepareDataOutput($data);

            $zip->addFromString($file . '.json', $exportData);
        }
    }

    private function prepareDataOutput(mixed $data): string
    {
        if ($data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
            return $data;
        }

        return '';
    }
}
