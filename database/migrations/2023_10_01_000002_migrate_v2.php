<?php

use Kami\Cocktail\Import\FromVersion2;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (file_exists(storage_path('bar-assistant/database.sqlite'))) {
            // Backup
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
