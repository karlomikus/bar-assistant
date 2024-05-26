<?php

use Kami\Cocktail\External\Import\FromVersion2;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (file_exists(storage_path('bar-assistant/database.sqlite'))) {
            // Backup
            $zip = new ZipArchive();

            $filename = storage_path('bar-assistant/backup_v2.zip');

            if ($zip->open($filename, ZipArchive::CREATE) !== true) {
                throw new Exception('Unable to backup old data, stopping...');
            }

            $zip->addGlob(storage_path('bar-assistant/database.sqlite'), options: ['remove_path' => storage_path('bar-assistant')]);
            $zip->addGlob(storage_path('bar-assistant/uploads/*/*'), options: ['remove_path' => storage_path('bar-assistant')]);

            $zip->close();

            // Migrate
            $importer = app(FromVersion2::class);
            $importer->process();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
