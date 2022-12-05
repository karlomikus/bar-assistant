<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Scraper\Manager;
use Kami\Cocktail\Services\ImportService;

class BarScrape extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:scrape {url : URL of the recipe} {--i|skip-ingredients : Do not add ingredients} {--tags= : Overwrite tags, seperated by comma}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import cocktail recipe from a given URL';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $scraper = Manager::scrape($this->argument('url'));

        $scrapedData = $scraper->toArray();

        if ($this->option('skip-ingredients')) {
            $scrapedData['ingredients'] = [];
        }

        if ($this->option('tags')) {
            $scrapedData['tags'] = explode(',', $this->option('tags'));
        }
        dd($scrapedData);
        resolve(ImportService::class)->import($scrapedData);

        return Command::SUCCESS;
    }
}
