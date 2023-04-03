<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use ZipArchive;
use Carbon\Carbon;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Exceptions\ExportException;

class ExportService
{
    public function __construct(
        private readonly LogManager $log,
    ) {
    }

    public function instanceShareExport(?string $exportPath = null): string
    {
        $meta = [
            'version_exported_from' => config('bar-assistant.version'),
        ];

        $tablesToExport = [
            'ingredient_categories',
            'glasses',
            'tags',
            'ingredients',
            'cocktails',
            'cocktail_ingredients',
            'cocktail_ingredient_substitutes',
            'cocktail_tag',
            'images',
        ];

        $zip = new ZipArchive();

        $filename = storage_path(sprintf('bar-assistant/%s_%s.zip', 'ba_export', Carbon::now()->format('Y-m-d-h-i-s')));
        if ($exportPath) {
            $filename = $exportPath;
        }

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            $message = sprintf('Error creating zip archive with filepath "%s"', $filename);
            $this->log->error($message);

            throw new ExportException($message);
        }

        $zip->addGlob(storage_path('bar-assistant/uploads/*/*'), options: ['remove_path' => storage_path('bar-assistant')]);
        foreach ($tablesToExport as $tableName) {
            if ($content = json_encode(DB::table($tableName)->get()->toArray())) {
                $zip->addFromString($tableName . '.json', $content);
            }
        }

        if ($metaContent = json_encode($meta)) {
            $zip->addFromString('_meta.json', $metaContent);
        }

        $zip->close();

        return $filename;
    }
}
