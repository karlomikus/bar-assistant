<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Artisan;
use Kami\Cocktail\Services\Image\ImageCleanupService;

class BarMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:maintenance {barId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will remove unused images, update missing ABVs, fix ingredient sort and refresh cache';

    public function __construct(private readonly ImageCleanupService $imageCleanupService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $barId = (int) $this->argument('barId');

        $this->info('Updating cocktail ABVs...');
        Cocktail::where('bar_id', $barId)->with('ingredients.ingredient')->chunk(50, function ($cocktails) {
            foreach ($cocktails as $cocktail) {
                $calculatedAbv = $cocktail->getABV();
                $cocktail->abv = $calculatedAbv;
                $cocktail->save();
            }
        });

        // Fix sort
        // $this->info('Fixing cocktail ingredients sort order...');
        // $this->fixSort($barId);

        // Clear unused images
        $this->info('Clearing unused images...');
        $cleanupResult = $this->imageCleanupService->deleteUnusedImages();
        if ($cleanupResult->deletedImages > 0) {
            $this->info('Deleted ' . $cleanupResult->deletedImages . ' images.');
        }
        if ($cleanupResult->deletedTemporaryFiles > 0) {
            $this->info('Deleted ' . $cleanupResult->deletedTemporaryFiles . ' temporary images.');
        }

        // Update indexes
        $this->info('Updating search indexes...');
        Artisan::call('bar:refresh-search --clear');

        // Clear cache
        $this->info('Clearing cache...');
        Artisan::call('cache:clear');

        $this->output->success('Done!');

        return Command::SUCCESS;
    }

    // private function fixSort(int $barId): void
    // {
    //     DB::table('cocktails')->where('bar_id', $barId)->orderBy('id')->lazy()->each(function ($cocktail) {
    //         $ingredients = DB::table('cocktail_ingredients')->where('cocktail_id', $cocktail->id)->get();
    //         $i = 1;
    //         foreach ($ingredients as $ci) {
    //             DB::table('cocktail_ingredients')->where('id', $ci->id)->orderBy('id')->update(['sort' => $i]);
    //             $i++;
    //         }
    //     });
    // }
}
