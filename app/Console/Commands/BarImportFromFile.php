<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Kami\Cocktail\Services\ImportService;

class BarImportFromFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:import-zip {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $service = resolve(ImportService::class);

        $service->importFromZipFile(storage_path($this->argument('filename')));

        Artisan::call('bar:refresh-search');

        return Command::SUCCESS;
    }
}
