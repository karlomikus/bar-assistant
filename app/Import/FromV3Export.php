<?php

declare(strict_types=1);

namespace Kami\Cocktail\Import;

use Exception;
use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class FromV3Export
{
    public function process(string $zipFilePath): void
    {
        $unzipPath = storage_path('bar-assistant/temp/export/import_' . Str::random(8));
        File::ensureDirectoryExists($unzipPath);

        /** @var \Illuminate\Support\Facades\Storage */
        $disk = Storage::build([
            'driver' => 'local',
            'root' => $unzipPath,
        ]);

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            Log::error(sprintf('Error opening zip archive with filepath "%s"', $zipFilePath));

            throw new Exception();
        }

        $zip->extractTo($unzipPath);
        $zip->close();

        $importVersion = null;
        if ($meta = json_decode(file_get_contents($disk->path('_meta.json')))) {
            $importVersion = $meta->version;
            // $versionInt = (int) str_replace('.', '', $importVersion);
            // if ($versionInt < 300) { // TODO
            //     throw new Exception('Can not import from this version!');
            // }
        }

        Log::info(sprintf('Starting import from version "%s"', $importVersion));

        // Clean images
        File::cleanDirectory(storage_path('bar-assistant/uploads/cocktails'));
        File::cleanDirectory(storage_path('bar-assistant/uploads/ingredients'));
        File::cleanDirectory(storage_path('bar-assistant/uploads/temp'));

        $importData = json_decode(file_get_contents($disk->path('tables.json')), true);

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::table('personal_access_tokens')->truncate();
        DB::table('password_resets')->truncate();

        foreach ($importData as $tableName => $tableData) {
            DB::table($tableName)->truncate();

            if ($tableName === 'users') {
                $tableData = array_map(function ($row) {
                    $row['password'] = Hash::needsRehash($row['password']) ? null : $row['password'];

                    return $row;
                }, $tableData);
            }

            DB::table($tableName)->insert($tableData);
        }

        DB::statement('PRAGMA foreign_keys = ON');

        foreach ($disk->directories('uploads/cocktails') as $barIdDir) {
            File::copyDirectory($disk->path($barIdDir), storage_path('bar-assistant/' . $barIdDir));
        }

        foreach ($disk->directories('uploads/ingredients') as $barIdDir) {
            File::copyDirectory($disk->path($barIdDir), storage_path('bar-assistant/' . $barIdDir));
        }

        $disk->deleteDirectory('/');
    }
}
