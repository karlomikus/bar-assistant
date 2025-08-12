<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Kami\Cocktail\Models\Bar;
use Illuminate\Console\Command;
use Kami\Cocktail\Models\Export;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\search;
use function Laravel\Prompts\spin;

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
    protected $signature = 'bar:export-recipes {barId?} {--t|type=datapack : Export type (datapack, schema, md, json-ld, xml, yaml)} {--u|units= : Force unit conversion when possible (none, ml, oz, cl)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export data from a single bar';

    protected $help = 'This command allows you to export data from a single bar in various formats. You can specify the bar ID as an argument, or search for it interactively if not provided.';

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

        $this->newLine();
        $this->line('Exporting data from a bar');
        $this->newLine();
        $this->line('Selected export type: ' . ($type?->value ?? 'datapack'));
        $this->line('Selected unit conversion: ' . ($units?->value ?? 'none'));

        if ($barId === 0) {
            $barId = search(
                'Search bars by name',
                fn (string $value) => strlen($value) > 0
                    ? DB::table('bars')->orderBy('name')->where('name', 'like', '%' . $value . '%')->pluck('name', 'id')->toArray()
                    : [],
                scroll: 10,
                required: true
            );
        }

        try {
            $bar = Bar::findOrFail($barId);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->line(sprintf('Starting new %s export from bar: [%s] %s', $type->value, $bar->id, $bar->name));

        $filename = spin(
            callback: function () use ($bar, $type, $units) {
                if ($type === ExportTypeEnum::Datapack) {
                    $filename = $this->datapackExporter->process($bar->id, Export::generateFilename('datapack'), $units);
                } else {
                    $filename = $this->recipeExporter->process($bar->id, Export::generateFilename($type->getFilenameContext()), $type, $units);
                }

                return $filename;
            },
            message: 'Generating...'
        );

        $this->output->success('Data exported to file: ' . $filename);

        return Command::SUCCESS;
    }
}
