<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Models\Cocktail;
use Symfony\Component\Yaml\Yaml;

class BarExportCocktails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export cocktails and ingredients to a human readable yaml file.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dump = [];
        Cocktail::with('tags', 'ingredients.ingredient')->orderBy('created_at')->chunk(200, function ($cocktails) use (&$dump) {
            foreach ($cocktails as $cocktail) {
                $dump[] = [
                    'name' => $cocktail->name,
                    'description' => $cocktail->description,
                    'instructions' => $cocktail->instructions,
                    'garnish' => $cocktail->garnish,
                    'source' => $cocktail->source,
                    'image_copyright' => $cocktail->images->first()->copyright ?? null,
                    'tags' => $cocktail->tags->pluck('name')->toArray(),
                    'glass' => $cocktail->glass->name,
                    'ingredients' => $cocktail->ingredients->map(function ($cIng) {
                        return [
                            'amount' => $cIng->amount,
                            'units' => $cIng->units,
                            'name' => $cIng->ingredient->name,
                            'optional' => (bool) $cIng->optional
                        ];
                    })->toArray()
                ];
            }
        });

        $yaml = Yaml::dump($dump, 10, flags: Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        file_put_contents(storage_path('cocktails-dump.yml'), $yaml);

        return Command::SUCCESS;
    }
}
