<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;

class DumpSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:dump-search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a meilisearch database dump task.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /** @var \Meilisearch\Client */
        $engine = app(\Laravel\Scout\EngineManager::class)->engine();

        $engine->createDump();

        $this->info('Dump task enqueued.');
        $this->info('The dump creation process is an asynchronous task that takes time proportional to the size of your dataset.');

        return Command::SUCCESS;
    }
}
