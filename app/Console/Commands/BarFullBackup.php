<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Export\FullBackupToZip;

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
    protected $description = 'Create a .zip file with the full backup of your files';

    public function __construct(private FullBackupToZip $exporter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->exporter->process();

        return Command::SUCCESS;
    }
}
