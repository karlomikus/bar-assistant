<?php

namespace Kami\Cocktail\Console\Commands;

use ZipArchive;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BarExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:export-zip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export data to be used in a another Bar Assistant instance.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $meta = [
            'version_exported_from' => config('bar-assistant.version'),
        ];

        $tablesToExport = [
            'ingredient_categories',
            'glasses',
            'tags',
            'ingredients',
            'cocktails',
            'cocktail_ingredients',
            'cocktail_ingredient_substitutes',
            'cocktail_tag',
            'images',
        ];

        $zip = new ZipArchive();
        $filename = storage_path(sprintf('%s_%s.zip', 'ba_export', Carbon::now()->format('Y-m-d-h-i-s')));

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            $this->error('Unable to create a ZIP archive.');

            return Command::FAILURE;
        }

        $zip->addGlob(storage_path('uploads/*/*'), options: ['remove_path' => storage_path()]);

        foreach ($tablesToExport as $tableName) {
            $zip->addFromString($tableName . '.json', json_encode(DB::table($tableName)->get()->toArray()));
        }

        $zip->addFromString('_meta.json', json_encode($meta));

        $zip->close();

        $this->info('Export created successfully at "' . $filename . '". Please note this will only create a zip file that will help you import data into a new BA instance. For a complete backup you should manually backup your uploads folder and database!');

        return Command::SUCCESS;
    }
}
