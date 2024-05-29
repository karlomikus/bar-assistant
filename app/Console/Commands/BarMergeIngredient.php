<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Ingredient;

class BarMergeIngredient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:merge-ingredients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (App::environment('production')) {
            throw new \Exception('Not ready yet...');
        }

        $listOfIngredientsToMerge = array_unique([
            5143, 5924, 6049, 6197, 6204, 6225, 6604, 6866
        ]);
        $mergeToIngredient = Ingredient::findOrFail(5122);

        $numberOfChanges = DB::table('cocktail_ingredients')
            ->whereIn('ingredient_id', $listOfIngredientsToMerge)
            ->get('cocktail_id')
            ->unique('cocktail_id')
            ->count();

        $this->line('This will remove the following ingredients:');
        foreach (Ingredient::findOrFail($listOfIngredientsToMerge) as $ing) {
            $this->line('   ꞏ ' . $ing->name);
        }

        $this->line(sprintf('This will change %s cocktail recipes', $numberOfChanges));
        $this->info(sprintf('CHANGE ingredients to "%s"', $mergeToIngredient->name));
        $this->warn(sprintf('DELETE %s ingredients', count($listOfIngredientsToMerge)));

        if (!$this->confirm('Continue?')) {
            return Command::INVALID;
        }

        DB::table('cocktail_ingredients')->whereIn('ingredient_id', $listOfIngredientsToMerge)->update([
            'ingredient_id' => $mergeToIngredient->id
        ]);

        // Delete orphan ingredients
        DB::table('ingredients')->whereIn('id', $listOfIngredientsToMerge)->delete();

        $this->info('Finished!');

        return Command::SUCCESS;
    }
}
