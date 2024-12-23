<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cocktail_ingredient_substitutes', function (Blueprint $table) {
            $table->index(['cocktail_ingredient_id', 'ingredient_id'], 'idx_ing_subs_composite');
        });
        Schema::table('cocktail_ingredients', function (Blueprint $table) {
            $table->index(['cocktail_id', 'optional', 'ingredient_id'], 'idx_cocktail_ing_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cocktail_ingredient_substitutes', function (Blueprint $table) {
            $table->dropIndex('idx_ing_subs_composite');
        });
        Schema::table('cocktail_ingredients', function (Blueprint $table) {
            $table->dropIndex('idx_cocktail_ing_composite');
        });
    }
};
