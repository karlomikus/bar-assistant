<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Export;

use ZipArchive;
use Carbon\Carbon;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\File;
use Kami\Cocktail\External\ExportTypeEnum;
use Kami\Cocktail\Exceptions\ExportFileNotCreatedException;
use Kami\Cocktail\External\Model\Schema as SchemaExternal;
use Illuminate\Contracts\Filesystem\Factory as FileSystemFactory;

class ToRecipeType
{
    public function __construct(private readonly FileSystemFactory $file)
    {
    }

    public function process(int $barId, ?string $filename = null, ExportTypeEnum $type = ExportTypeEnum::Schema): string
    {
        if (!$filename) {
            throw new \Exception('Export filename is required');
        }

        $version = config('bar-assistant.version');
        $meta = [
            'version' => $version,
            'date' => Carbon::now()->toAtomString(),
            'called_from' => __CLASS__,
            'type' => $type->value,
            'bar_id' => $barId,
            'schema' => $type === ExportTypeEnum::Schema ? 'https://barassistant.app/cocktail-02.schema.json' : null,
        ];

        File::ensureDirectoryExists($this->file->disk('exports')->path((string) $barId));
        $filename = $this->file->disk('exports')->path($barId . '/' . $filename);

        $zip = new ZipArchive();

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            $message = sprintf('Error creating zip archive with filepath "%s"', $filename);

            throw new ExportFileNotCreatedException($message);
        }

        $this->dumpCocktails($barId, $zip, $type);

        if ($metaContent = json_encode($meta)) {
            $zip->addFromString('_meta.json', $metaContent);
        }

        $zip->close();

        return $filename;
    }

    private function dumpCocktails(int $barId, ZipArchive &$zip, ExportTypeEnum $type): void
    {
        $cocktails = Cocktail::with([
            'ingredients.ingredient',
            'ingredients.substitutes.ingredient',
            'ingredients.ingredient.category',
            'images.imageable',
            'glass',
            'method',
            'tags',
            'utensils'
        ])->where('bar_id', $barId)->get();

        /** @var Cocktail $cocktail */
        foreach ($cocktails as $cocktail) {
            foreach ($cocktail->images as $img) {
                $zip->addFile($img->getPath(), 'cocktails/' . $cocktail->getExternalId() . '/' . $img->getFileName());
            }

            $externalSchema = SchemaExternal::fromCocktailModel($cocktail);

            if ($type === ExportTypeEnum::Schema) {
                $cocktailExportData = $this->prepareDataOutput(
                    SchemaExternal::fromCocktailModel($cocktail),
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
        }
    }

    private function prepareDataOutput(SchemaExternal|array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
