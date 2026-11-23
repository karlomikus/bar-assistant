<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\External\Export\ToFullBackup;

#[\Illuminate\Console\Attributes\Description('Create a .zip file with the full backup of your Bar Assistant instance data')]
#[\Illuminate\Console\Attributes\Signature('bar:full-backup')]
class BarFullBackup extends Command
{
    public function __construct(private readonly ToFullBackup $exporter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $filename = $this->exporter->process();
        } catch (Throwable $e) {
            $this->output->error('Unable to create a data export file.');
            Log::error($e->getMessage());

            return Command::FAILURE;
        }

        $this->output->success('Backup exported to file: ' . $filename);

        return Command::SUCCESS;
    }
}
