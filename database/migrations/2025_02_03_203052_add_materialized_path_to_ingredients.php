<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bar_memberships', function (Blueprint $table) {
            $table->dropColumn('use_parent_as_substitute');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('materialized_path')->nullable()->index('ing_path_index');
        });

        Schema::table('cocktail_ingredients', function (Blueprint $table) {
            $table->boolean('is_specified')->default(false);
        });

        DB::transaction(function () {
            $categoryToIngredientMap = [];

            // Migrate existing ingredient categories to ingredients
            // Update materialized path for ingredients with ingredient category
            $categories = DB::table('ingredient_categories')->get();
            $categoryToIngredientMap = [];

            foreach ($categories as $category) {
                $userId = DB::table('bars')
                    ->select('created_user_id')
                    ->where('id', $category->bar_id)
                    ->first()
                    ->created_user_id;

                // Create new root ingredient from category
                $newIngredientId = DB::table('ingredients')->insertGetId([
                    'name' => $category->name,
                    'slug' => Str::slug($category->name) . '-' . $category->bar_id . '-' . Str::random(3), // Random slug for new ingredient
                    'description' => $category->description,
                    'bar_id' => $category->bar_id,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                    'created_user_id' => $userId,
                    'materialized_path' => null, // Root ingredients should have null path
                    'parent_ingredient_id' => null, // Root level
                ]);

                // Map category ID to new ingredient ID for later updates
                $categoryToIngredientMap[$category->id] = $newIngredientId;
            }

            // // 3. Update Ingredients with New Parent IDs
            foreach ($categoryToIngredientMap as $oldCategoryId => $newIngredientId) {
                DB::table('ingredients')
                    ->where('ingredient_category_id', $oldCategoryId)
                    ->where('parent_ingredient_id', null)
                    ->update([
                        'parent_ingredient_id' => $newIngredientId
                    ]);
            }

            // 4. Recursively Update Materialized Paths
            $this->updateMaterializedPaths();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bar_memberships', function (Blueprint $table) {
            $table->boolean('use_parent_as_substitute')->default(false);
        });

        Schema::table('cocktail_ingredients', function (Blueprint $table) {
            $table->dropColumn('is_specified');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropIndex('ing_path_index');
            $table->dropColumn('materialized_path');
        });
    }

    private function updateMaterializedPaths($parentId = null, $path = null)
    {
        $ingredients = DB::table('ingredients')
            ->where('parent_ingredient_id', $parentId)
            ->get();

        foreach ($ingredients as $ingredient) {
            // Construct new path
            $newPath = $path !== null ? $path . $ingredient->parent_ingredient_id . '/' : null;

            // Update materialized path for the current ingredient
            DB::table('ingredients')
                ->where('id', $ingredient->id)
                ->update(['materialized_path' => $newPath]);

            // Recursively update children
            $this->updateMaterializedPaths($ingredient->id, $newPath ?? '');
        }
    }
};
