<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

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

    private Filesystem $disk;

    public function __construct()
    {
        parent::__construct();

        $this->disk = Storage::disk('uploads');
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
        $this->info('Fixing cocktail ingredients sort order...');
        $this->fixSort($barId);

        // Clear unused images
        $this->info('Clearing unused images...');
        $this->deleteUnusedImages();

        // Update indexes
        $this->info('Updating search indexes...');
        Artisan::call('bar:refresh-search --clear');

        // Clear cache
        $this->info('Clearing cache...');
        Artisan::call('cache:clear');

        $this->output->success('Done!');

        return Command::SUCCESS;
    }

    private function fixSort(int $barId): void
    {
        DB::table('cocktails')->where('bar_id', $barId)->orderBy('id')->lazy()->each(function ($cocktail) {
            $ingredients = DB::table('cocktail_ingredients')->where('cocktail_id', $cocktail->id)->get();
            $i = 1;
            foreach ($ingredients as $ci) {
                DB::table('cocktail_ingredients')->where('id', $ci->id)->orderBy('id')->update(['sort' => $i]);
                $i++;
            }
        });
    }

    private function deleteUnusedImages(): void
    {
        $images = Image::whereNull('imageable_id')->get();

        if ($images->isNotEmpty()) {
            $i = 0;
            foreach ($images as $image) {
                try {
                    $i++;
                    $image->delete();
                } catch (Throwable) {
                }
            }
            $this->info('Deleted ' . $i . ' images.');
        }

        $tempFiles = $this->disk->files('temp/');

        if (count($tempFiles) > 0) {
            $this->disk->delete($tempFiles);
            $this->info('Deleted ' . count($tempFiles) . ' temporary images.');
        }
    }
}
