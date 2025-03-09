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
        // Schema::table('ingredient_categories', function (Blueprint $table) {
        //     $table->nestedSet();
        // });

        // $bars = \Illuminate\Support\Facades\DB::table('bars')->pluck('id');
        // foreach ($bars as $barId) {
        //     \Kami\Cocktail\Models\IngredientCategory::scoped(['bar_id' => $barId])->fixTree();
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('ingredient_categories', function (Blueprint $table) {
        //     $table->dropNestedSet();
        // });
    }
};
