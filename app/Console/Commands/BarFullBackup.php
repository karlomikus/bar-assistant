<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\External\Export\FullBackupToZip;

class BarFullBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:full-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a .zip file with the full backup of your Bar Assistant instance data';

    public function __construct(private FullBackupToZip $exporter)
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
