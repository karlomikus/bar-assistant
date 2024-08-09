<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Export;

use ZipArchive;
use Carbon\Carbon;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\File;
use Kami\Cocktail\External\Draft2\Schema as SchemaExternal;
use Kami\Cocktail\External\Draft2\Cocktail as CocktailExternal;
use Kami\Cocktail\Exceptions\ExportFileNotCreatedException;
use Kami\Cocktail\External\Draft2\Ingredient as IngredientExternal;

class ToSchemaDraft2
{
    public function process(int $barId, ?string $exportPath = null): string
    {
        $version = config('bar-assistant.version');
        $meta = [
            'version' => $version,
            'date' => Carbon::now()->toJSON(),
            'called_from' => __CLASS__,
            'schema_version' => 'https://barassistant.app/cocktail-02.schema.json',
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

            $i = 1;
            foreach ($cocktail->images as $img) {
                $zip->addFile($img->getPath(), 'cocktails/' . $cocktail->getExternalId() . '/' . $img->getFileName());
                $i++;
            }

            $ingredients = [];
            foreach ($cocktail->ingredients as $cocktailIngredient) {
                $ingredients[] = IngredientExternal::fromModel($cocktailIngredient->ingredient);
            }

            $cocktailExportData = $this->prepareDataOutput(
                new SchemaExternal($data, $ingredients)
            );

            $zip->addFromString('cocktails/' . $cocktail->getExternalId() . '/recipe.json', $cocktailExportData);
        }
    }

    private function prepareDataOutput(SchemaExternal $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
