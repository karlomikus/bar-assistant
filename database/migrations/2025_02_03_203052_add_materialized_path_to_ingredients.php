<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('materialized_path')->nullable()->index('ing_path_index');
        });

        // Migrate existing ingredient categories to ingredients
        // Update materialized path for ingredients with ingredient category
        $categories = DB::table('ingredient_categories')->get();
        foreach ($categories as $category) {
            $userId = DB::table('bars')->select('created_user_id')->where('id', $category->bar_id)->first()->created_user_id;
            $newIngredientId = DB::table('ingredients')->insertGetId([
                'name' => $category->name,
                'slug' => Str::slug($category->name) . '-' . $category->bar_id,
                'description' => $category->description,
                'bar_id' => $category->bar_id,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
                'created_user_id' => $userId,
            ]);

            DB::table('ingredients')
                ->where('bar_id', $category->bar_id)
                ->where('ingredient_category_id', $category->id)
                ->update(['materialized_path' => $newIngredientId . '/', 'parent_ingredient_id' => $newIngredientId]);
        }

        // Update materialized path for ingredients with existing parent ingredient
        DB::select("UPDATE ingredients SET materialized_path = materialized_path || parent_ingredient_id || '/' WHERE parent_ingredient_id IS NOT NULL");

        // Schema::table('ingredients', function (Blueprint $table) {
        //     $table->dropColumn('ingredient_category_id');
        // });
        // Schema::dropIfExists('ingredient_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropIndex('ing_path_index');
            $table->dropColumn('materialized_path');
        });
    }
};
