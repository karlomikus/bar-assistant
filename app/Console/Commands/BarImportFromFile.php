<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BarImportFromFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:import-zip {filename?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data exported from another Bar Assistant instance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $disk = Storage::build([
            'driver' => 'local',
            'root' => storage_path('bar-assistant'),
        ]);

        $selectedFilename = $this->argument('filename');
        if ($selectedFilename) {
            $zipFilePath = $disk->path($this->argument('filename'));
        } else {
            $existingZipFiles = collect($disk->files())->filter(function ($filepath) {
                return str_ends_with($filepath, 'zip');
            })->toArray();

            $zipFilePath = $this->choice(
                'What is the filename that you want to import?',
                $existingZipFiles,
            );

            $zipFilePath = $disk->path($zipFilePath);
        }

        $this->info(sprintf('Checking for "%s" file...', $zipFilePath));

        if (!file_exists($zipFilePath)) {
            $this->info('File not found! Make sure the file is located in storage/ directory.');

            return Command::FAILURE;
        }

        if (!$this->confirm('This action will overwrite any data you currently have. Are you sure you want to continue?')) {
            return Command::SUCCESS;
        }

        // Setup temporary extract folder
        $unzipPath = storage_path('temp/export/import_' . Str::random(8));
        $disk = Storage::build([
            'driver' => 'local',
            'root' => $unzipPath,
        ]);

        // Extract the archive
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            $this->info('Unable to open the zip file!');

            return Command::FAILURE;
        }
        $zip->extractTo($unzipPath);
        $zip->close();

        $importOrder = [
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

        $this->info('Importing table data...');
        foreach ($importOrder as $tableName) {
            $data = json_decode(file_get_contents($disk->path($tableName . '.json')), true);

            foreach ($data as $row) {
                try {
                    DB::table($tableName)->insert($row);
                } catch (Throwable $e) {
                    // $this->info(sprintf('Unable to import row with id "%s" to table "%s"', $row['id'], $tableName));
                }
            }
        }

        $this->info('Importing images...');

        $baDisk = Storage::disk('bar-assistant');

        foreach (glob($disk->path('uploads/cocktails/*')) as $pathFrom) {
            copy($pathFrom, $baDisk->path('cocktails/' . basename($pathFrom)));
        }

        foreach (glob($disk->path('uploads/ingredients/*')) as $pathFrom) {
            copy($pathFrom, $baDisk->path('ingredients/' . basename($pathFrom)));
        }

        $this->info('Removing temp folder...');

        $disk->deleteDirectory('/');

        $this->info('Refreshing search indexes...');

        Artisan::call('bar:refresh-search');

        $this->info('Importing is finished!');

        return Command::SUCCESS;
    }
}
