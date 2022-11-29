<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Scraper\Manager;
use Illuminate\Database\Eloquent\Model;

class BarScrape extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:scrape {url}';

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
        Model::unguard();

        $scraper = Manager::scrape($this->argument('url'));

        dump($scraper->toArray());

        return Command::SUCCESS;
    }
}
