<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Kami\Cocktail\Models\Bar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Services\MeilisearchService;

class SetupMeilisearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:setup-meilisearch {--f|force : Force search token update for all bars}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Using master key is not recommended for API calls. This command will generate a new scoped API key and update the ENV file.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (config('scout.driver') !== 'meilisearch') {
            $this->error('Search driver is not set to "meilisearch". Skipping.');

            return Command::INVALID;
        }

        $forceTokenChange = (bool) $this->option('force');
        $isNewMeilisearchKey = true;

        try {
            $this->line('Setting up bar search tokens...');
            /** @var MeilisearchService */
            $meilisearch = resolve(MeilisearchService::class);
            $searchApiKey = $meilisearch->getSearchAPIKey();
            $isNewMeilisearchKey = $meilisearch->isNewMeilisearchKey();
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->error('Unable to request Meilisearch keys. This could mean your Meilisearch instance is not running correctly. Skipping...');

            return Command::INVALID;
        }

        if (!$isNewMeilisearchKey && !$forceTokenChange) {
            $this->line('Skipping Meilisearch setup. Key did not change.');

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($searchApiKey) {
            $bars = Bar::all();
            foreach ($bars as $bar) {
                $bar->updateSearchToken($searchApiKey->getUid(), $searchApiKey->getKey());
            }
        });

        Cache::flush();

        $this->info('Meilisearch setup done!');

        return Command::SUCCESS;
    }
}
