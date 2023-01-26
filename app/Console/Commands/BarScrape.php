<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Kami\Cocktail\Scraper\Manager;
use Kami\Cocktail\Services\ImportService;
use Kami\Cocktail\Scraper\ScraperInfoContract;

class BarScrape extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:scrape {url : URL of the recipe} {--i|skip-ingredients : Do not add ingredients} {--tags= : Overwrite tags, seperated by comma} {--name= : Overwrite cocktail name} {--d|dump : Do not import data, just dump it}';

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
        try {
            $scraper = Manager::scrape($this->argument('url'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($scraper instanceof ScraperInfoContract) {
            $this->info($scraper->getInfoMessage());
        }

        $scrapedData = $scraper->toArray();

        if ($this->option('skip-ingredients')) {
            $scrapedData['ingredients'] = [];
        }

        if ($this->option('tags')) {
            $scrapedData['tags'] = explode(',', $this->option('tags'));
        }

        if ($this->option('name')) {
            $scrapedData['name'] = $this->option('name');
        }

        if ($this->option('dump')) {
            dump($scrapedData);

            return Command::SUCCESS;
        }

        try {
            resolve(ImportService::class)->importFromScraper($scrapedData);

            $this->info('Cocktail imported successfully, do not forget to check the imported data for errors.');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
