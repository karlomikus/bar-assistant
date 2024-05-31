<?php

namespace Kami\Cocktail\Console\Commands;

use Kami\Cocktail\Models\Bar;
use Illuminate\Console\Command;
use Kami\RecipeUtils\Parser\Parser;
use Kami\Cocktail\DTO\Cocktail\Cocktail;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\DTO\Ingredient\Ingredient;
use Kami\Cocktail\External\Import\FromArray;

class Fill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:fill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /** @var FromArray */
        $service = resolve(FromArray::class);
        $source = json_decode(file_get_contents(storage_path('result_imbibe.json')), true);
        // $source = array_slice($source, 0, 5);

        foreach ($source as $data) {
            if (empty($data['name'])) {
                continue;
            }

            $ingredients = [];
            foreach ($data['ingredients'] ?? [] as $ing) {
                if (str_starts_with($ing, '____')) {
                    break;
                }

                $parsed = Parser::line($ing);

                $ingredients[] = [
                    'name' => $parsed->name,
                    'amount' => $parsed->amount,
                    'units' => $parsed->units,
                    'amount_max' => $parsed->amountMax,
                ];
            }

            if (empty($ingredients)) {
                continue;
            }

            $service->process([
                'name' => $data['name'],
                'instructions' => $data['instructions'],
                'description' => $data['description'],
                'source' => $data['url'],
                'tags' => $data['tags'],
                'ingredients' => $ingredients,
                'images' => [
                    ['url' => $data['image'], 'copyright' => 'Imbibe magazine']
                ],
            ], 1, 37);

            sleep(2);
        }
    }
}
