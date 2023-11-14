<?php

declare(strict_types=1);

namespace Kami\Cocktail\Import;

use Exception;
use Throwable;
use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class FromV3Export
{
    /**
     * @deprecated Not to be used anymore
     */
    public function process(string $zipFilePath, ?string $defaultPassword = null): void
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
            $currVersion = config('bar-assistant.version');
            if ($currVersion !== 'develop' && $importVersion !== $currVersion) {
                throw new Exception('Can not import from this version. Importing should be done with the same version as the export, in this case you should do import on version: ' . $importVersion);
            }
        }

        Log::info(sprintf('Starting import from version "%s"', $importVersion));

        // Clean images
        File::cleanDirectory(storage_path('bar-assistant/uploads/cocktails'));
        File::cleanDirectory(storage_path('bar-assistant/uploads/ingredients'));
        File::cleanDirectory(storage_path('bar-assistant/uploads/temp'));

        $importData = json_decode(file_get_contents($disk->path('tables.json')), true);

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::beginTransaction();

        DB::table('personal_access_tokens')->truncate();
        DB::table('password_resets')->truncate();

        try {
            foreach ($importData as $tableName => $tableData) {
                DB::table($tableName)->truncate();

                if ($tableName === 'users') {
                    $tableData = array_map(function ($row) use ($defaultPassword) {
                        if ($defaultPassword !== null) {
                            $row['password'] = Hash::make($defaultPassword);
                        }

                        $row['password'] = Hash::needsRehash($row['password']) ? null : $row['password'];

                        if (!$row['password']) {
                            $row['password'] = Hash::make('12345');
                        }

                        if (!$row['email']) {
                            $row['email'] = Str::random(10) . '@bar.temp';
                        }

                        return $row;
                    }, $tableData);
                }

                DB::table($tableName)->insert($tableData);
            }
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            DB::rollBack();
        }

        DB::commit();

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
