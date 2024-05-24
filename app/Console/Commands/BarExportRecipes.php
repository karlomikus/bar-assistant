<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Kami\Cocktail\Models\Bar;
use Illuminate\Console\Command;
use Kami\Cocktail\External\Export\Recipes;
use Kami\Cocktail\External\ExportTypeEnum;

class BarExportRecipes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:export-recipes {barId} {--t|type=yml : Export type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all recipe data (ingredients, cocktails, base data) from a single bar';

    public function __construct(private readonly Recipes $exporter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $barId = (int) $this->argument('barId');
        $type = $this->option('type') ?? 'yml';

        $type = ExportTypeEnum::tryFrom($type);

        try {
            $bar = Bar::findOrFail($barId);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->output->info(sprintf('Starting recipe export from bar: %s - "%s"', $bar->id, $bar->name));

        $filename = $this->exporter->process($barId, null, $type);

        $this->output->success('Data exported to file: ' . $filename);

        return Command::SUCCESS;
    }
}
