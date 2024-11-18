<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\PriceCategory;
use Kami\Cocktail\Models\IngredientPrice;

class BarSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[DEV] Seed with random data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ingredients = Ingredient::all();
        $priceCategories = PriceCategory::all();

        foreach ($priceCategories as $priceCategory) {
            foreach ($ingredients as $ingredient) {
                IngredientPrice::factory()->for($ingredient)->for($priceCategory)->create(['units' => 'ml']);
            }
        }

        $this->output->success('Done!');

        return Command::SUCCESS;
    }
}
