<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\SearchActions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

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
        $key = App::environment('demo') ? SearchActions::getPublicDemoApiKey() : SearchActions::getPublicApiKey();

        DB::transaction(function () use ($key) {
            DB::table('users')->where('id', '<>', 1)->update(['search_api_key' => $key]);
        });

        $this->info('Updated API keys!');

        return Command::SUCCESS;
    }
}
