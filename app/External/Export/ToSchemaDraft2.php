<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Export;

use ZipArchive;
use Carbon\Carbon;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\File;
use Kami\Cocktail\External\ProcessesBarExport;
use Kami\Cocktail\Exceptions\ExportFileNotCreatedException;
use Kami\Cocktail\External\Draft2\Schema as SchemaExternal;
use Illuminate\Contracts\Filesystem\Factory as FileSystemFactory;

class ToSchemaDraft2 implements ProcessesBarExport
{
    public function __construct(private readonly FileSystemFactory $file)
    {
    }

    public function process(int $barId, ?string $filename = null): string
    {
        if (!$filename) {
            throw new \Exception('Export filename is required');
        }

        $version = config('bar-assistant.version');
        $meta = [
            'version' => $version,
            'date' => Carbon::now()->toAtomString(),
            'called_from' => __CLASS__,
            'schema_version' => 'https://barassistant.app/cocktail-02.schema.json',
        ];

        File::ensureDirectoryExists($this->file->disk('export-datapacks')->path((string) $barId));
        $filename = $this->file->disk('export-datapacks')->path($barId . '/' . $filename);

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
            foreach ($cocktail->images as $img) {
                $zip->addFile($img->getPath(), 'cocktails/' . $cocktail->getExternalId() . '/' . $img->getFileName());
            }

            $cocktailExportData = $this->prepareDataOutput(
                SchemaExternal::fromCocktailModel($cocktail)
            );

            $zip->addFromString('cocktails/' . $cocktail->getExternalId() . '/recipe.json', $cocktailExportData);
        }
    }

    private function prepareDataOutput(SchemaExternal $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
