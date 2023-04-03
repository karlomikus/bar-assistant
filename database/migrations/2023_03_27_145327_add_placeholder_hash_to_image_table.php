<?php

use Kami\Cocktail\Models\Image;
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

        foreach (Image::whereNotNull('imageable_type')->cursor() as $image) {
            try {
                $hash = $imageService->generateThumbHash(
                    $image->asInterventionImage(),
                    true
                );
    
                $image->placeholder_hash = $hash;
                $image->save();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error(
                    'Placeholder hash generation migration error: ' . $e->getMessage()
                );
            }
        }
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
