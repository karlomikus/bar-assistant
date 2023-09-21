<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Jobs\ProcessBarBackup;

class BarBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:backup {barId*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete backup of all data related to a bar.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $barIds = $this->argument('barId');

        ProcessBarBackup::dispatch($barIds);

        $this->info('Backup started successfully!');

        return Command::SUCCESS;
    }
}
