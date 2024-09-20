<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Export;

use ZipArchive;
use Carbon\Carbon;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\File;
use Kami\Cocktail\Exceptions\ExportFileNotCreatedException;

class ToFullBackup
{
    public function __construct(
        private readonly LogManager $log,
    ) {
    }

    public function process(?string $exportPath = null): string
    {
        $version = config('bar-assistant.version');
        $meta = [
            'version' => $version,
            'date' => Carbon::now()->toAtomString(),
            'called_from' => __CLASS__,
        ];

        $zip = new ZipArchive();

        File::ensureDirectoryExists(storage_path('bar-assistant/backups'));

        $filename = storage_path(sprintf('bar-assistant/backups/%s_%s.zip', Carbon::now()->format('Ymdhi'), 'bass-backup'));
        if ($exportPath) {
            $filename = $exportPath;
        }

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            $message = sprintf('Error creating zip archive with filepath "%s"', $filename);
            $this->log->error($message);

            throw new ExportFileNotCreatedException($message);
        }

        $zip->addGlob(storage_path('bar-assistant/*.sqlite'), options: ['remove_path' => storage_path('bar-assistant')]);
        $zip->addGlob(storage_path('bar-assistant/uploads/*/*/*'), options: ['remove_path' => storage_path('bar-assistant')]);

        if ($metaContent = json_encode($meta)) {
            $zip->addFromString('_meta.json', $metaContent);
        }

        $zip->close();

        return $filename;
    }
}
