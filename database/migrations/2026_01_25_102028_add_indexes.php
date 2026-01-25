<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cocktails', function (Blueprint $table) {
            $table->index('parent_cocktail_id', 'c_par_cocktail_id_index');
        });

        Schema::table('cocktail_utensil', function (Blueprint $table) {
            $table->index('cocktail_id', 'cu_cocktail_id_index');
        });

        Schema::table('utensils', function (Blueprint $table) {
            $table->index('bar_id', 'u_bar_id_index');
        });

        Schema::table('calculators', function (Blueprint $table) {
            $table->index('bar_id', 'calc_bar_id_index');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->index('bar_id', 'tags_bar_id_index');
        });

        Schema::table('glasses', function (Blueprint $table) {
            $table->index('bar_id', 'g_bar_id_index');
        });

        Schema::table('cocktail_methods', function (Blueprint $table) {
            $table->index('bar_id', 'cm_bar_id_index');
        });

        Schema::table('price_categories', function (Blueprint $table) {
            $table->index('bar_id', 'pc_bar_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cocktails', function (Blueprint $table) {
            $table->dropIndex('c_par_cocktail_id_index');
        });

        Schema::table('cocktail_utensil', function (Blueprint $table) {
            $table->dropIndex('cu_cocktail_id_index');
        });

        Schema::table('utensils', function (Blueprint $table) {
            $table->dropIndex('u_bar_id_index');
        });

        Schema::table('calculators', function (Blueprint $table) {
            $table->dropIndex('calc_bar_id_index');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('tags_bar_id_index');
        });

        Schema::table('glasses', function (Blueprint $table) {
            $table->dropIndex('g_bar_id_index');
        });

        Schema::table('cocktail_methods', function (Blueprint $table) {
            $table->dropIndex('cm_bar_id_index');
        });

        Schema::table('price_categories', function (Blueprint $table) {
            $table->dropIndex('pc_bar_id_index');
        });
    }
};
