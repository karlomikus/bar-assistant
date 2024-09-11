<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Kami\Cocktail\Models\Bar;
use Illuminate\Console\Command;
use Kami\Cocktail\Models\Export;
use Kami\Cocktail\External\ExportTypeEnum;
use Kami\Cocktail\External\Export\ToDataPack;
use Kami\Cocktail\External\Export\ToRecipeType;
use Kami\Cocktail\External\ForceUnitConvertEnum;

class BarExportRecipes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:export-recipes {barId} {--t|type=datapack : Export type} {--u|units= : Force unit conversion when possible (none, ml, oz, cl)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all recipe data (ingredients, cocktails, base data) from a single bar';

    public function __construct(private readonly ToDataPack $datapackExporter, private readonly ToRecipeType $recipeExporter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $barId = (int) $this->argument('barId');
        $type = ExportTypeEnum::tryFrom($this->option('type') ?? 'datapack');
        $units = ForceUnitConvertEnum::tryFrom($this->option('units') ?? 'none');

        try {
            $bar = Bar::findOrFail($barId);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->line(sprintf('Starting new export (%s | %s) from bar: %s - "%s"', $type->value, $units->value,$bar->id, $bar->name));

        if ($type === ExportTypeEnum::Datapack) {
            $filename = $this->datapackExporter->process($bar->id, Export::generateFilename('datapack'), $units);
        } else {
            $filename = $this->recipeExporter->process($bar->id, Export::generateFilename($type->getFilenameContext()), $type, $units);
        }

        $this->output->success('Data exported to file: ' . $filename);

        return Command::SUCCESS;
    }
}
