<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Services\ImportService;

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

    public function __construct(private readonly ImportService $importService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /** @var \Illuminate\Support\Facades\Storage */
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

        try {
            $this->importService->importFromZipFile($zipFilePath);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $this->info('Refreshing search indexes...');

        Artisan::call('bar:refresh-search');

        $this->info('Importing is finished!');

        return Command::SUCCESS;
    }
}
