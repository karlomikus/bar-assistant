<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Kami\Cocktail\Export\BarToZip;
use Illuminate\Support\Facades\Log;

class BarExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:export-zip {barId*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports bar data and compresses them to a zip file. Can process multiple bar ids';

    public function __construct(private BarToZip $exporter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $barIds = $this->argument('barId');

        try {
            $filename = $this->exporter->process($barIds);
        } catch (Throwable $e) {
            $this->output->error('Unable to create a data export file.');
            Log::error($e->getMessage());

            return Command::FAILURE;
        }

        $this->output->success('Data exported to file: ' . $filename);

        return Command::SUCCESS;
    }
}
