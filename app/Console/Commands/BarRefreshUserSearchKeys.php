<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Kami\Cocktail\Search\SearchActionsAdapter;
use Kami\Cocktail\Search\SearchActionsContract;

class BarRefreshUserSearchKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:refresh-user-search-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update users meilisearch api keys, used after meilisearch instance updates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /** @var SearchActionsContract */
        $searchActions = app(SearchActionsAdapter::class)->getActions();

        $key = $searchActions->getBarSearchApiKey();

        DB::transaction(function () use ($key) {
            DB::table('users')->where('id', '<>', 1)->update(['search_api_key' => $key]);
        });

        $this->info('Updated API keys!');

        return Command::SUCCESS;
    }
}
