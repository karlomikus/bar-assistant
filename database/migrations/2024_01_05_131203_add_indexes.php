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
        Schema::table('cocktail_ingredients', function (Blueprint $table) {
            $table->index('cocktail_id', 'ci_cocktail_id_index');
            $table->index('ingredient_id', 'ci_ingredient_id_index');
        });

        Schema::table('user_ingredients', function (Blueprint $table) {
            $table->index('bar_membership_id', 'ui_bar_membership_id_index');
            $table->index('ingredient_id', 'ui_ingredient_id_index');
        });

        Schema::table('cocktails', function (Blueprint $table) {
            $table->index('bar_id', 'c_bar_id_index');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->index('bar_id', 'i_bar_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cocktail_ingredients', function (Blueprint $table) {
            $table->dropIndex('ci_cocktail_id_index');
            $table->dropIndex('ci_ingredient_id_index');
        });

        Schema::table('user_ingredients', function (Blueprint $table) {
            $table->dropIndex('ui_bar_membership_id_index');
            $table->dropIndex('ui_ingredient_id_index');
        });

        Schema::table('cocktails', function (Blueprint $table) {
            $table->dropIndex('c_bar_id_index');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropIndex('i_bar_id_index');
        });
    }
};
