<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Kami\Cocktail\Services\ExportService;

class BarExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:export-zip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export data to be used in a another Bar Assistant instance.';

    public function __construct(private readonly ExportService $exportService)
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
        try {
            $filepath = $this->exportService->instanceShareExport();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Export created successfully at "' . $filepath . '". Please note this will only create a zip file that will help you import data into a new BA instance. For a complete backup you should manually backup your uploads folder and database!');

        return Command::SUCCESS;
    }
}
