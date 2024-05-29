<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Ingredient;
use function Laravel\Prompts\text;
use function Laravel\Prompts\multiselect;

class BarMergeIngredient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:merge-ingredients {barId} {toIngredientId}';

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
        if (App::environment('production')) {
            throw new \Exception('Not ready yet...');
        }

        $barId = $this->argument('barId');
        $toIngredientId = $this->argument('toIngredientId');

        try {
            $mergeToIngredient = Ingredient::findOrFail($toIngredientId);
        } catch (\Throwable) {
            $this->error('Cannot find ingredient with ID: ' . $toIngredientId);

            return Command::FAILURE;
        }

        $searchTerm = text('Search for ingredients to merge into "' . $mergeToIngredient->name . '"', required: true);

        $ingredientChoices = DB::table('ingredients')->where('bar_id', $barId)->where('name', 'like', '%' . $searchTerm . '%')->get();

        $listOfIngredientsToMerge = multiselect(
            label: 'What ingredients do you want to merge?',
            options: $ingredientChoices->pluck('name', 'id'),
            scroll: 15,
            required: true,
        );

        $numberOfChanges = DB::table('cocktail_ingredients')
            ->whereIn('ingredient_id', $listOfIngredientsToMerge)
            ->get('cocktail_id')
            ->unique('cocktail_id')
            ->count();

        $this->line('This will remove the following ingredients:');
        $possibleIngredients = Ingredient::find($listOfIngredientsToMerge);
        if ($possibleIngredients->isEmpty()) {
            $this->error('Cannot find find any ingredients to merge.');

            return Command::FAILURE;
        }

        $possibleIngredients = $possibleIngredients->filter(fn ($i) => (int) $i->id !== (int) $toIngredientId);

        foreach ($possibleIngredients as $ing) {
            $this->line('   ・ ' . $ing->name);
        }

        $this->line(sprintf('This will change %s cocktail recipes', $numberOfChanges));
        $this->info(sprintf('CHANGE ingredients to "%s"', $mergeToIngredient->name));
        $this->warn(sprintf('DELETE %s ingredients', count($listOfIngredientsToMerge)));

        if (!$this->confirm('Confirm merge?')) {
            return Command::INVALID;
        }

        DB::table('cocktail_ingredients')->whereIn('ingredient_id', $listOfIngredientsToMerge)->update([
            'ingredient_id' => $mergeToIngredient->id
        ]);

        // Delete orphan ingredients
        DB::table('ingredients')->whereIn('id', $possibleIngredients->pluck('id'))->delete();

        $this->info('Finished!');

        return Command::SUCCESS;
    }
}
