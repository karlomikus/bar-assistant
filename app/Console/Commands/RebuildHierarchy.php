<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Repository\IngredientRepository;

class RebuildHierarchy extends Command
{
    protected $signature = 'bar:rebuild-hierarchy {barId}';

    protected $description = 'Rebuilds ingredient hierarchy of a single bar';

    public function handle(): int
    {
        /** @var IngredientRepository */
        $repo = resolve(IngredientRepository::class);

        $barId = (int) $this->argument('barId');

        try {
            $repo->rebuildMaterializedPath($barId);
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->output->success('Done!');

        return Command::SUCCESS;
    }
}
