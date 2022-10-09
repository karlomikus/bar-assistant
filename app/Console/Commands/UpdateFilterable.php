<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Scout\EngineManager;

class UpdateFilterable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:filterable';

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
    public function handle(EngineManager $manager)
    {
        $manager->engine()->index('cocktails')->updateSettings([
            'filterableAttributes' => ['tags'],
            'sortableAttributes' => ['id', 'name', 'date']
        ]);

        return Command::SUCCESS;
    }
}
