<?php

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\CocktailIngredient;

class ImportFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dbIngredients = DB::table('ingredients')->select(['name', 'id'])->get()->map(function ($ing) {
            $ing->name = strtolower($ing->name);

            return $ing;
        });
        // dd($dbIngredients);
        $source = json_decode(file_get_contents(resource_path('/data/iba_cocktails.json')), true);
        
        foreach ($source as $sCocktail) {
            DB::beginTransaction();
            try {
                $cocktail = new Cocktail();
                $cocktail->name = $sCocktail['name'];
                $cocktail->instructions = $sCocktail['instructions'][0];
                $cocktail->garnish = $sCocktail['garnish'][0];
                $cocktail->save();

                foreach ($sCocktail['ingredients'] as $cIngredient) {
                    $split = explode(' ', $cIngredient);
                    $amount = $split[0];
                    $units = $split[1];
                    $output = array_splice($split, 2);
                    $sIngredient = implode(' ', $output);

                    if (!$dbIngredients->contains('name', strtolower($sIngredient))) {
                        dump('Ingredient not found: [' . $sCocktail['name'] . '] ' . $sIngredient);
                        continue;
                    }
                    $dbId = $dbIngredients->filter(fn ($item) => $item->name == strtolower($sIngredient))->first()->id;

                    $cocktailIng = new CocktailIngredient();
                    $cocktailIng->cocktail_id = $cocktail->id;
                    $cocktailIng->ingredient_id = $dbId;
                    $cocktailIng->amount = floatval($amount);
                    $cocktailIng->units = strtolower($units);
                    $cocktailIng->save();
                }
            } catch(Throwable $e) {
                dd($e->getMessage());
                DB::rollBack();
            }
            DB::commit();
        }

        return Command::SUCCESS;
    }
}
