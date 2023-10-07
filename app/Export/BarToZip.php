<?php

declare(strict_types=1);

namespace Kami\Cocktail\Export;

use ZipArchive;
use Carbon\Carbon;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Exceptions\ExportException;

class BarToZip
{
    public function __construct(
        private readonly LogManager $log,
    ) {
    }

    public function process(array $barIds, ?string $exportPath = null): string
    {
        $version = config('bar-assistant.version');
        $meta = [
            'version_exported_from' => $version,
        ];

        $zip = new ZipArchive();

        // TODO: Query

        $filename = storage_path(sprintf('bar-assistant/backups/%s_%s_%s.zip', Carbon::now()->format('Ymdhi'), 'bass', implode('-', $barIds)));
        if ($exportPath) {
            $filename = $exportPath;
        }

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            $message = sprintf('Error creating zip archive with filepath "%s"', $filename);
            $this->log->error($message);

            throw new ExportException($message);
        }

        $zip->addGlob(storage_path('bar-assistant/uploads/*/[' . implode(',', $barIds) . ']/*'), options: ['remove_path' => storage_path('bar-assistant')]);

        if ($metaContent = json_encode($meta)) {
            $zip->addFromString('_meta.json', $metaContent);
        }

        $zip->close();

        return $filename;
    }
}
