<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Export;

use ZipArchive;
use Carbon\Carbon;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Log;
use Kami\RecipeUtils\UnitConverter\Units;
use Illuminate\Contracts\Filesystem\Cloud;
use Kami\Cocktail\External\ExportTypeEnum;
use Illuminate\Container\Attributes\Storage;
use Kami\Cocktail\External\ForceUnitConvertEnum;
use Kami\Cocktail\Exceptions\ImageFileNotFoundException;
use Kami\Cocktail\External\Model\Schema as SchemaExternal;
use Kami\Cocktail\Exceptions\ExportFileNotCreatedException;

class ToRecipeType
{
    public function __construct(
        #[Storage('exports')]
        private readonly Cloud $file,
    ) {
    }

    public function process(int $barId, ?string $filename = null, ExportTypeEnum $type = ExportTypeEnum::Schema, ForceUnitConvertEnum $units = ForceUnitConvertEnum::Original): string
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
            'called_from' => self::class,
            'type' => $type->value,
            'bar_id' => $barId,
            'schema' => $type === ExportTypeEnum::Schema ? 'https://barassistant.app/cocktail-02.schema.json' : null,
        ];

        $tempFilePath = tempnam(sys_get_temp_dir(), 'cocktail_export');
        Log::debug(sprintf('Exporting recipe type "%s" to temporary file "%s"', $type->value, $tempFilePath));

        try {
            $zip = new ZipArchive();

            if ($zip->open($tempFilePath, ZipArchive::CREATE) !== true) {
                $message = sprintf('Error creating zip archive with filepath "%s"', $tempFilePath);

                throw new ExportFileNotCreatedException($message);
            }

            $this->dumpCocktails($barId, $zip, $type, $toUnits);

            if ($metaContent = json_encode($meta)) {
                $zip->addFromString('_meta.json', $metaContent);
            }
        } finally {
            $zip->close();
        }

        $fullPath = $barId . '/' . $filename;
        Log::debug(sprintf('Moving temporary file from "%s" to exports disk at "%s"', $tempFilePath, $fullPath));
        $this->file->makeDirectory((string) $barId);
        $contents = file_get_contents($tempFilePath);
        if ($contents === false) {
            throw new ExportFileNotCreatedException('Could not read temporary export file contents');
        }

        $this->file->put($fullPath, $contents);

        return $fullPath;
    }

    private function dumpCocktails(int $barId, ZipArchive &$zip, ExportTypeEnum $type, ?Units $toUnits = null): void
    {
        $cocktails = Cocktail::with([
            'ingredients.ingredient',
            'ingredients.ingredient.parentIngredient',
            'ingredients.substitutes.ingredient',
            'ingredients.ingredient',
            'images.imageable',
            'glass',
            'method',
            'tags',
            'utensils'
        ])->where('bar_id', $barId)->get();

        /** @var Cocktail $cocktail */
        foreach ($cocktails as $cocktail) {
            foreach ($cocktail->images as $img) {
                try {
                    $zip->addFile($img->getPath(), 'cocktails/' . $cocktail->getExternalId() . '/' . $img->getFileName());
                } catch (ImageFileNotFoundException $e) {
                    Log::warning($e->getMessage());
                }
            }

            $externalSchema = SchemaExternal::fromCocktailModel($cocktail, $toUnits);

            if ($type === ExportTypeEnum::Schema) {
                $cocktailExportData = $this->prepareDataOutput(
                    $externalSchema->toDraft2Array(),
                );

                $zip->addFromString('cocktails/' . $cocktail->getExternalId() . '/recipe.json', $cocktailExportData);
            }

            if ($type === ExportTypeEnum::JSONLD) {
                $cocktailExportData = $this->prepareDataOutput(
                    $cocktail->asJsonLDSchema(),
                );

                $zip->addFromString('cocktails/' . $cocktail->getExternalId() . '/recipe.json', $cocktailExportData);
            }

            if ($type === ExportTypeEnum::Markdown) {
                $cocktailExportData = $externalSchema->toMarkdown();

                $zip->addFromString('cocktails/' . $cocktail->getExternalId() . '/recipe.md', $cocktailExportData);
            }

            if ($type === ExportTypeEnum::XML) {
                $cocktailExportData = $externalSchema->toXML();

                $zip->addFromString('cocktails/' . $cocktail->getExternalId() . '/recipe.xml', $cocktailExportData);
            }

            if ($type === ExportTypeEnum::YAML) {
                $cocktailExportData = $externalSchema->toYAML();

                $zip->addFromString('cocktails/' . $cocktail->getExternalId() . '/recipe.yaml', $cocktailExportData);
            }
        }
    }

    /**
     * @param SchemaExternal|array<mixed> $data
     */
    private function prepareDataOutput(SchemaExternal|array $data): string
    {
        if ($data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
            return $data;
        }

        return '';
    }
}
