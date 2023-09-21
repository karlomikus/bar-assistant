<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BarMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:maintenance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Cocktail::with('ingredients.ingredient')->chunk(50, function ($cocktails) {
            foreach ($cocktails as $cocktail) {
                $calculatedAbv = $cocktail->getABV();
                $cocktail->abv = $calculatedAbv;
                $cocktail->save();
            }
        });

        // Fix sort
        $this->info('Fixing cocktail ingredients sort order...');
        $this->fixSort();

        // Clear unused images
        $this->info('Checking unused images...');
        // $this->deleteUnusedImages();

        // Optimize images
        $this->info('Optimizing images...');
        // $this->optimizeImages();

        // Update indexes
        Artisan::call('bar:refresh-search --clear');

        // Clear cache
        Artisan::call('cache:clear');

        return Command::SUCCESS;
    }

    private function fixSort(): void
    {
        DB::table('cocktails')->orderBy('id')->lazy()->each(function ($cocktail) {
            $ingredients = DB::table('cocktail_ingredients')->where('cocktail_id', $cocktail->id)->get();
            $i = 1;
            foreach ($ingredients as $ci) {
                DB::table('cocktail_ingredients')->where('id', $ci->id)->orderBy('id')->update(['sort' => $i]);
                $i++;
            }
        });
    }

    // private function deleteUnusedImages(): void
    // {
    //     $baDisk = Storage::disk('bar-assistant');
    //     $images = Image::whereNull('imageable_id')->get();

    //     if ($images->isNotEmpty()) {
    //         $i = 0;
    //         foreach ($images as $image) {
    //             try {
    //                 $i++;
    //                 $image->delete();
    //             } catch (Throwable) {
    //             }
    //         }
    //         $this->info('Deleted ' . $i . ' images.');
    //     }

    //     $tempFiles = $baDisk->files('temp/');

    //     if (count($tempFiles) > 0) {
    //         $baDisk->delete($tempFiles);
    //         $this->info('Deleted ' . count($tempFiles) . ' temporary images.');
    //     }
    // }

    public function optimizeImages(): void
    {
        $baDisk = Storage::disk('bar-assistant');

        $cocktailImages = $baDisk->files('cocktails');
        $ingredientImages = $baDisk->files('ingredients');
        $paths = array_merge($cocktailImages, $ingredientImages);

        $bar = $this->output->createProgressBar(count($paths));
        $bar->start();
        foreach ($paths as $imagePath) {
            $fullPath = $baDisk->path($imagePath);

            if (mime_content_type($fullPath) === 'image/jpeg') {
                Process::quietly()->run('jpegoptim ' . escapeshellarg($fullPath) . ' --max=90 -s --all-progressive');
            }

            if (mime_content_type($fullPath) === 'image/png') {
                Process::quietly()->run('pngquant --quality=80-95 --force --skip-if-larger ' . escapeshellarg($fullPath) . ' --output=' . escapeshellarg($fullPath));
            }

            $bar->advance();
        }
        $bar->finish();
    }
}
