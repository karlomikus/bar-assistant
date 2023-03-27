<?php

use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kami\Cocktail\Services\ImageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('placeholder_hash')->nullable();
        });

        $imageService = app(ImageService::class);
        Image::all()->each(function ($image) use ($imageService) {
            $hash = $imageService->generateThumbHash(
                $image->asInterventionImage()
            );

            $image->placeholder_hash = $hash;
            $image->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn('placeholder_hash');
        });
    }
};
