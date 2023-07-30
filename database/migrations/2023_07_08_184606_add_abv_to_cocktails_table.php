<?php

use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cocktails', function (Blueprint $table) {
            $table->decimal('abv')->nullable();
            $table->index('abv', 'cocktails_abv_index');
        });

        Cocktail::with('ingredients.ingredient')->chunk(50, function (Collection $cocktails) {
            foreach ($cocktails as $cocktail) {
                $calculatedAbv = $cocktail->getABV();
                $cocktail->abv = $calculatedAbv;
                $cocktail->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cocktails', function (Blueprint $table) {
            $table->dropIndex('cocktails_abv_index');
            $table->removeColumn('abv');
        });
    }
};
