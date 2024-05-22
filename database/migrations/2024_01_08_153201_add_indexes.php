<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cocktail_ingredient_substitutes', function (Blueprint $table) {
            $table->index('cocktail_ingredient_id', 'cis_cocktail_ingredient_id_index');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->index('parent_ingredient_id', 'i_parent_ingredient_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cocktail_ingredient_substitutes', function (Blueprint $table) {
            $table->dropIndex('cis_cocktail_ingredient_id_index');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropIndex('i_parent_ingredient_id_index');
        });
    }
};
