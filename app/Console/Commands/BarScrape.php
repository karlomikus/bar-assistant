<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Scraper\Manager;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;
use Intervention\Image\ImageManagerStatic as InterventionImage;

class BarScrape extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:scrape {url}';

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
        $scraper = Manager::scrape($this->argument('url'));

        $scrapedData = $scraper->toArray();

        /** @var IngredientService */
        $ingredientService = app(IngredientService::class);
        /** @var CocktailService */
        $cocktailService = app(CocktailService::class);

        $dbIngredients = DB::table('ingredients')->select('id', DB::raw('LOWER(name) AS name'))->get()->keyBy('name');
        $dbGlasses = DB::table('glasses')->select('id', DB::raw('LOWER(name) AS name'))->get()->keyBy('name');

        $cocktailImages = [];
        if ($scrapedData['image']['url']) {
            $memImage = InterventionImage::make($scrapedData['image']['url']);

            $filepath = 'temp/' . Str::random(40) . '.jpg';
            $memImage->save(storage_path('uploads/' . $filepath));

            $image = new Image();
            $image->copyright = $scrapedData['image']['copyright'] ?? null;
            $image->file_path = $filepath;
            $image->file_extension = 'jpg';
            $image->save();

            $cocktailImages[] = $image->id;
        }

        // Match ingredients
        foreach ($scrapedData['ingredients'] as &$scrapedIngredient) {
            if ($dbIngredients->has(strtolower($scrapedIngredient['name']))) {
                $scrapedIngredient['ingredient_id'] = $dbIngredients->get(strtolower($scrapedIngredient['name']))->id;
            } else {
                $this->info('Creating a new ingredient: ' . $scrapedIngredient['name']);
                $newIngredient = $ingredientService->createIngredient(ucfirst($scrapedIngredient['name']), 1, 1, description: 'Created by scraper from ' . $scrapedData['source']);
                $dbIngredients->put(strtolower($scrapedIngredient['name']), $newIngredient->id);
                $scrapedIngredient['ingredient_id'] = $newIngredient->id;
            }
        }

        // Match glass
        $glassId = null;
        if ($dbGlasses->has(strtolower($scrapedData['glass']))) {
            $glassId = $dbGlasses->get(strtolower($scrapedData['glass']))->id;
        } elseif ($scrapedData['glass'] !== null) {
            $this->info('Creating a new glass type: ' . $scrapedData['glass']);
            $newGlass = new Glass();
            $newGlass->name = ucfirst($scrapedData['glass']);
            $newGlass->description = 'Created by scraper from ' . $scrapedData['source'];
            $newGlass->save();
            $dbGlasses->put(strtolower($scrapedData['glass']), $newGlass->id);
            $glassId = $newGlass->id;
        }

        $cocktailService->createCocktail(
            $scrapedData['name'],
            $scrapedData['instructions'],
            $scrapedData['ingredients'],
            1,
            $scrapedData['description'],
            $scrapedData['garnish'],
            $scrapedData['source'],
            $cocktailImages,
            $scrapedData['tags'],
            $glassId
        );

        return Command::SUCCESS;
    }
}
