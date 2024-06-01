<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Ingredient;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\search;
use function Laravel\Prompts\multisearch;

class BarMergeIngredient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:merge-ingredients {barId : ID of bar to search ingredients in}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge multiple ingredients into one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $barId = $this->argument('barId');

        $toIngredientId = search(
            'Search ingredient',
            fn (string $value) => strlen($value) > 0
                ? DB::table('ingredients')->orderBy('name')->where('bar_id', $barId)->where('name', 'like', '%' . $value . '%')->pluck('name', 'id')->toArray()
                : [],
            scroll: 10,
            required: true
        );

        try {
            $mergeToIngredient = Ingredient::findOrFail($toIngredientId);
        } catch (\Throwable) {
            $this->error('Cannot find ingredient with ID: ' . $toIngredientId);

            return Command::FAILURE;
        }

        $listOfIngredientsToMerge = multisearch(
            'Search for ingredients to merge into "' . $mergeToIngredient->name . '"',
            fn (string $value) => strlen($value) > 0
                ? DB::table('ingredients')->orderBy('name')->where('bar_id', $barId)->where('name', 'like', '%' . $value . '%')->pluck('name', 'id')->toArray()
                : [],
            scroll: 15,
            required: true
        );

        $numberOfChanges = DB::table('cocktail_ingredients')
            ->whereIn('ingredient_id', $listOfIngredientsToMerge)
            ->get('cocktail_id')
            ->unique('cocktail_id')
            ->count();

        $possibleIngredients = Ingredient::find($listOfIngredientsToMerge);
        $possibleIngredients = $possibleIngredients->filter(fn ($i) => (int) $i->id !== (int) $toIngredientId);

        if ($possibleIngredients->isEmpty()) {
            $this->error('Cannot find find any ingredients to merge.');

            return Command::FAILURE;
        }

        $this->line('This will remove the following ingredients:');
        foreach ($possibleIngredients as $ing) {
            $this->line('   ・ ' . $ing->name);
        }
        $this->line(sprintf('and merge them into "%s"', $mergeToIngredient->name));
        $this->line(sprintf('This will change %s cocktail recipes', $numberOfChanges));
        $this->line('');

        if (!confirm('Continue with merging?')) {
            return Command::INVALID;
        }

        spin(
            function () use ($listOfIngredientsToMerge, $mergeToIngredient, $possibleIngredients) {
                DB::table('cocktail_ingredients')->whereIn('ingredient_id', $listOfIngredientsToMerge)->update([
                    'ingredient_id' => $mergeToIngredient->id
                ]);

                // Delete orphan ingredients
                DB::table('ingredients')->whereIn('id', $possibleIngredients->pluck('id'))->delete();
            },
            'Merging ingredients...'
        );

        $this->info('Finished!');

        return Command::SUCCESS;
    }
}
